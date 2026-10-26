<?php
// Robust contact form handler with PHPMailer-if-present or built-in SMTP fallback.
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/config.php';

function sanitize($v) { return trim(filter_var($v, FILTER_SANITIZE_STRING)); }
function is_ajax() {
  return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
         (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

// Basic validation
$name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
$email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) : false;
$phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$honeypot = isset($_POST['website']) ? $_POST['website'] : '';

$errors = [];
if ($honeypot) $errors[] = 'Bot detected.';
if (!$name) $errors[] = 'Name is required.';
if (!$email) $errors[] = 'Valid email is required.';
if (!$message) $errors[] = 'Message is required.';

if (!empty($errors)) {
  $resp = ['ok'=>false, 'errors'=>$errors];
  if (is_ajax()) { header('Content-Type: application/json'); echo json_encode($resp); }
  else { echo implode('<br>', $errors); }
  exit;
}

// Compose body
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$body = "New contact form submission:\n\n"
      . "Name: $name\n"
      . "Email: " . ($email ?: '') . "\n"
      . "Phone: $phone\n"
      . "Message:\n$message\n\n"
      . "IP: $ip\n"
      . "UA: $ua\n";

$sent = false;
$error_msg = '';

// Try PHPMailer if available
$hasPHPMailer = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
  require_once __DIR__ . '/../vendor/autoload.php';
  if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
    $hasPHPMailer = true;
  }
}
if ($hasPHPMailer) {
  try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = SMTP_AUTH;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port = SMTP_PORT;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom(SMTP_USERNAME, 'Website Contact');
    $mail->addAddress(MAIL_TO, MAIL_TO_NAME);
    $mail->addReplyTo($email ?: SMTP_USERNAME, $name ?: 'Visitor');

    $mail->Subject = MAIL_SUBJECT;
    $mail->Body = $body;

    $sent = $mail->send();
  } catch (\Throwable $e) {
    $error_msg = 'PHPMailer error: ' . $e->getMessage();
    $sent = false;
  }
}

// Fallback: minimal SMTP without dependencies (LOGIN auth)
if (!$sent) {
  $context = stream_context_create();
  $remote = (SMTP_SECURE === 'ssl') ? 'ssl://' . SMTP_HOST : SMTP_HOST;
  $port = SMTP_PORT;
  $fp = @stream_socket_client("$remote:$port", $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);
  if ($fp) {
    stream_set_timeout($fp, 20);
    $read = function() use ($fp) { return fgets($fp, 512); };
    $write = function($cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };
    $read(); // banner
    $write("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    while ($line = $read()) { if (substr($line,3,1) != '-') break; }
    if (SMTP_SECURE === 'tls') { $write("STARTTLS"); $read(); stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT); $write("EHLO localhost"); $read(); }
    if (SMTP_AUTH) {
      $write("AUTH LOGIN");
      $read();
      $write(base64_encode(SMTP_USERNAME)); $read();
      $write(base64_encode(SMTP_PASSWORD)); $read();
    }
    $from = SMTP_USERNAME;
    $write("MAIL FROM:<$from>"); $read();
    $write("RCPT TO:<" . MAIL_TO . ">"); $read();
    $write("DATA"); $read();
    $headers = "From: Website Contact <" . SMTP_USERNAME . ">\r\n"
             . "Reply-To: " . ($email ?: SMTP_USERNAME) . "\r\n"
             . "MIME-Version: 1.0\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";
    $data = $headers . "\r\n" . "Subject: " . MAIL_SUBJECT . "\r\n\r\n" . $body . "\r\n.";
    $write($data);
    $read();
    $write("QUIT"); $read();
    fclose($fp);
    $sent = true;
  } else {
    $error_msg = "SMTP fallback connect error: $errstr ($errno)";
  }
}

if ($sent) {
  $resp = ['ok'=>true, 'message'=>'Thanks! Your message has been sent.'];
  if (is_ajax()) { header('Content-Type: application/json'); echo json_encode($resp); }
  else { echo "Thanks! Your message has been sent."; }
} else {
  $resp = ['ok'=>false, 'errors'=>['Failed to send. ' . $error_msg]];
  if (is_ajax()) { header('Content-Type: application/json'); echo json_encode($resp); }
  else { echo "Failed to send. " . htmlspecialchars($error_msg); }
}
