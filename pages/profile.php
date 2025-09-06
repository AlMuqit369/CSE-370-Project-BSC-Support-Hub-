<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if(!isLoggedIn()) {
    redirect('login.php');
}

// Initialize Database
$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];

// Handle profile update
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $bio = sanitize($_POST['bio']);
    $phone = sanitize($_POST['phone']);
    $department = sanitize($_POST['department']);
    $graduation_year = !empty($_POST['graduation_year']) ? (int)$_POST['graduation_year'] : null;
    $expertise = sanitize($_POST['expertise']);
    $linkedin_profile = sanitize($_POST['linkedin_profile']);
    
    // Update profile
    $query = 'UPDATE users SET 
              first_name = :first_name, 
              last_name = :last_name, 
              email = :email, 
              bio = :bio, 
              phone = :phone, 
              department = :department, 
              graduation_year = :graduation_year, 
              expertise = :expertise, 
              linkedin_profile = :linkedin_profile 
              WHERE user_id = :user_id';
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':bio', $bio);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':department', $department);
    $stmt->bindParam(':graduation_year', $graduation_year);
    $stmt->bindParam(':expertise', $expertise);
    $stmt->bindParam(':linkedin_profile', $linkedin_profile);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    // Update session variables
    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name'] = $last_name;
    
    redirect('profile.php?updated=1');
}

// Get user data
$query = 'SELECT * FROM users WHERE user_id = :user_id';
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h2>Your Profile</h2>
        <p>Manage your personal information and preferences.</p>
    </div>

    <?php if(isset($_GET['updated'])): ?>
        <div class="alert alert-success">Your profile has been updated successfully.</div>
    <?php endif; ?>

    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                <p><?php echo ucfirst($user['user_type']); ?></p>
            </div>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio:</label>
                    <textarea name="bio" id="bio" rows="3"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone:</label>
                        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="department">Department:</label>
                        <input type="text" name="department" id="department" value="<?php echo htmlspecialchars($user['department']); ?>">
                    </div>
                </div>
                
                <?php if(isStudent()): ?>
                    <div class="form-group">
                        <label for="graduation_year">Graduation Year:</label>
                        <input type="number" name="graduation_year" id="graduation_year" value="<?php echo htmlspecialchars($user['graduation_year']); ?>" min="2000" max="2030">
                    </div>
                <?php endif; ?>
                
                <?php if(isMentor()): ?>
                    <div class="form-group">
                        <label for="expertise">Expertise:</label>
                        <input type="text" name="expertise" id="expertise" value="<?php echo htmlspecialchars($user['expertise']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="linkedin_profile">LinkedIn Profile:</label>
                        <input type="text" name="linkedin_profile" id="linkedin_profile" value="<?php echo htmlspecialchars($user['linkedin_profile']); ?>">
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>