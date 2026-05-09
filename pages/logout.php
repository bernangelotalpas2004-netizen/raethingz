<?php
/**
 * logout.php — destroys the session and redirects to home
 */
require_once '../includes/auth.php';
logout();
header('Location: ../index.php');
exit;
