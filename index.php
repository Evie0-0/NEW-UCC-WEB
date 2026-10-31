<?php
declare(strict_types=1);

// REMINDER: Remove before going live
session_start();

$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'superadmin';

session_write_close();

header('Location: public/index.php');
