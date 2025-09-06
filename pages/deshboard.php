<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if(!isLoggedIn()) {
    redirect('login.php');
}

// Get user type
$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

// Initialize Database
$db = new Database();
$conn = $db->getConnection();

// Get user data
$user = getUserById($user_id);

// Get recent announcements
$query = 'SELECT * FROM announcements ORDER BY posted_at DESC LIMIT 5';
$stmt = $conn->prepare($query);
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent jobs if student
$recent_jobs = [];
if(isStudent()) {
    $query = 'SELECT * FROM jobs WHERE is_active = 1 ORDER BY posted_at DESC LIMIT 5';
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $recent_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get mentorship requests if mentor
$mentorship_requests = [];
if(isMentor()) {
    $query = 'SELECT mr.*, u.first_name, u.last_name 
              FROM mentorship_requests mr 
              JOIN users u ON mr.student_id = u.user_id 
              WHERE mr.mentor_id = :mentor_id AND mr.status = "pending"
              ORDER BY mr.created_at DESC';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':mentor_id', $user_id);
    $stmt->execute();
    $mentorship_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
require_once '../includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <h2>Welcome, <?php echo $user['first_name'] . ' ' . $user['last_name']; ?>!</h2>
        <p>You are logged in as a <?php echo $user_type; ?>.</p>
    </div>

    <div class="dashboard-grid">
        <!-- Announcements Section -->
        <div class="dashboard-section">
            <h3>Recent Announcements</h3>
            <div class="announcement-list">
                <?php if(empty($announcements)): ?>
                    <p>No announcements yet.</p>
                <?php else: ?>
                    <?php foreach($announcements as $announcement): ?>
                        <div class="announcement-item">
                            <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                            <p><?php echo substr(htmlspecialchars($announcement['content']), 0, 100) . '...'; ?></p>
                            <span class="announcement-time"><?php echo formatDate($announcement['posted_at']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <a href="announcements.php" class="view-all">View All Announcements</a>
        </div>

        <!-- Student-specific sections -->
        <?php if(isStudent()): ?>
            <div class="dashboard-section">
                <h3>Recent Job Opportunities</h3>
                <div class="job-list">
                    <?php if(empty($recent_jobs)): ?>
                        <p>No job opportunities available at the moment.</p>
                    <?php else: ?>
                        <?php foreach($recent_jobs as $job): ?>
                            <div class="job-item">
                                <h4><?php echo htmlspecialchars($job['title']); ?></h4>
                                <p><?php echo htmlspecialchars($job['company']); ?> - <?php echo htmlspecialchars($job['location']); ?></p>
                                <span class="job-type"><?php echo ucfirst($job['type']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a href="jobs.php" class="view-all">View All Jobs</a>
            </div>
        <?php endif; ?>

        <!-- Mentor-specific sections -->
        <?php if(isMentor()): ?>
            <div class="dashboard-section">
                <h3>Pending Mentorship Requests</h3>
                <div class="request-list">
                    <?php if(empty($mentorship_requests)): ?>
                        <p>No pending mentorship requests.</p>
                    <?php else: ?>
                        <?php foreach($mentorship_requests as $request): ?>
                            <div class="request-item">
                                <h4><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></h4>
                                <p><?php echo htmlspecialchars($request['subject']); ?></p>
                                <div class="request-actions">
                                    <a href="mentorship.php?action=accept&request_id=<?php echo $request['request_id']; ?>" class="btn btn-success">Accept</a>
                                    <a href="mentorship.php?action=reject&request_id=<?php echo $request['request_id']; ?>" class="btn btn-danger">Reject</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a href="mentorship.php" class="view-all">View All Requests</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>