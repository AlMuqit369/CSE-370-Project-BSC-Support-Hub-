<?php
require_once 'config.php';
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><i class="fas fa-graduation-cap"></i> <?php echo APP_NAME; ?></h1>
            </div>
            <nav>
                <ul>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <!-- For logged-in users, show Dashboard instead of Home -->
                        <li><a href="<?php echo APP_URL; ?>pages/dashboard.php" class="active">Dashboard</a></li>
                        <li><a href="<?php echo APP_URL; ?>pages/resources.php">Resources</a></li>
                        <li><a href="<?php echo APP_URL; ?>pages/mentorship.php">Mentorship</a></li>
                        <li><a href="<?php echo APP_URL; ?>pages/jobs.php">Jobs</a></li>
                        <li><a href="<?php echo APP_URL; ?>pages/announcements.php">Announcements</a></li>
                        <li><a href="<?php echo APP_URL; ?>pages/profile.php">Profile</a></li>
                        <li><a href="<?php echo APP_URL; ?>pages/logout.php">Logout</a></li>
                    <?php else: ?>
                        <!-- For non-logged-in users, show Home -->
                        <li><a href="<?php echo APP_URL; ?>" class="active">Home</a></li>
                        <li><a href="<?php echo APP_URL; ?>pages/login.php">Login</a></li>
                        <li><a href="<?php echo APP_URL; ?>pages/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main>