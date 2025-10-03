<?php

// email-service.php
header("Access-Control-Allow-Origin: https://autopsy.no");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple error reporting
error_reporting(0); // Turn off in production

// Logging function
function logFormSubmission($data, $status, $errors = [], $emailStatus = '')
{
    $logFile = 'form-submissions.log';
    $timestamp = date('Y-m-d H:i:s');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $logEntry = "==========================================\n";
    $logEntry .= "SUBMISSION TIME: $timestamp\n";
    $logEntry .= "STATUS: $status\n";
    if (!empty($emailStatus)) {
        $logEntry .= "EMAIL STATUS: $emailStatus\n";
    }
    $logEntry .= "IP ADDRESS: $ipAddress\n";
    $logEntry .= "USER AGENT: $userAgent\n";
    $logEntry .= "------------------------------------------\n";
    $logEntry .= "FORM DATA:\n";

    foreach ($data as $field => $value) {
        // Sanitize values for logging
        $logValue = !empty($value) ? $value : 'Not provided';
        $logEntry .= "  $field: $logValue\n";
    }

    if (!empty($errors)) {
        $logEntry .= "------------------------------------------\n";
        $logEntry .= "VALIDATION ERRORS:\n";
        foreach ($errors as $error) {
            $logEntry .= "  - $error\n";
        }
    }

    $logEntry .= "==========================================\n\n";

    // Append to log file
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Collect all form data for logging
    $formData = [
        'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
        'work-email' => isset($_POST['work-email']) ? trim($_POST['work-email']) : '',
        'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : '',
        'country' => isset($_POST['country']) ? trim($_POST['country']) : '',
        'organization' => isset($_POST['organization']) ? trim($_POST['organization']) : '',
        'hear-about' => isset($_POST['hear-about']) ? trim($_POST['hear-about']) : '',
        'additional-info' => isset($_POST['additional-info']) ? trim($_POST['additional-info']) : ''
    ];

    // Validate required fields
    $errors = [];
    $name = $formData['name'];
    $email = filter_var($formData['work-email'], FILTER_VALIDATE_EMAIL);
    $country = $formData['country'];
    $organization = $formData['organization'];

    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($email)) {
        $errors[] = 'Valid work email is required';
    }

    if (empty($country)) {
        $errors[] = 'Country is required';
    }

    if (empty($organization)) {
        $errors[] = 'Organization is required';
    }

    // Log the submission regardless of validation status
    if (!empty($errors)) {
        logFormSubmission($formData, 'VALIDATION_FAILED', $errors);
    } else {
        logFormSubmission($formData, 'VALIDATION_PASSED');
    }

    // If validation errors - but still return success to user
    if (!empty($errors)) {
        // Log but don't show errors to user
        logFormSubmission($formData, 'VALIDATION_FAILED_BUT_ACCEPTED', $errors);

        // Always return success to user
        echo json_encode([
            "status" => "success",
            "message" => "Thank you! Your demo request has been submitted successfully. We will contact you shortly."
        ]);
        exit;
    }

    // Prepare email
    $to = "info@autopsy.no"; // Your email
    $subject = "New Demo Request from $organization";

    $body = "NEW DEMO REQUEST RECEIVED\n\n";
    $body .= "================================\n";
    $body .= "CONTACT INFORMATION:\n";
    $body .= "================================\n";
    $body .= "Name: $name\n";
    $body .= "Email: " . $formData['work-email'] . "\n";
    $body .= "Phone: " . ($formData['phone'] ?: 'Not provided') . "\n";
    $body .= "Country: $country\n";
    $body .= "Organization: $organization\n";
    $body .= "How they heard about us: " . ($formData['hear-about'] ?: 'Not specified') . "\n\n";

    if (!empty($formData['additional-info'])) {
        $body .= "ADDITIONAL INFORMATION:\n";
        $body .= "================================\n";
        $body .= $formData['additional-info'] . "\n\n";
    }

    $body .= "SUBMISSION DETAILS:\n";
    $body .= "================================\n";
    $body .= "Submitted on: " . date('Y-m-d H:i:s') . "\n";
    $body .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
    $body .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "\n";

    $headers = "From: website@autopsy.no\r\n";
    $headers .= "Reply-To: " . $formData['work-email'] . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Try to send email
    $emailSent = false;
    try {
        $emailSent = mail($to, $subject, $body, $headers);
    } catch (Exception $e) {
        // Log email exception but continue
        error_log("Mail exception: " . $e->getMessage());
    }

    if ($emailSent) {
        // Log email success
        error_log("Demo request submitted successfully from: " . $formData['work-email']);
        logFormSubmission($formData, 'EMAIL_SENT_SUCCESS', [], 'SUCCESS');
    } else {
        // Log email failure but don't tell user
        error_log("Mail failed for demo request from: " . $formData['work-email']);
        logFormSubmission($formData, 'EMAIL_SENT_FAILED', [], 'FAILED');
    }

    // Always return success to user regardless of email status
    echo json_encode([
        "status" => "success",
        "message" => "Thank you! Your demo request has been submitted successfully. We will contact you shortly."
    ]);
} else {
    // Even for non-POST requests, return success structure
    echo json_encode([
        "status" => "success",
        "message" => "Thank you! Your demo request has been submitted successfully. We will contact you shortly."
    ]);
}
