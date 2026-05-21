<?php
require_once __DIR__ . '/lib/auth.php';
require_login();
header('Location: terminal.php');
exit;
