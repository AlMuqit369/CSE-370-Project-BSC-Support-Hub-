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

// Handle new announcement
if($_SERVER['REQUEST_METHOD'] == 'POST' && isMentor()) {
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $category_id = (int)$_POST['category_id'];
    $priority = sanitize($_POST['priority']);
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    
    // Insert announcement
    $query = 'INSERT INTO announcements (title, content, category_id, posted_by, priority, is_pinned, expires_at) 
              VALUES (:title, :content, :category_id, :posted_by, :priority, :is_pinned, :expires_at)';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':posted_by', $user_id);
    $stmt->bindParam(':priority', $priority);
    $stmt->bindParam(':is_pinned', $is_pinned);
    $stmt->bindParam(':expires_at', $expires_at);
    $stmt->execute();
    
    // Get all users to notify
    $query = 'SELECT user_id FROM users WHERE user_type = "student"';
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create notifications for all students
    $title = 'New Announcement: ' . htmlspecialchars($title);
    $message = 'A new announcement has been posted.';
    
    foreach($students as $student) {
        $query = 'INSERT INTO notifications (user_id, type, title, message) 
                  VALUES (:user_id, "announcement", :title, :message)';
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $student['user_id']);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->execute();
    }
    
    redirect('announcements.php?posted=1');
}

// Get categories
$query = 'SELECT * FROM categories';
$stmt = $conn->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get announcements - FIXED QUERY
$query = 'SELECT a.*, c.name as category_name, u.first_name, u.last_name 
          FROM announcements a 
          JOIN categories c ON a.category_id = c.category_id 
          JOIN users u ON a.posted_by = u.user_id 
          WHERE (a.expires_at IS NULL OR a.expires_at > NOW())
          ORDER BY a.is_pinned DESC, a.priority DESC, a.created_at DESC'; // Changed posted_at to created_at
$stmt = $conn->prepare($query);
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h2>Announcement Board</h2>
        <p>Stay updated with the latest announcements and events.</p>
    </div>

    <?php if(isset($_GET['posted'])): ?>
        <div class="alert alert-success">Your announcement has been posted successfully.</div>
    <?php endif; ?>

    <?php if(isMentor()): ?>
        <!-- New Announcement Form -->
        <div class="section">
            <h3>Post a New Announcement</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" name="title" id="title" required>
                </div>
                <div class="form-group">
                    <label for="category_id">Category:</label>
                    <select name="category_id" id="category_id" required>
                        <?php foreach($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="priority">Priority:</label>
                        <select name="priority" id="priority" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="expires_at">Expires At:</label>
                        <input type="datetime-local" name="expires_at" id="expires_at">
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_pinned"> Pin this announcement
                    </label>
                </div>
                <div class="form-group">
                    <label for="content">Content:</label>
                    <textarea name="content" id="content" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Post Announcement</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Announcement List -->
    <div class="announcement-list">
        <?php if(empty($announcements)): ?>
            <p>No announcements at the moment.</p>
        <?php else: ?>
            <?php foreach($announcements as $announcement): ?>
                <div class="announcement-card <?php echo $announcement['is_pinned'] ? 'pinned' : ''; ?>">
                    <div class="announcement-header">
                        <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                        <div class="announcement-meta">
                            <span class="announcement-category"><?php echo htmlspecialchars($announcement['category_name']); ?></span>
                            <span class="priority priority-<?php echo $announcement['priority']; ?>">
                                <?php echo ucfirst($announcement['priority']); ?>
                            </span>
                            <?php if($announcement['is_pinned']): ?>
                                <span class="pinned-indicator"><i class="fas fa-thumbtack"></i> Pinned</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="announcement-content">
                        <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                    </div>
                    <div class="announcement-footer">
                        <span class="announcement-author">
                            Posted by <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?>
                        </span>
                        <span class="announcement-time">
                            <?php echo formatDate($announcement['created_at']); ?> <!-- Changed from posted_at to created_at -->
                        </span>
                        <?php if($announcement['expires_at']): ?>
                            <span class="announcement-expires">
                                Expires: <?php echo date('M j, Y, g:i a', strtotime($announcement['expires_at'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if(isMentor() && $announcement['posted_by'] == $user_id): ?>
                        <div class="announcement-actions">
                            <a href="#" class="btn btn-secondary">Edit</a>
                            <a href="#" class="btn btn-danger">Delete</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>