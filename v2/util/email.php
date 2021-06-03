<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../lib/phpmailer/Exception.php';
require_once __DIR__ . '/../lib/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../lib/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\SMTP;
//use PHPMailer\PHPMailer\Exception;

function email_send(array $recipients, string $subject, string $html_body, array $attachment_paths = [])
{
    global $config;
    $mail = new PHPMailer(true);

//    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->isSMTP();
    $mail->Host = $config['smtp']['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp']['username'];
    $mail->Password = $config['smtp']['password'];
    if ($config['smtp']['security'] === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = intval($config['smtp']['port'] ?? '587');
    } elseif ($config['smtp']['security'] === 'smtps') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = intval($config['smtp']['port'] ?? '465');
    }

    $mail->setFrom($config['smtp']['from']/*, 'Mailer'*/);
    foreach ($recipients as $recipient) {
        $mail->addAddress($recipient);
    }

    foreach ($attachment_paths as $attachment_name => $attachment_path) {
        $mail->addAttachment($attachment_path, !is_numeric($attachment_name) ? $attachment_name : basename($attachment_path));
    }

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $html_body;
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
}