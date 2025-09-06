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
$email = '';
$first_name = '';
$last_name = '';
$user_type = 'student';
$password = '';
$confirm_password = '';
$error = '';

// Process form when submitted
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $user_type = sanitize($_POST['user_type']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Initialize Database
    $db = new Database();
    $conn = $db->getConnection();

    // Validate inputs
    if(empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif($password != $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if username already exists
        $query = 'SELECT * FROM users WHERE username = :username';
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $error = 'Username already exists';
        } else {
            // Check if email already exists
            $query = 'SELECT * FROM users WHERE email = :email';
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $error = 'Email already exists';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $query = 'INSERT INTO users (username, password, email, first_name, last_name, user_type) 
                          VALUES (:username, :password, :email, :first_name, :last_name, :user_type)';
                $stmt = $conn->prepare($query);
                
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':user_type', $user_type);
                
                if($stmt->execute()) {
                    // Registration successful, redirect to login
                    redirect('login.php?registered=1');
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <h2>Create an Account</h2>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if(isset($_GET['registered'])): ?>
            <div class="alert alert-success">Registration successful! Please login.</div>
        <?php endif; ?>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
            </div>
            <div class="form-group">
                <label for="user_type">I am a</label>
                <select id="user_type" name="user_type" required>
                    <option value="student" <?php if($user_type == 'student') echo 'selected'; ?>>Student</option>
                    <option value="mentor" <?php if($user_type == 'mentor') echo 'selected'; ?>>Mentor</option>
                </select>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Register</button>
            </div>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>