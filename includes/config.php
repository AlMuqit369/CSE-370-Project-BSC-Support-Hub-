<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bsc_support_hub');

// Application settings
define('APP_NAME', 'BSC Support HUB');
define('APP_URL', 'http://localhost/bsc_support_hub/');
define('APP_ROOT', $_SERVER['DOCUMENT_ROOT'] . '/bsc_support_hub/');

// Session settings
session_start();

// Error reporting (for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>