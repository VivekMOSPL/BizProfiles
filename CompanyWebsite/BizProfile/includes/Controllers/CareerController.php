<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class CareerController extends Controller
{
    public function index(): void
    {
        $jobs = Database::query("SELECT * FROM careers WHERE status = 'active' ORDER BY sort_order ASC");
        
        $this->view('careers', [
            'jobs' => $jobs,
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function apply(): void
    {
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/careers');
        }
        
        $careerId = (int)($_POST['career_id'] ?? 0);
        $name = Security::sanitize($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $phone = Security::sanitize($_POST['phone'] ?? '');
        $coverLetter = Security::sanitize($_POST['cover_letter'] ?? '');
        
        if (!$email) {
            $_SESSION['error'] = 'Invalid email address';
            $this->redirect('/careers');
        }
        
        $resume = $_FILES['resume'] ?? null;
        $errors = Security::validateFileUpload($resume, ['application/pdf'], 5242880);
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            $this->redirect('/careers');
        }
        
        $resumePath = 'uploads/resumes/' . time() . '_' . $resume['name'];
        move_uploaded_file($resume['tmp_name'], BASE_PATH . '/assets/' . $resumePath);
        
        Database::execute(
            "INSERT INTO job_applications (career_id, name, email, phone, resume, cover_letter) VALUES (?, ?, ?, ?, ?, ?)",
            [$careerId, $name, $email, $phone, $resumePath, $coverLetter]
        );
        
        $_SESSION['success'] = 'Your application has been submitted successfully';
        $this->redirect('/careers');
    }
}