<?php

namespace BizProfile\Controllers\Api;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class NewsletterController extends Controller
{
    public function subscribe(): void
    {
        if (!Security::rateLimit('api_newsletter_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $required = ['email'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field {$field} is required"]);
                return;
            }
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // Check if email already exists
        $existingSubscriber = Database::query(
            "SELECT * FROM newsletter WHERE email = ? AND status = 'active' LIMIT 1",
            [$data['email']]
        );

        if ($existingSubscriber) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already subscribed']);
            return;
        }

        // Subscribe the user
        $result = Database::execute(
            "INSERT INTO newsletter (email, status) VALUES (?, 'active')",
            [$data['email']]
        );

        if ($result) {
            // Log subscription
            $this->logNewsletterSubscription($data['email'], $_SERVER['REMOTE_ADDR']);

            header('Content-Type: application/json');
            echo json_encode(['message' => 'Thank you for subscribing to our newsletter']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to subscribe']);
        }
    }

    public function unsubscribe(string $email): void
    {
        if (!Security::rateLimit('api_newsletter_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        // Find the subscriber
        $subscriber = Database::query(
            "SELECT * FROM newsletter WHERE email = ? LIMIT 1",
            [$email]
        );

        if (!$subscriber) {
            http_response_code(404);
            echo json_encode(['error' => 'Subscriber not found']);
            return;
        }

        // Unsubscribe (set status to inactive instead of deleting)
        $result = Database::execute(
            "UPDATE newsletter SET status = 'inactive' WHERE email = ?",
            [$email]
        );

        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(['message' => 'Successfully unsubscribed']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to unsubscribe']);
        }
    }

    private function logNewsletterSubscription(string $email, string $ipAddress): void
    {
        Database::execute(
            "INSERT INTO newsletter_log (email, ip_address, action) VALUES (?, ?, 'subscribe')",
            [$email, $ipAddress]
        );
    }
}