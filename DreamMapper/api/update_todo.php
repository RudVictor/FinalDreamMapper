<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');
$userId = $_SESSION['user_id'] ?? 1;
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = (int)($data['id'] ?? 0);
$completed = !empty($data['completed']) ? 1 : 0;

$s = $pdo->prepare("SELECT t.* FROM todos t WHERE t.id=? AND t.user_id=?");
$s->execute([$id,$userId]); $todo = $s->fetch();
if (!$todo) { http_response_code(404); echo json_encode(['success'=>false]); exit; }

$doneAt = $completed ? date('Y-m-d H:i:s') : null;
$pdo->prepare("UPDATE todos SET completed=?, completed_at=? WHERE id=?")->execute([$completed,$doneAt,$id]);

if ($todo['node_id']) {
    $status = $completed ? 'completed' : 'in_progress';
    $pdo->prepare("UPDATE nodes SET status=? WHERE id=?")->execute([$status,$todo['node_id']]);
}
echo json_encode(['success'=>true]);
