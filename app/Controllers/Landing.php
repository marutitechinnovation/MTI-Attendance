<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Landing extends BaseController
{
    public function index()
    {
        return view('landing/index');
    }

    public function privacy()
    {
        return view('landing/privacy');
    }

    public function submitContact()
    {
        $name    = $this->request->getPost('name');
        $email   = $this->request->getPost('email');
        $message = $this->request->getPost('message');

        // Basic validation
        if (empty($name) || empty($email) || empty($message)) {
            return redirect()->back()->with('error', 'All fields are required.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', 'Invalid email address.');
        }

        // Send Email
        $emailService = \Config\Services::email();

        $emailService->setFrom('noreply@mti-attendance.com', 'MTI Attendance Web');
        $emailService->setTo('kuldipparmar18@gmail.com');
        $emailService->setSubject('New Contact Form Submission from ' . $name);
        
        $body = "You have received a new message from the MTI Attendance Landing Page.\n\n";
        $body .= "Name: " . $name . "\n";
        $body .= "Email: " . $email . "\n\n";
        $body .= "Message:\n" . $message . "\n";

        $emailService->setMessage($body);

        if ($emailService->send()) {
            return redirect()->back()->with('success', 'Message sent successfully! We will get back to you soon.');
        } else {
            log_message('error', 'Email failed to send: ' . $emailService->printDebugger(['headers']));
            return redirect()->back()->with('error', 'Failed to send message. Please try again later.');
        }
    }
}
