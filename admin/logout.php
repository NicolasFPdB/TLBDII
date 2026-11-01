<?php require_once __DIR__ . '/../includes/session.php';
session_boot();
session_destroy_full();
header('Location: login.html');
exit;
