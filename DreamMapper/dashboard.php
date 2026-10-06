<?php
session_start();
require_once 'db.php';

$userId = $_SESSION['user_id'] ?? 1;

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$dreamsStmt = $pdo->prepare("SELECT * FROM dreams WHERE user_id = ? ORDER BY updated_at DESC");
$dreamsStmt->execute([$userId]);
$dreams = $dreamsStmt->fetchAll();

$activeDream = $dreams[0] ?? null;
$notificationsStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 8");
$notificationsStmt->execute([$userId]);
$notifications = $notificationsStmt->fetchAll();

$quote = $pdo->query("SELECT * FROM motivation_media WHERE media_type='quote' AND is_active=1 ORDER BY RAND() LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DreamMapper Dashboard</title>
<link rel="stylesheet" href="assets/css/styles.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/cytoscape@3.30.3/dist/cytoscape.min.js"></script>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand"><span class="brand-mark">✦</span> DreamMapper</div>
        <nav>
            <a class="nav-item active" href="#overview">⌂ <span>Dashboard</span></a>
            <a class="nav-item" href="#dream-engine">✧ <span>Dream Engine</span></a>
            <a class="nav-item" href="#roadmap">⌘ <span>Roadmap</span></a>
            <a class="nav-item" href="#tasks">✓ <span>Tasks</span></a>
            <a class="nav-item" href="#analytics">◒ <span>Analytics</span></a>
            <a class="nav-item" href="#motivation">❤ <span>Motivation</span></a>
        </nav>
        <div class="sidebar-bottom">
            <button id="themeToggle" class="ghost-btn">☾ Toggle Theme</button>
            <div class="user-mini"><div class="avatar"><?= strtoupper(substr($user['name'] ?? 'D',0,1)) ?></div><div><b><?= htmlspecialchars($user['name'] ?? 'Demo User') ?></b><small>Prototype account</small></div></div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <p class="eyebrow">PERSONAL EXECUTION SYSTEM</p>
                <h1 id="overview">Good to see you, <?= htmlspecialchars(explode(' ', $user['name'] ?? 'there')[0]) ?>.</h1>
                <p class="muted">Turn an ambitious dream into a practical path.</p>
            </div>
            <div class="top-actions">
                <div class="notification-wrap">
                    <button class="icon-btn" id="notificationBtn">🔔 <span class="badge"><?= count($notifications) ?></span></button>
                    <div class="notification-panel" id="notificationPanel">
                        <h3>Notifications</h3>
                        <?php if (!$notifications): ?><p class="muted">No notifications yet.</p><?php endif; ?>
                        <?php foreach ($notifications as $n): ?>
                            <div class="notification"><b><?= htmlspecialchars($n['title']) ?></b><p><?= htmlspecialchars($n['message']) ?></p><small><?= htmlspecialchars($n['created_at']) ?></small></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </header>

        <section class="hero-card" id="dream-engine">
            <div>
                <span class="pill">SMART DREAM ENGINE</span>
                <h2>What do you want to achieve?</h2>
                <p>Describe your dream naturally. The local processing engine will create goals, milestones, risks, backups and actionable tasks.</p>
                <form id="dreamForm" class="dream-form">
                    <textarea id="dreamPrompt" placeholder="Example: I want to become a Game Developer in 2 years" required></textarea>
                    <div class="form-row">
                        <input id="targetDate" type="date">
                        <button class="primary-btn" type="submit">✦ Map My Dream</button>
                    </div>
                    <div id="processStatus" class="form-status"></div>
                </form>
            </div>
            <div class="hero-orbit">
                <div class="orbit-center">DREAM</div>
                <div class="orbit-node n1">GOAL</div><div class="orbit-node n2">SKILL</div><div class="orbit-node n3">TASK</div>
            </div>
        </section>

        <section class="stats-grid">
            <div class="stat-card"><span>Dream Score</span><strong id="dreamScore">0</strong><small>XP-based execution score</small></div>
            <div class="stat-card"><span>Completion</span><strong id="completionRate">0%</strong><small>Completed roadmap nodes</small></div>
            <div class="stat-card"><span>Success Index</span><strong id="successProbability">0%</strong><small>Estimated from progress + risk</small></div>
            <div class="stat-card"><span>Streak</span><strong id="streakDays">0 🔥</strong><small>Consecutive active days</small></div>
        </section>

        <section class="content-grid">
            <div class="panel large-panel" id="roadmap">
                <div class="panel-header"><div><span class="eyebrow">NETWORK VIEW</span><h3>Dream Roadmap</h3></div><div class="legend"><span>● Goal</span><span>● Task</span><span>● Backup</span></div></div>
                <div id="cy" class="graph"></div>
                <div class="graph-tools">
                    <button class="secondary-btn" id="addNodeBtn">＋ Add Node</button>
                    <button class="secondary-btn" id="refreshGraphBtn">↻ Refresh</button>
                    <span class="muted">Click a node to edit status/title.</span>
                </div>
            </div>

            <div class="panel" id="tasks">
                <div class="panel-header"><div><span class="eyebrow">EXECUTION</span><h3>Today's Tasks</h3></div></div>
                <div id="todoList" class="todo-list"><p class="muted">Create a dream to generate tasks.</p></div>
                <form id="todoForm" class="inline-form">
                    <input id="newTodo" placeholder="Add a task manually..." required>
                    <button class="primary-btn" type="submit">Add</button>
                </form>
            </div>
        </section>

        <section class="content-grid">
            <div class="panel" id="analytics">
                <div class="panel-header"><div><span class="eyebrow">LIFE ANALYSIS</span><h3>Progress Trends</h3></div></div>
                <canvas id="progressChart" height="180"></canvas>
                <div class="risk-meter"><div><span>Risk Index</span><b id="riskIndex">0%</b></div><div class="meter"><i id="riskBar"></i></div></div>
            </div>
            <div class="panel" id="motivation">
                <div class="panel-header"><div><span class="eyebrow">MOTIVATION HUB</span><h3>Daily Reset</h3></div></div>
                <div class="quote-card">
                    <div class="quote-mark">“</div>
                    <p id="quoteText"><?= htmlspecialchars($quote['quote_text'] ?? 'Your future is built by the actions you repeat.') ?></p>
                    <small id="quoteAuthor">— <?= htmlspecialchars($quote['author'] ?? 'DreamMapper') ?></small>
                </div>
                <div class="media-placeholder">▶ Motivation media area<br><small>Add YouTube/audio records in <code>motivation_media</code>.</small></div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header"><div><span class="eyebrow">VIVA VIEW</span><h3>How DreamMapper Thinks</h3></div></div>
            <div class="logic-grid">
                <div><b>1. Input</b><p>Natural-language dream statement.</p></div>
                <div><b>2. Decompose</b><p>PHP rules identify timeframe, domain and milestones.</p></div>
                <div><b>3. Visualize</b><p>Nodes and relationships become an interactive graph.</p></div>
                <div><b>4. Execute</b><p>Micro-tasks create measurable progress and XP.</p></div>
                <div><b>5. Analyze</b><p>Completion, risk and consistency produce a score.</p></div>
            </div>
        </section>
    </main>
</div>
<script>
window.DREAMMAPPER = { userId: <?= (int)$userId ?> };
</script>
<script src="assets/js/graph.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
