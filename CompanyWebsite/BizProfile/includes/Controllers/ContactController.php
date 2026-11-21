<?php

namespace BizProfile\Controllers;

use BizProfile\Core\Controller;
use BizProfile\Core\Database;
use BizProfile\Core\Security;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ContactController extends Controller
{
    public function index(): void
    {
        $this->view('contact', [
            'settings' => $this->getWebsiteSettings()
        ]);
    }
    
    public function store(): void
    {
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!Security::validateCsrfToken($csrfToken)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/contact');
        }
        
        $name = Security::sanitize($_POST['name'] ?? '');
        $company = Security::sanitize($_POST['company'] ?? '');
        $phone = Security::sanitize($_POST['phone'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $subject = Security::sanitize($_POST['subject'] ?? '');
        $message = Security::sanitize($_POST['message'] ?? '');
        
        if (!$email) {
            $_SESSION['error'] = 'Invalid email address';
            $this->redirect('/contact');
        }
        
        Database::execute(
            "INSERT INTO enquiries (name, company, phone, email, subject, message) VALUES (?, ?, ?, ?, ?, ?)",
            [$name, $company, $phone, $email, $subject, $message]
        );
        
        $this->sendNotificationEmail($name, $email, $subject, $message);
        
        $_SESSION['success'] = 'Your enquiry has been submitted successfully';
        $this->redirect('/contact');
    }
    
    private function sendNotificationEmail(string $name, string $email, string $subject, string $message): void
    {
        $settings = $this->getWebsiteSettings();
        
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'] ?? 'localhost';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'] ?? '';
            $mail->Password = $_ENV['MAIL_PASSWORD'] ?? '';
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
            $mail->Port = $_ENV['MAIL_PORT'] ?? 587;
            
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@datachron.in', $_ENV['MAIL_FROM_NAME'] ?? 'BizProfile');
            $mail->addAddress($settings['email'] ?? 'info@bizprofile.com');
            $mail->addReplyTo($email, $name);
            
            $mail->isHTML(true);
            $mail->Subject = "New Enquiry: {$subject}";
            $mail->Body = "<h3>New Enquiry</h3><p><strong>Name:</strong> {$name}</p><p><strong>Email:</strong> {$email}</p><p><strong>Company:</strong> {$company}</p><p><strong>Phone:</strong> {$phone}</p><p><strong>Message:</strong> {$message}</p>";
            
            $mail->send();
        } catch (Exception $e) {
            error_log("Email error: " . $mail->ErrorInfo);
        }
    }
}