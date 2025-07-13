<?php
// forms/contact.php
header("Content-Type: application/json");

// 1) Load Composer’s autoloader
require __DIR__ . "/vendor/autoload.php";

use SendinBlue\Client\Configuration;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client as GuzzleClient;

// 2) Enforce POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode("Method Not Allowed");
    exit();
}

// 3) Sanitize & validate
$name = strip_tags(trim($_POST["name"] ?? ""));
$email = filter_var(trim($_POST["email"] ?? ""), FILTER_VALIDATE_EMAIL);
$subject = strip_tags(trim($_POST["subject"] ?? ""));
$message = trim($_POST["message"] ?? "");

if (!$name || !$email || !$subject || !$message) {
    http_response_code(400);
    echo json_encode("Missing required fields");
    exit();
}

// 4) Configure SDK with your API key
$config = Configuration::getDefaultConfiguration()->setApiKey(
    "api-key",
    "<insert_api_key>"
);

$apiInstance = new TransactionalEmailsApi(new GuzzleClient(), $config);

// 5) Build and send email
$emailParams = (new SendSmtpEmail())
    ->setSender(["email" => "<insert_email_id>", "name" => $name])
    ->setTo([
        ["email" => "<insert_email_id>", "name" => "<insert_name>"],
    ])
    ->setReplyTo(["email" => $email, "name" => $name])
    ->setSubject($subject)
    ->setTextContent("Name: $name\nEmail: $email\n\n$message")
    ->setHtmlContent(
        "<p><strong>Name:</strong> $name</p>" .
            "<p><strong>Email:</strong> $email</p>" .
            "<p><strong>Message:</strong><br/>" .
            nl2br(htmlspecialchars($message)) .
            "</p>"
    );

try {
    $result = $apiInstance->sendTransacEmail($emailParams);
    file_put_contents("/tmp/email_response.log", print_r($result, true));
    echo "OK";
} catch (\Exception $e) {
    file_put_contents("/tmp/email_error.log", $e->getMessage());
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
