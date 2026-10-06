<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');
$userId = $_SESSION['user_id'] ?? 1;
$dreamId = (int)($_GET['dream_id'] ?? 0);

if (!$dreamId) {
    $s = $pdo->prepare("SELECT id FROM dreams WHERE user_id=? ORDER BY updated_at DESC LIMIT 1");
    $s->execute([$userId]); $dreamId = (int)($s->fetchColumn() ?: 0);
}
if (!$dreamId) { echo json_encode(['success'=>true,'dream'=>null,'nodes'=>[],'todos'=>[]]); exit; }

$s = $pdo->prepare("SELECT * FROM dreams WHERE id=? AND user_id=?");
$s->execute([$dreamId,$userId]); $dream = $s->fetch();

$s = $pdo->prepare("SELECT * FROM nodes WHERE dream_id=? ORDER BY id");
$s->execute([$dreamId]); $nodes = $s->fetchAll();

$s = $pdo->prepare("SELECT * FROM todos WHERE dream_id=? ORDER BY completed,due_date,id");
$s->execute([$dreamId]); $todos = $s->fetchAll();

echo json_encode(['success'=>true,'dream'=>$dream,'nodes'=>$nodes,'todos'=>$todos]);
