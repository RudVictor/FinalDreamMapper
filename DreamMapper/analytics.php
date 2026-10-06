<?php
declare(strict_types=1);
session_start();
require_once 'db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 1;
$dreamId = (int)($_GET['dream_id'] ?? 0);

if (!$dreamId) {
    $s=$pdo->prepare("SELECT id FROM dreams WHERE user_id=? ORDER BY updated_at DESC LIMIT 1");
    $s->execute([$userId]); $dreamId=(int)($s->fetchColumn() ?: 0);
}
if (!$dreamId) { echo json_encode(['success'=>true,'metrics'=>[],'trend'=>[]]); exit; }

$s=$pdo->prepare("SELECT COUNT(*) total, SUM(status='completed') completed, SUM(risk_level='high') high_risk, SUM(reward_xp) xp FROM nodes WHERE dream_id=?");
$s->execute([$dreamId]); $x=$s->fetch();
$total=(int)($x['total']??0); $completed=(int)($x['completed']??0);
$completion=$total ? round($completed/$total*100,1) : 0;
$risk= $total ? round(((int)$x['high_risk']/$total)*100,1) : 0;
$success=max(0,min(100, round(45 + $completion*0.55 - $risk*0.25,1)));
$score=(int)$completed*100 + (int)($x['xp']??0);

$trend=[];
$s=$pdo->prepare("SELECT snapshot_date,completion_rate,success_probability,risk_index,xp_earned FROM user_analytics WHERE user_id=? AND dream_id=? ORDER BY snapshot_date ASC LIMIT 14");
$s->execute([$userId,$dreamId]); $trend=$s->fetchAll();

$today=date('Y-m-d');
$ins=$pdo->prepare("INSERT INTO user_analytics (user_id,dream_id,completion_rate,success_probability,risk_index,xp_earned,streak_days,time_spent_minutes,snapshot_date)
VALUES (?,?,?,?,?,?,?,?,?)
ON DUPLICATE KEY UPDATE completion_rate=VALUES(completion_rate),success_probability=VALUES(success_probability),risk_index=VALUES(risk_index),xp_earned=VALUES(xp_earned)");
$ins->execute([$userId,$dreamId,$completion,$success,$risk,$score,0,0,$today]);

$pdo->prepare("UPDATE dreams SET dream_score=?,success_probability=? WHERE id=?")->execute([$score,$success,$dreamId]);

echo json_encode(['success'=>true,'metrics'=>[
    'completion_rate'=>$completion,
    'risk_index'=>$risk,
    'success_probability'=>$success,
    'dream_score'=>$score,
    'streak_days'=>0
],'trend'=>$trend]);
