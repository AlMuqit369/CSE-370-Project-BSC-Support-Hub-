<?php
// Include required files
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to home page
redirect('../index.php');
?>