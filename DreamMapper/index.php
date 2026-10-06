<?php
// DreamMapper entry page. For a classroom prototype we use a demo user.
session_start();
$_SESSION['user_id'] = 1;
header('Location: dashboard.php');
exit;
?>
