<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS, GET");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); echo json_encode(['ok'=>true]); exit; }
if (isset($_GET['ping'])) { echo json_encode(['ok'=>true,'message'=>'contact.php alive']); exit; }

error_reporting(E_ALL);
ini_set('display_errors','0');

function jerror($code, $msg, $hint=null, $extra=array()) {
  $resp = ['success'=>false,'code'=>$code,'error'=>$msg];
  if ($hint) { $resp['hint'] = $hint; }
  if (!empty($extra)) { $resp['extra'] = $extra; }
  http_response_code(200);
  echo json_encode($resp);
  exit;
}

function field($k,$d=''){
  $v = '';
  if (isset($_POST[$k])) $v = $_POST[$k];
  elseif (isset($_GET[$k])) $v = $_GET[$k];
  return trim((string)$v) === '' ? $d : trim((string)$v);
}

$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
  jerror('vendor_missing', 'PHPMailer vendor files not found.', 'Upload the /vendor directory (PHPMailer).', ['path'=>$autoload]);
}
require $autoload;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$SMTP_HOST = 'smtp.titan.email';
$SMTP_PORT = 465;
$SMTP_USER = 'info@mykaytech.com';
$SMTP_PASS = 'Dorothy@2018';
$TO_EMAIL  = 'info@mykaytech.com';
$FROM_NAME = 'Kay-Tech Website';

$name    = field('name','Visitor'); // OPTIONAL
$email   = field('email');          // REQUIRED
$message = field('message');        // REQUIRED
$service = field('service','General'); // OPTIONAL
$subject = 'Kay-Tech Website Inquiry';

$errors = [];
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if ($message === '') $errors[] = 'Message is required.';
if (!empty($errors)) jerror('validation_failed','Validation error.', null, ['errors'=>$errors]);

try {
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host       = $SMTP_HOST;
  $mail->SMTPAuth   = true;
  $mail->Username   = $SMTP_USER;
  $mail->Password   = $SMTP_PASS;
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  $mail->Port       = $SMTP_PORT;
  $mail->Timeout    = 15;

  $mail->setFrom($SMTP_USER, $FROM_NAME);
  $mail->addAddress($TO_EMAIL);
  $mail->addReplyTo($email, $name);

  $bodyHtml = "<h3>New Website Inquiry</h3>"
    . "<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>"
    . "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>"
    . "<p><strong>Service:</strong> " . htmlspecialchars($service) . "</p>"
    . "<p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";

  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body    = $bodyHtml;
  $mail->AltBody = strip_tags(str_replace(['<br>','<br/>','<br />'],"\n",$bodyHtml));

  if ($mail->send()) {
    echo json_encode(['success'=>true]);
  } else {
    jerror('send_failed', 'Mailer failed to send.', $mail->ErrorInfo ?: null);
  }
} catch (Exception $e) {
  jerror('exception', 'Unhandled exception during send.', $e->getMessage());
}
