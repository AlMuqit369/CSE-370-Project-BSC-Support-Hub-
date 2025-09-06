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

// Handle job application
if(isset($_GET['apply']) && isStudent()) {
    $job_id = (int)$_GET['apply'];
    
    // Check if already applied
    $query = 'SELECT * FROM job_applications WHERE job_id = :job_id AND student_id = :student_id';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':job_id', $job_id);
    $stmt->bindParam(':student_id', $user_id);
    $stmt->execute();
    
    if($stmt->rowCount() == 0) {
        // Insert application
        $query = 'INSERT INTO job_applications (job_id, student_id) VALUES (:job_id, :student_id)';
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':job_id', $job_id);
        $stmt->bindParam(':student_id', $user_id);
        $stmt->execute();
        
        // Get job details for notification
        $query = 'SELECT * FROM jobs WHERE job_id = :job_id';
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':job_id', $job_id);
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Create notification
        $title = 'Job Application Submitted';
        $message = 'You have successfully applied for the position: ' . htmlspecialchars($job['title']);
        
        $query = 'INSERT INTO notifications (user_id, type, title, message, related_id) 
                  VALUES (:user_id, "job", :title, :message, :related_id)';
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':related_id', $job_id);
        $stmt->execute();
    }
    
    redirect('jobs.php?applied=1');
}

// Handle new job posting
if($_SERVER['REQUEST_METHOD'] == 'POST' && isMentor()) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $company = sanitize($_POST['company']);
    $location = sanitize($_POST['location']);
    $type = sanitize($_POST['type']);
    $mode = sanitize($_POST['mode']);
    $salary_min = isset($_POST['salary_min']) ? floatval($_POST['salary_min']) : null;
    $salary_max = isset($_POST['salary_max']) ? floatval($_POST['salary_max']) : null;
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    
    // Insert job
    $query = 'INSERT INTO jobs (title, description, company, location, type, mode, salary_min, salary_max, posted_by, deadline) 
              VALUES (:title, :description, :company, :location, :type, :mode, :salary_min, :salary_max, :posted_by, :deadline)';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':company', $company);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':mode', $mode);
    $stmt->bindParam(':salary_min', $salary_min);
    $stmt->bindParam(':salary_max', $salary_max);
    $stmt->bindParam(':posted_by', $user_id);
    $stmt->bindParam(':deadline', $deadline);
    $stmt->execute();
    
    redirect('jobs.php?posted=1');
}

// Get jobs with filtering
$type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$mode = isset($_GET['mode']) ? sanitize($_GET['mode']) : '';

$query = 'SELECT j.*, u.first_name, u.last_name 
          FROM jobs j 
          LEFT JOIN users u ON j.posted_by = u.user_id 
          WHERE j.is_active = 1';

if(!empty($type)) {
    $query .= ' AND j.type = :type';
}

if(!empty($mode)) {
    $query .= ' AND j.mode = :mode';
}

$query .= ' ORDER BY j.posted_at DESC';

$stmt = $conn->prepare($query);

if(!empty($type)) {
    $stmt->bindParam(':type', $type);
}

if(!empty($mode)) {
    $stmt->bindParam(':mode', $mode);
}

