<?php
require __DIR__ . '/../src/bootstrap.php';

// Logging out changes state, so it only works as a POST with a valid token.
if (!is_post()) redirect('index.php');
csrf_check();
$wasAdmin = !empty($_SESSION['admin_id']);
logout();
session_boot();
flash('success', 'You have been logged out.');
redirect($wasAdmin ? 'admin_login.php' : 'index.php');
