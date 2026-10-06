<?php
declare(strict_types=1);
session_start();
require_once 'db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 1;
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$prompt = trim($input['prompt'] ?? '');
$targetDate = !empty($input['target_date']) ? $input['target_date'] : null;

if ($prompt === '') {
    http_response_code(422);
    echo json_encode(['success'=>false, 'message'=>'Dream prompt is required.']);
    exit;
}

/*
 * Local Smart Processing Engine
 * -----------------------------
 * This is intentionally rule-based for a BCA prototype.
 * It can later be replaced by an LLM/API without changing the graph UI.
 */
function inferTitle(string $prompt): string {
    $p = preg_replace('/^(i want to|i want|my dream is to|i would like to)\s+/i', '', $prompt);
    return ucfirst(trim($p, " ."));
}
function detectDomain(string $prompt): string {
    $map = [
        'game' => 'Game Development',
        'developer' => 'Software Development',
        'software' => 'Software Development',
        'app' => 'App Development',
        'business' => 'Business',
        'startup' => 'Startup',
        'job' => 'Career',
        'career' => 'Career',
        'fitness' => 'Fitness',
        'weight' => 'Fitness',
        'study' => 'Education',
        'degree' => 'Education',
        'exam' => 'Education',
        'travel' => 'Travel',
        'youtube' => 'Content Creation',
        'content' => 'Content Creation'
    ];
    foreach ($map as $word=>$domain) if (stripos($prompt, $word) !== false) return $domain;
    return 'Personal Goal';
}
function estimateDays(string $prompt): int {
    if (preg_match('/(\d+)\s*(year|years)/i', $prompt, $m)) return max(30, (int)$m[1] * 365);
    if (preg_match('/(\d+)\s*(month|months)/i', $prompt, $m)) return max(14, (int)$m[1] * 30);
    return 180;
}

$title = inferTitle($prompt);
$domain = detectDomain($prompt);
$days = estimateDays($prompt);

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("INSERT INTO dreams (user_id,title,prompt,target_date,success_probability) VALUES (?,?,?,?,?)");
    $stmt->execute([$userId, $title, $prompt, $targetDate, 50]);
    $dreamId = (int)$pdo->lastInsertId();

    $root = $pdo->prepare("INSERT INTO nodes (dream_id,parent_id,title,description,node_type,status,priority,estimated_days,reward_xp,risk_level) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $root->execute([$dreamId,null,$title,"Main dream objective",'dream','in_progress','high',$days,100,'medium']);
    $rootId = (int)$pdo->lastInsertId();

    $milestones = [
        ['Define success criteria',"Write a measurable definition of success for {$title}.",'goal','high',7,25,'low'],
        ["Learn core {$domain} skills","Build the foundational knowledge needed for the dream.",'milestone','high',30,50,'medium'],
        ['Build a practical portfolio',"Complete small projects that prove your ability.",'milestone','high',45,60,'medium'],
        ['Get feedback and improve',"Use mentors, users or peers to identify gaps.",'milestone','medium',30,45,'low'],
        ['Execute the launch step',"Apply, launch, publish, sell or otherwise take the dream into the real world.",'milestone','high',30,80,'high'],
        ['Backup Route',"If the primary route slows down, use a smaller adjacent goal to maintain momentum.",'backup','medium',21,35,'medium']
    ];

    $nodeIds = [];
    foreach ($milestones as $m) {
        $s = $pdo->prepare("INSERT INTO nodes (dream_id,parent_id,title,description,node_type,status,priority,estimated_days,reward_xp,risk_level) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $s->execute([$dreamId,$rootId,$m[0],$m[1],$m[2],'pending',$m[3],$m[4],$m[5],$m[6]]);
        $nodeIds[] = (int)$pdo->lastInsertId();
    }

    $taskTemplates = [
        'Break the dream into a one-page definition.',
        'Choose one beginner-friendly learning resource.',
        'Schedule a 30-minute practice block.',
        'Create one small proof-of-work project.',
        'Ask one person for feedback.',
        'Review progress and choose the next smallest action.'
    ];
    $todo = $pdo->prepare("INSERT INTO todos (user_id,dream_id,node_id,title,due_date) VALUES (?,?,?,?,?)");
    foreach ($taskTemplates as $i=>$task) {
        $nodeId = $nodeIds[$i % count($nodeIds)];
        $due = date('Y-m-d', strtotime("+".($i+1)." day"));
        $todo->execute([$userId,$dreamId,$nodeId,$task,$due]);
    }

    $n = $pdo->prepare("INSERT INTO notifications (user_id,dream_id,title,message,notification_type) VALUES (?,?,?,?,?)");
    $n->execute([$userId,$dreamId,'Dream mapped successfully','Your first roadmap and tasks are ready. Complete the smallest step first.','milestone']);

    $pdo->commit();
    echo json_encode(['success'=>true,'dream_id'=>$dreamId,'message'=>'Dream mapped successfully.']);
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