$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get student applications
$applications = [];
if(isStudent()) {
    $query = 'SELECT job_id FROM job_applications WHERE student_id = :student_id';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_id', $user_id);
    $stmt->execute();
    
    $app_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($app_results as $app) {
        $applications[] = $app['job_id'];
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h2>Job and Internship Tracker</h2>
        <p><?php echo isStudent() ? 'Discover and apply for job and internship opportunities.' : 'Post job and internship opportunities for students.'; ?></p>
    </div>

    <?php if(isset($_GET['applied'])): ?>
        <div class="alert alert-success">Your application has been submitted successfully.</div>
    <?php endif; ?>

    <?php if(isset($_GET['posted'])): ?>
        <div class="alert alert-success">Your job posting has been created successfully.</div>
    <?php endif; ?>

    <?php if(isMentor()): ?>
        <!-- New Job Form -->
        <div class="section">
            <h3>Post a New Job</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Job Title:</label>
                    <input type="text" name="title" id="title" required>
                </div>
                <div class="form-group">
                    <label for="company">Company:</label>
                    <input type="text" name="company" id="company" required>
                </div>
                <div class="form-group">
                    <label for="location">Location:</label>
                    <input type="text" name="location" id="location" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="type">Type:</label>
                        <select name="type" id="type" required>
                            <option value="full-time">Full-time</option>
                            <option value="part-time">Part-time</option>
                            <option value="internship">Internship</option>
                            <option value="contract">Contract</option>
                            <option value="volunteer">Volunteer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="mode">Mode:</label>
                        <select name="mode" id="mode" required>
                            <option value="on-site">On-site</option>
                            <option value="remote">Remote</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="salary_min">Salary Min:</label>
                        <input type="number" name="salary_min" id="salary_min" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="salary_max">Salary Max:</label>
                        <input type="number" name="salary_max" id="salary_max" step="0.01">
                    </div>
                </div>
                <div class="form-group">
                    <label for="deadline">Application Deadline:</label>
                    <input type="date" name="deadline" id="deadline">
                </div>
                <div class="form-group">
                    <label for="description">Job Description:</label>
                    <textarea name="description" id="description" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Post Job</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Job Filters -->
    <div class="job-filters">
        <form method="GET" action="">
            <div class="filter-group">
                <label for="type">Type:</label>
                <select name="type" id="type">
                    <option value="">All Types</option>
                    <option value="full-time" <?php if($type == 'full-time') echo 'selected'; ?>>Full-time</option>
                    <option value="part-time" <?php if($type == 'part-time') echo 'selected'; ?>>Part-time</option>
                    <option value="internship" <?php if($type == 'internship') echo 'selected'; ?>>Internship</option>
                    <option value="contract" <?php if($type == 'contract') echo 'selected'; ?>>Contract</option>
                    <option value="volunteer" <?php if($type == 'volunteer') echo 'selected'; ?>>Volunteer</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="mode">Mode:</label>
                <select name="mode" id="mode">
                    <option value="">All Modes</option>
                    <option value="on-site" <?php if($mode == 'on-site') echo 'selected'; ?>>On-site</option>
                    <option value="remote" <?php if($mode == 'remote') echo 'selected'; ?>>Remote</option>
                    <option value="hybrid" <?php if($mode == 'hybrid') echo 'selected'; ?>>Hybrid</option>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="jobs.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Job Listings -->
    <div class="job-list">
        <?php if(empty($jobs)): ?>
            <p>No job opportunities found matching your criteria.</p>
        <?php else: ?>
            <?php foreach($jobs as $job): ?>
                <div class="job-card">
                    <div class="job-header">
                        <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                        <div class="job-meta">
                            <span class="job-type"><?php echo ucfirst($job['type']); ?></span>
                            <span class="job-mode"><?php echo ucfirst($job['mode']); ?></span>
                        </div>
                    </div>
                    <div class="job-company">
                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($job['company']); ?>
                        <span class="job-location"><?php echo htmlspecialchars($job['location']); ?></span>
                    </div>
                    <div class="job-description">
                        <?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 200) . '...')); ?>
                    </div>
                    <div class="job-footer">
                        <div class="job-salary">
                            <?php if($job['salary_min'] && $job['salary_max']): ?>
                                $<?php echo number_format($job['salary_min'], 2); ?> - $<?php echo number_format($job['salary_max'], 2); ?>
                            <?php elseif($job['salary_min']): ?>
                                $<?php echo number_format($job['salary_min'], 2); ?>+
                            <?php endif; ?>
                        </div>
                        <div class="job-deadline">
                            <?php if($job['deadline']): ?>
                                <i class="fas fa-calendar-alt"></i> Deadline: <?php echo date('M j, Y', strtotime($job['deadline'])); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="job-actions">
                        <?php if(isStudent()): ?>
                            <?php if(in_array($job['job_id'], $applications)): ?>
                                <button class="btn btn-secondary" disabled>Applied</button>
                            <?php else: ?>
                                <a href="?apply=<?php echo $job['job_id']; ?>" class="btn btn-primary">Apply Now</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if(isMentor() && $job['posted_by'] == $user_id): ?>
                            <a href="#" class="btn btn-secondary">Edit</a>
                            <a href="#" class="btn btn-danger">Delete</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>