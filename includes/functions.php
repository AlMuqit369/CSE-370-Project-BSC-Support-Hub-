<?php
require_once 'config.php';

// Redirect function
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user type
function isStudent() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
}

function isMentor() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'mentor';
}

// Sanitize input
function sanitize($input) {
    global $conn;
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    return $input;
}

// Display notification
function displayNotification($message, $type = 'success') {
    echo '<div class="alert alert-' . $type . '">' . $message . '</div>';
}

// Format date
function formatDate($date) {
    return date('F j, Y, g:i a', strtotime($date));
}

// Get user by ID
function getUserById($id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = 'SELECT * FROM users WHERE user_id = :id';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>