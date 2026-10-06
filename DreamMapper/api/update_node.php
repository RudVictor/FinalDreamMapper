<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');
$userId = $_SESSION['user_id'] ?? 1;
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$id = (int)($data['id'] ?? 0);
$title = trim($data['title'] ?? '');
$status = $data['status'] ?? null;
$allowed = ['pending','in_progress','completed'];

if (!$id) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'Node ID required']); exit; }

$s = $pdo->prepare("SELECT n.* FROM nodes n JOIN dreams d ON d.id=n.dream_id WHERE n.id=? AND d.user_id=?");
$s->execute([$id,$userId]); $node = $s->fetch();
if (!$node) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Node not found']); exit; }

$fields=[]; $values=[];
if ($title !== '') { $fields[]='title=?'; $values[]=$title; }
if (in_array($status,$allowed,true)) { $fields[]='status=?'; $values[]=$status; }
if (!$fields) { echo json_encode(['success'=>false,'message'=>'Nothing to update']); exit; }

$values[]=$id;
$pdo->prepare("UPDATE nodes SET ".implode(',',$fields)." WHERE id=?")->execute($values);
echo json_encode(['success'=>true]);
