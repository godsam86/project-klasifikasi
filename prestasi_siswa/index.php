<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Location: ' . (!empty($_SESSION['pengguna']) ? 'dashboard.php' : 'login.php'));
exit;
