<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS, GET");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); echo json_encode(['ok'=>true]); exit; }
$payload = [
  'ok' => true,
  'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
  'data' => ['get'=>$_GET, 'post'=>$_POST],
  'hint' => 'This file does NOT send email. It only proves the frontend can reach PHP.'
];
http_response_code(200);
echo json_encode($payload);
?>