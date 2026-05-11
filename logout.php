<?php
// logout.php
require_once __DIR__ . '/php/config.php';
session_destroy();
header('Location: index.html');
exit;
