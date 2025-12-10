<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch();

// Handle category operations
if (isset($_POST['add_category'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $color = $_POST['color'];
    
    $stmt = $pdo->prepare("INSERT INTO categories (name, description, color) VALUES (?, ?, ?)");
    $stmt->execute([$name, $description, $color]);
    header('Location: categories.php');
    exit;
}

if (isset($_POST['edit_category'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $color = $_POST['color'];
    
    $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, color = ? WHERE id = ?");
    $stmt->execute([$name, $description, $color, $id]);
    header('Location: categories.php');
    exit;
}

if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    
    // Check if category has products
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = (SELECT name FROM categories WHERE id = ?)");
    $stmt->execute([$id]);
    $product_count = $stmt->fetchColumn();
    
    if ($product_count == 0) {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['message'] = 'Category deleted successfully';
    } else {
        $_SESSION['error'] = 'Cannot delete category with existing products';
    }
    header('Location: categories.php');
    exit;
}

// Load categories
$categories = $pdo->query("SELECT c.*, 
                           (SELECT COUNT(*) FROM products WHERE category = c.name) as product_count
                           FROM categories c")->fetchAll();

// Get category statistics
$total_categories = count($categories);
$total_products_in_categories = array_sum(array_column($categories, 'product_count'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriStock Pro - Categories</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="categories.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    
                    <h1>AgriStock Pro</h1>
                </div>
                <nav>
                    <ul>
                        <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                        <li><a href="products.php" class="nav-link">Products</a></li>
                        <li><a href="categories.php" class="nav-link active">Categories</a></li>
                        <li><a href="reports.php" class="nav-link">Reports</a></li>
                        <li><a href="settings.php" class="nav-link">Settings</a></li>
                    </ul>
                </nav>
                <div class="user-info">
                    <img src="<?php echo $currentUser['avatar']; ?>" alt="User">
                    <span><?php echo $currentUser['name']; ?></span>
                    <a href="logout.php" class="btn btn-secondary" style="margin-left: 10px;">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="section-header">
                <h2>Product Categories &copy;</h2>
                <button class="btn btn-primary" onclick="openCategoryModal()">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </div>

            <!-- Category Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Categories</h3>
                        <div class="stat-value"><?php echo $total_categories; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Products</h3>
                        <div class="stat-value"><?php echo $total_products_in_categories; ?></div>
                    </div>
                </div>
            </div>

            <!-- Categories Grid -->
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <div class="category-card" style="border-left: 4px solid <?php echo $category['color']; ?>">
                        <div class="category-header">
                            <div class="category-color" style="background: <?php echo $category['color']; ?>"></div>
                            <h3 class="category-name"><?php echo $category['name']; ?></h3>
                            <div class="category-actions">
                                <button class="btn-icon btn-edit" onclick="openEditModal(<?php echo $category['id']; ?>, '<?php echo $category['name']; ?>', '<?php echo $category['description']; ?>', '<?php echo $category['color']; ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="categories.php?delete_id=<?php echo $category['id']; ?>" class="btn-icon btn-delete" onclick="return confirm('Are you sure you want to delete this category?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                        <p class="category-description"><?php echo $category['description'] ?: 'No description provided'; ?></p>
                        <div class="category-footer">
                            <span class="product-count">
                                <i class="fas fa-box"></i>
                                <?php echo $category['product_count']; ?> products
                            </span>
                            <span class="category-id">ID: <?php echo $category['id']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($categories)): ?>
                    <div class="empty-state">
                        <i class="fas fa-tags"></i>
                        <h3>No Categories Found</h3>
                        <p>Get started by creating your first product category.</p>
                        <button class="btn btn-primary" onclick="openCategoryModal()">
                            <i class="fas fa-plus"></i> Create Category
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Add Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content fade-in">
            <div class="modal-header">
                <h3>Add New Category</h3>
                <button class="close" onclick="closeCategoryModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Category Name</label>
                        <input type="text" id="name" name="name" required placeholder="Enter category name">
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Enter category description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="color">Category Color</label>
                        <div class="color-picker">
                            <?php
                            $defaultColors = [
                                '#4caf50', '#02132250', '#8e910fff', '#6d0e07ff',
                                '#0a0a0a', '#c2d2d480', '#8bc34a', '#080224ff'
                                
                            ];
                            foreach ($defaultColors as $color): ?>
                                <label class="color-option">
                                    <input type="radio" name="color" value="<?php echo $color; ?>" <?php echo $color === '#4caf50' ? 'checked' : ''; ?>>
                                    <span class="color-dot" style="background: <?php echo $color; ?>"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Cancel</button>
                    <button type="submit" name="add_category" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="modal">
        <div class="modal-content fade-in">
            <div class="modal-header">
                <h3>Edit Category</h3>
                <button class="close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">Category Name</label>
                        <input type="text" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_color">Category Color</label>
                        <div class="color-picker">
                            <?php foreach ($defaultColors as $color): ?>
                                <label class="color-option">
                                    <input type="radio" name="color" value="<?php echo $color; ?>">
                                    <span class="color-dot" style="background: <?php echo $color; ?>"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="edit_category" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>

    
</body>
</html>
<script src="categories script.js"></script>
