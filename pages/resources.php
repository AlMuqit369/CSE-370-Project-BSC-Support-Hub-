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

// Handle new resource posting
if($_SERVER['REQUEST_METHOD'] == 'POST' && isMentor()) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $category_id = (int)$_POST['category_id'];
    $resource_type = sanitize($_POST['resource_type']);
    $link = sanitize($_POST['link']);
    
    // Insert new resource
    $query = 'INSERT INTO resources (title, description, category_id, resource_type, link, created_by, is_public) 
              VALUES (:title, :description, :category_id, :resource_type, :link, :created_by, 1)';
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':resource_type', $resource_type);
    $stmt->bindParam(':link', $link);
    $stmt->bindParam(':created_by', $_SESSION['user_id']);
    
    if($stmt->execute()) {
        redirect('resources.php?posted=1');
    } else {
        // Debug: Show error if insertion fails
        $error_info = $stmt->errorInfo();
        redirect('resources.php?error=insert_failed');
    }
}

// Get categories
$query = 'SELECT * FROM categories';
$stmt = $conn->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get resources with filtering
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build the base query
$query = 'SELECT r.*, c.name as category_name, u.first_name, u.last_name 
          FROM resources r 
          JOIN categories c ON r.category_id = c.category_id 
          LEFT JOIN users u ON r.created_by = u.user_id 
          WHERE 1=1'; // Using 1=1 as a base condition

// Add filtering conditions
if($category_id > 0) {
    $query .= ' AND r.category_id = :category_id';
}
if(!empty($search)) {
    $query .= ' AND (r.title LIKE :search OR r.description LIKE :search)';
}

$query .= ' ORDER BY r.created_at DESC';

$stmt = $conn->prepare($query);

// Bind parameters if needed
if($category_id > 0) {
    $stmt->bindParam(':category_id', $category_id);
}
if(!empty($search)) {
    $search_param = '%' . $search . '%';
    $stmt->bindParam(':search', $search_param);
}

$stmt->execute();
$resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Check if resources are being retrieved
if(empty($resources) && !empty($search)) {
    // Try without search to see if resources exist
    $query_no_search = 'SELECT r.*, c.name as category_name, u.first_name, u.last_name 
                        FROM resources r 
                        JOIN categories c ON r.category_id = c.category_id 
                        LEFT JOIN users u ON r.created_by = u.user_id 
                        WHERE 1=1
                        ORDER BY r.created_at DESC';
    $stmt_no_search = $conn->prepare($query_no_search);
    $stmt_no_search->execute();
    $all_resources = $stmt_no_search->fetchAll(PDO::FETCH_ASSOC);
    
    if(!empty($all_resources)) {
        // Resources exist but search didn't find any
        $search_error = true;
    }
}

require_once '../includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h2>Resource Navigator</h2>
        <p><?php echo isMentor() ? 'Post and manage educational resources for students.' : 'Explore our collection of academic resources, courses, and materials.'; ?></p>
    </div>
    
    <?php if(isset($_GET['posted'])): ?>
        <div class="alert alert-success">Your resource has been posted successfully.</div>
    <?php endif; ?>
    
    <?php if(isset($_GET['error']) && $_GET['error'] == 'insert_failed'): ?>
        <div class="alert alert-danger">Failed to post your resource. Please try again.</div>
    <?php endif; ?>
    
    <?php if(isMentor()): ?>
        <!-- New Resource Form -->
        <div class="section">
            <h3>Post a New Resource</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Resource Title:</label>
                    <input type="text" name="title" id="title" required>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea name="description" id="description" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category:</label>
                        <select name="category_id" id="category_id" required>
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="resource_type">Resource Type:</label>
                        <select name="resource_type" id="resource_type" required>
                            <option value="document">Document</option>
                            <option value="video">Video</option>
                            <option value="link">Link</option>
                            <option value="course">Course</option>
                            <option value="tool">Tool</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="link">Resource URL:</label>
                    <input type="text" name="link" id="link" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Post Resource</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
    
    <!-- Resource Filters -->
    <div class="resource-filters">
        <form method="GET" action="">
            <div class="filter-group">
                <label for="category">Category:</label>
                <select name="category" id="category">
                    <option value="0">All Categories</option>
                    <?php foreach($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>" <?php if($category_id == $category['category_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="search">Search:</label>
                <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="resources.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
    
    <!-- Debug Information (Remove in production) -->
    <div class="alert alert-info">
        <strong>Debug Info:</strong> Found <?php echo count($resources); ?> resources. 
        <?php if(isset($search_error)): ?>
        Search didn't return results, but <?php echo count($all_resources); ?> resources exist in database.
        <?php endif; ?>
    </div>
    
    <!-- Resource Grid -->
    <div class="resource-grid">
        <?php if(empty($resources)): ?>
            <p>No resources found matching your criteria.</p>
        <?php else: ?>
            <?php foreach($resources as $resource): ?>
                <div class="resource-card">
                    <div class="resource-type"><?php echo ucfirst($resource['resource_type']); ?></div>
                    <h3><?php echo htmlspecialchars($resource['title']); ?></h3>
                    <p class="resource-category"><?php echo htmlspecialchars($resource['category_name']); ?></p>
                    <p class="resource-description"><?php echo htmlspecialchars($resource['description']); ?></p>
                    <div class="resource-meta">
                        <span class="resource-views"><?php echo $resource['view_count']; ?> views</span>
                        <span class="resource-author">
                            <?php if($resource['created_by']): ?>
                                by <?php echo htmlspecialchars($resource['first_name'] . ' ' . $resource['last_name']); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <a href="<?php echo htmlspecialchars($resource['link']); ?>" class="btn btn-primary" target="_blank">View Resource</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php
require_once '../includes/footer.php';
?>