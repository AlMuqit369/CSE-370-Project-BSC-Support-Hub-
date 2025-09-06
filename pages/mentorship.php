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
$user_type = $_SESSION['user_type'];

// Handle mentorship actions
if(isset($_GET['action']) && isset($_GET['request_id']) && isMentor()) {
    $action = sanitize($_GET['action']);
    $request_id = (int)$_GET['request_id'];
    
    // Update mentorship request status
    $status = ($action == 'accept') ? 'accepted' : 'rejected';
    $query = 'UPDATE mentorship_requests SET status = :status WHERE request_id = :request_id AND mentor_id = :mentor_id';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':request_id', $request_id);
    $stmt->bindParam(':mentor_id', $user_id);
    $stmt->execute();
    
    // Create notification for student
    $query = 'SELECT student_id FROM mentorship_requests WHERE request_id = :request_id';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':request_id', $request_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $student_id = $result['student_id'];
    
    $title = ($action == 'accept') ? 'Mentorship Request Accepted' : 'Mentorship Request Rejected';
    $message = ($action == 'accept') ? 'Your mentorship request has been accepted.' : 'Your mentorship request has been rejected.';
    
    $query = 'INSERT INTO notifications (user_id, type, title, message, related_id) 
              VALUES (:user_id, "mentorship", :title, :message, :related_id)';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $student_id);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':related_id', $request_id);
    $stmt->execute();
    
    redirect('mentorship.php');
}

// Handle new mentorship request
if($_SERVER['REQUEST_METHOD'] == 'POST' && isStudent()) {
    $mentor_id = (int)$_POST['mentor_id'];
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    // Insert mentorship request
    $query = 'INSERT INTO mentorship_requests (student_id, mentor_id, subject, message) 
              VALUES (:student_id, :mentor_id, :subject, :message)';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_id', $user_id);
    $stmt->bindParam(':mentor_id', $mentor_id);
    $stmt->bindParam(':subject', $subject);
    $stmt->bindParam(':message', $message);
    $stmt->execute();
    
    // Create notification for mentor
    $title = 'New Mentorship Request';
    $message = 'You have received a new mentorship request.';
    
    $query = 'INSERT INTO notifications (user_id, type, title, message, related_id) 
              VALUES (:user_id, "mentorship", :title, :message, :related_id)';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $mentor_id);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':related_id', $request_id);
    $stmt->execute();
    
    redirect('mentorship.php?requested=1');
}

// Get mentors for students
$mentors = [];
if(isStudent()) {
    $query = 'SELECT * FROM users WHERE user_type = "mentor" AND account_status = "active"';
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $mentors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get mentorship requests
if(isStudent()) {
    $query = 'SELECT mr.*, u.first_name, u.last_name 
              FROM mentorship_requests mr 
              JOIN users u ON mr.mentor_id = u.user_id 
              WHERE mr.student_id = :student_id 
              ORDER BY mr.created_at DESC';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_id', $user_id);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $query = 'SELECT mr.*, u.first_name, u.last_name 
              FROM mentorship_requests mr 
              JOIN users u ON mr.student_id = u.user_id 
              WHERE mr.mentor_id = :mentor_id 
              ORDER BY mr.created_at DESC';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':mentor_id', $user_id);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h2>Mentor Connect</h2>
        <p><?php echo isStudent() ? 'Connect with experienced mentors for guidance and support.' : 'Manage mentorship requests from students.'; ?></p>
    </div>

    <?php if(isset($_GET['requested'])): ?>
        <div class="alert alert-success">Your mentorship request has been sent successfully.</div>
    <?php endif; ?>

    <?php if(isStudent()): ?>
        <!-- New Request Form -->
        <div class="section">
            <h3>Request Mentorship</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="mentor_id">Select Mentor:</label>
                    <select name="mentor_id" id="mentor_id" required>
                        <option value="">-- Select a Mentor --</option>
                        <?php foreach($mentors as $mentor): ?>
                            <option value="<?php echo $mentor['user_id']; ?>">
                                <?php echo htmlspecialchars($mentor['first_name'] . ' ' . $mentor['last_name']); ?>
                                <?php if($mentor['expertise']): ?>
                                    - <?php echo htmlspecialchars($mentor['expertise']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" name="subject" id="subject" required>
                </div>
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea name="message" id="message" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Send Request</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Mentorship Requests -->
    <div class="section">
        <h3><?php echo isStudent() ? 'Your Mentorship Requests' : 'Mentorship Requests'; ?></h3>
        <?php if(empty($requests)): ?>
            <p><?php echo isStudent() ? 'You have not made any mentorship requests yet.' : 'You have no mentorship requests at the moment.'; ?></p>
        <?php else: ?>
            <div class="request-list">
                <?php foreach($requests as $request): ?>
                    <div class="request-item">
                        <div class="request-header">
                            <h4>
                                <?php if(isStudent()): ?>
                                    <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($request['subject']); ?>
                                <?php endif; ?>
                            </h4>
                            <span class="status status-<?php echo $request['status']; ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                        </div>
                        <div class="request-content">
                            <?php if(isStudent()): ?>
                                <p><strong>Subject:</strong> <?php echo htmlspecialchars($request['subject']); ?></p>
                            <?php endif; ?>
                            <p><strong>Message:</strong> <?php echo htmlspecialchars($request['message']); ?></p>
                            <p><strong>Date:</strong> <?php echo formatDate($request['created_at']); ?></p>
                        </div>
                        <?php if(isMentor() && $request['status'] == 'pending'): ?>
                            <div class="request-actions">
                                <a href="?action=accept&request_id=<?php echo $request['request_id']; ?>" class="btn btn-success">Accept</a>
                                <a href="?action=reject&request_id=<?php echo $request['request_id']; ?>" class="btn btn-danger">Reject</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>