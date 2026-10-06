<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');
$userId = $_SESSION['user_id'] ?? 1;
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$title = trim($data['title'] ?? '');
$dreamId = (int)($data['dream_id'] ?? 0);
if (!$title || !$dreamId) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'Title and dream are required']); exit; }

$s=$pdo->prepare("INSERT INTO todos (user_id,dream_id,title,due_date) VALUES (?,?,?,?)");
$s->execute([$userId,$dreamId,$title,date('Y-m-d')]);
echo json_encode(['success'=>true,'id'=>(int)$pdo->lastInsertId()]);
