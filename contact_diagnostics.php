<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
error_reporting(E_ALL);
ini_set('display_errors','0');

function out($arr){ http_response_code(200); echo json_encode($arr); exit; }

$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) out(['ok'=>false,'code'=>'vendor_missing','error'=>'PHPMailer vendor files not found.','path'=>$autoload]);

require $autoload;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$SMTP_HOST = 'smtp.titan.email';
$SMTP_PORT = 465;
$SMTP_USER = 'info@mykaytech.com';
$SMTP_PASS = 'Dorothy@2018';

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $SMTP_HOST;
$mail->SMTPAuth = true;
$mail->Username = $SMTP_USER;
$mail->Password = $SMTP_PASS;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = $SMTP_PORT;
$mail->Timeout = 15;

try {
  if ($mail->smtpConnect()) {
    out(['ok'=>true,'stage'=>'connected','host'=>$SMTP_HOST,'port'=>$SMTP_PORT]);
  } else {
    out(['ok'=>false,'code'=>'connect_failed','error'=>$mail->ErrorInfo]);
  }
} catch (Exception $e) {
  out(['ok'=>false,'code'=>'exception','error'=>$e->getMessage()]);
}
?>