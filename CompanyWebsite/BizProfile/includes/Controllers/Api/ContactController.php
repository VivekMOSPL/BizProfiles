<?php

namespace BizProfile\Controllers\Api;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;

class ContactController extends Controller
{
    public function store(): void
    {
        if (!Security::rateLimit('api_contact_' . $_SERVER['REMOTE_ADDR'], 100)) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $required = ['name', 'email', 'subject', 'message'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field {$field} is required"]);
                return;
            }
        }

        Database::execute(
            "INSERT INTO enquiries (name, email, subject, message, status) VALUES (?, ?, ?, ?, 'new')",
            [
                $data['name'],
                $data['email'],
                $data['subject'],
                $data['message']
            ]
        );

        header('Content-Type: application/json');
        echo json_encode(['message' => 'Contact form submitted successfully']);
    }
}