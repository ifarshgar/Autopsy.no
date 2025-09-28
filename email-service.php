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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Use $_POST for form data
    $firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
    $lastname = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
    $email = isset($_POST['work-email']) ? filter_var(trim($_POST['work-email']), FILTER_VALIDATE_EMAIL) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $country = isset($_POST['country']) ? trim($_POST['country']) : '';
    $organization = isset($_POST['organization']) ? trim($_POST['organization']) : '';
    $hear_about = isset($_POST['hear-about']) ? trim($_POST['hear-about']) : '';
    $additional_info = isset($_POST['additional-info']) ? trim($_POST['additional-info']) : '';
    
    // Combine first and last name
    $fullname = trim("$firstname $lastname");
    
    // Validate required fields
    $errors = [];
    
    if (empty($firstname)) {
        $errors[] = 'First name is required';
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
    
    // If validation errors
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Validation failed",
            "errors" => $errors
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
    $body .= "Full Name: $fullname\n";
    $body .= "Email: $email\n";
    $body .= "Phone: " . ($phone ?: 'Not provided') . "\n";
    $body .= "Country: $country\n";
    $body .= "Organization: $organization\n";
    $body .= "How they heard about us: " . ($hear_about ?: 'Not specified') . "\n\n";
    
    if (!empty($additional_info)) {
        $body .= "ADDITIONAL INFORMATION:\n";
        $body .= "================================\n";
        $body .= "$additional_info\n\n";
    }
    
    $body .= "SUBMISSION DETAILS:\n";
    $body .= "================================\n";
    $body .= "Submitted on: " . date('Y-m-d H:i:s') . "\n";
    $body .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
    $body .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "\n";
    
    $headers = "From: website@autopsy.no\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Try to send email
    if (mail($to, $subject, $body, $headers)) {
        // Log the submission (optional)
        error_log("Demo request submitted successfully from: $email");
        
        echo json_encode([
            "status" => "success",
            "message" => "Thank you! Your demo request has been submitted successfully. We will contact you shortly."
        ]);
    } else {
        // If mail fails, log the error but still return success to user
        error_log("Mail failed for demo request from: $email");
        
        // You might want to implement a fallback email method here
        // For now, we'll still show success to the user
        echo json_encode([
            "status" => "success",
            "message" => "Thank you! Your request has been received. We'll contact you shortly."
        ]);
    }
    
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed"
    ]);
}
?>