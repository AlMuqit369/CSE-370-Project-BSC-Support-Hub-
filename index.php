<?php
// Include required files
require_once 'includes/config.php';
require_once 'includes/functions.php';

require_once 'includes/header.php';
?>

<div class="container">
    <div class="hero">
        <h2>Welcome to BSC Support HUB</h2>
        <p>A platform designed specifically for BSC students to get centralized support in academics, career planning, networking, and mental well-being.</p>
        <?php if(!isLoggedIn()): ?>
            <div class="cta-buttons">
                <a href="pages/login.php" class="btn btn-primary">Login</a>
                <a href="pages/register.php" class="btn btn-secondary">Register</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="features">
        <div class="feature">
            <div class="feature-icon">
                <i class="fas fa-book"></i>
            </div>
            <h3>Smart Resource Navigator</h3>
            <p>Access a wide range of academic resources, courses, and study materials.</p>
        </div>
        <div class="feature">
            <div class="feature-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <h3>Mentor Connect</h3>
            <p>Connect with experienced mentors for guidance and support.</p>
        </div>
        <div class="feature">
            <div class="feature-icon">
                <i class="fas fa-briefcase"></i>
            </div>
            <h3>Job and Internship Tracker</h3>
            <p>Discover and apply for job and internship opportunities.</p>
        </div>
        <div class="feature">
            <div class="feature-icon">
                <i class="fas fa-bullhorn"></i>
            </div>
            <h3>Announcement Board</h3>
            <p>Stay updated with the latest announcements and events.</p>
        </div>
        <div class="feature">
            <div class="feature-icon">
                <i class="fas fa-bell"></i>
            </div>
            <h3>Notifications and Alerts</h3>
            <p>Get timely notifications about important updates and opportunities.</p>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>