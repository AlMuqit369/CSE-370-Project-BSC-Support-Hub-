<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Check if already logged in
if(isLoggedIn()) {
    redirect('dashboard.php');
}

// Initialize variables
$username = '';
$password = '';
$error = '';

// Process form when submitted
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    // Initialize Database
    $db = new Database();
    $conn = $db->getConnection();

    // Query to check user
    $query = 'SELECT * FROM users WHERE username = :username';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user && password_verify($password, $user['password'])) {
        // Create session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];

        // Get unread notifications count
        $query = 'SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0';
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user['user_id']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['unread_count'] = $result['count'];

        // Redirect to dashboard
        redirect('dashboard.php');
    } else {
        $error = 'Invalid username or password';
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <h2>Login to Your Account</h2>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </form>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>