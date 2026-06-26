<?php
require_once __DIR__ . '/config/session.php';
startAppSession();
session_destroy();
header('Location: login.php');
exit;
