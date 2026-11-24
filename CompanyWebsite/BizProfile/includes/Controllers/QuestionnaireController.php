<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\AuthSessionService;

class QuestionnaireController extends Controller
{
    private AuthSessionService $session;

    public function __construct()
    {
        $this->session = new AuthSessionService();
    }

    /**
     * GET /questionnaire - Show the active questionnaire
     * Redirects to default industry questionnaire or specific slug
     */
    public function index(): void
    {
        $questionnaire = $this->getActiveQuestionnaire('general');
        if (!$questionnaire) {
            $this->adminView('questionnaire/not_available', [
                'message' => 'No questionnaire is currently available.'
            ]);
            return;
        }
        $this->showQuestionnaire($questionnaire['slug']);
    }

    /**
     * GET /questionnaire/{slug} - Show a specific questionnaire
     */
    public function show(string $slug): void
    {
        $questionnaire = $this->getActiveQuestionnaire($slug);
        if (!$questionnaire) {
            http_response_code(404);
            $this->adminView('questionnaire/not_found', [
                'slug' => $slug
            ]);
            return;
        }

        $questions = $this->getQuestions($questionnaire['id']);

        $this->adminView('questionnaire/show', [
            'questionnaire' => $questionnaire,
            'questions' => $questions,
            'csrf_token' => $this->session->getCsrfToken(),
        ]);
    }

    /**
     * POST /questionnaire/{slug}/submit - Submit questionnaire responses
     */
    public function submit(string $slug): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method not allowed';
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$this->session->validateCsrfToken($csrfToken)) {
            http_response_code(400);
            echo 'Invalid CSRF token';
            return;
        }

        $questionnaire = $this->getActiveQuestionnaire($slug);
        if (!$questionnaire) {
            http_response_code(404);
            echo 'Questionnaire not found';
            return;
        }

        $questions = $this->getQuestions($questionnaire['id']);
        $questionIds = array_column($questions, 'id');

        // Validate required questions
        $errors = [];
        $answers = [];

        foreach ($questions as $question) {
            $qKey = $question['question_key'];
            $postKey = 'q_' . $question['id'];
            $value = $_POST[$postKey] ?? null;

            if ($question['is_required'] && (empty($value) || $value === '')) {
                $errors[$qKey] = 'This field is required.';
                continue;
            }

            $answers[$question['id']] = [
                'question_id' => $question['id'],
                'question_type' => $question['question_type'],
                'answer_text' => is_array($value) ? json_encode($value) : $value,
                'answer_json' => is_array($value) ? json_encode($value) : null,
            ];
        }

        if (!empty($errors)) {
            $this->adminView('questionnaire/show', [
                'questionnaire' => $questionnaire,
                'questions' => $questions,
                'errors' => $errors,
                'csrf_token' => $this->session->getCsrfToken(),
                'submitted_values' => $_POST,
            ]);
            return;
        }

        // Save response
        $respondentName = $_SESSION['user_name'] ?? $_POST['respondent_name'] ?? null;
        $respondentEmail = $_SESSION['user_email'] ?? $_POST['respondent_email'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        $sessionToken = bin2hex(random_bytes(16));
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        try {
            $pdo = Database::getInstance();
            $pdo->beginTransaction();

            $pdo->execute(
                "INSERT INTO questionnaire_responses
                 (questionnaire_id, respondent_name, respondent_email, respondent_ip, user_id, session_token, is_complete, submitted_at)
                 VALUES (?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)",
                [$questionnaire['id'], $respondentName, $respondentEmail, $ipAddress, $userId, $sessionToken]
            );

            $responseId = $pdo->lastInsertId();

            foreach ($answers as $answer) {
                $pdo->execute(
                    "INSERT INTO response_answers
                     (response_id, question_id, answer_text, answer_json)
                     VALUES (?, ?, ?, ?)",
                    [$responseId, $answer['question_id'], $answer['answer_text'], $answer['answer_json']]
                );
            }

            $pdo->commit();

            $this->adminView('questionnaire/thank_you', [
                'questionnaire' => $questionnaire,
                'response_id' => $responseId,
            ]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("QuestionnaireController::submit error: " . $e->getMessage());
            http_response_code(500);
            echo 'Failed to save response. Please try again.';
        }
    }

    /**
     * GET /admin/questionnaires - List all questionnaires (admin)
     */
    public function adminList(): void
    {
        $this->session->requireOwner();

        $questionnaires = Database::query(
            "SELECT * FROM questionnaires ORDER BY is_default DESC, industry, title"
        );

        $this->adminView('questionnaire/admin_list', [
            'questionnaires' => $questionnaires,
            'csrf_token' => $this->session->getCsrfToken(),
        ]);
    }

    /**
     * GET /admin/questionnaires/create - Show create questionnaire form
     */
    public function adminCreate(): void
    {
        $this->session->requireOwner();
        $this->adminView('questionnaire/admin_create', [
            'csrf_token' => $this->session->getCsrfToken(),
        ]);
    }

    /**
     * POST /admin/questionnaires - Create a new questionnaire
     */
    public function adminStore(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $this->session->requireOwner();

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$this->session->validateCsrfToken($csrfToken)) {
            http_response_code(400);
            echo 'Invalid CSRF token';
            return;
        }

        $slug = $_POST['slug'] ?? '';
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $industry = $_POST['industry'] ?? 'general';

        if (empty($slug) || empty($title)) {
            http_response_code(400);
            echo 'Slug and title are required';
            return;
        }

        try {
            Database::execute(
                "INSERT INTO questionnaires (slug, title, description, industry, is_active)
                 VALUES (?, ?, ?, ?, 1)",
                [$slug, $title, $description, $industry]
            );
            $this->redirect('/admin/questionnaires');
        } catch (\Exception $e) {
            error_log("QuestionnaireController::adminStore error: " . $e->getMessage());
            http_response_code(500);
            echo 'Failed to create questionnaire';
        }
    }

    /**
     * GET /admin/questionnaires/{id}/responses - View responses for a questionnaire
     */
    public function adminResponses(int $questionnaireId): void
    {
        $this->session->requireOwner();

        $questionnaire = Database::query(
            "SELECT * FROM questionnaires WHERE id = ? LIMIT 1",
            [$questionnaireId]
        );
        if (empty($questionnaire)) {
            http_response_code(404);
            echo 'Questionnaire not found';
            return;
        }

        $responses = Database::query(
            "SELECT * FROM questionnaire_responses
             WHERE questionnaire_id = ?
             ORDER BY created_at DESC",
            [$questionnaireId]
        );

        $this->adminView('questionnaire/admin_responses', [
            'questionnaire' => $questionnaire[0],
            'responses' => $responses,
            'csrf_token' => $this->session->getCsrfToken(),
        ]);
    }

    // ========================================================================
    // Private helpers
    // ========================================================================

    private function getActiveQuestionnaire(string $slug): ?array
    {
        try {
            $row = Database::query(
                "SELECT * FROM questionnaires WHERE slug = ? AND is_active = 1 LIMIT 1",
                [$slug]
            );
            return empty($row) ? null : $row[0];
        } catch (\Exception $e) {
            error_log("QuestionnaireController::getActiveQuestionnaire error: " . $e->getMessage());
            return null;
        }
    }

    private function getQuestions(int $questionnaireId): array
    {
        try {
            return Database::query(
                "SELECT * FROM questions
                 WHERE questionnaire_id = ?
                 ORDER BY sort_order ASC, id ASC",
                [$questionnaireId]
            );
        } catch (\Exception $e) {
            error_log("QuestionnaireController::getQuestions error: " . $e->getMessage());
            return [];
        }
    }
}
