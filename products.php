<!-- products.php - Products Page -->
<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$products = $pdo->query("SELECT * FROM products")->fetchAll();
$categories = $pdo->query("SELECT name FROM categories")->fetchAll(PDO::FETCH_COLUMN);

if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $stock = $_POST['stock'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image = ''; // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = 'data:' . $_FILES['image']['type'] . ';base64,' . base64_encode(file_get_contents($_FILES['image']['tmp_name']));
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, category, stock, price, description, image) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $category, $stock, $price, $description, $image]);
    header('Location: products.php');
    exit;
}

// Similar for edit and delete
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriStock Pro - Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="products.css">
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
                 <BRK>   <ul>
                        <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                        <li><a href="products.php" class="nav-link active">Products</a></li>
                        <li><a href="categories.php" class="nav-link">Categories</a></li>
                        <li><a href="reports.php" class="nav-link">Reports</a></li>
                        <li><a href="settings.php" class="nav-link">Settings</a></li>
                    </ul></BRK>
                </nav>
                <div class="user-info">
                    <img src="<?php echo $currentUser['avatar']; ?>" alt="User">
                    <span><?php echo $_SESSION['user_name']; ?></span>
                    <a href="logout.php" class="btn btn-secondary" style="margin-left: 10px;">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="section-header">
                <h2>Agricultural Products &copy;</h2>
                <button class="btn btn-primary" onclick="openProductModal()">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </div>

            <!-- Search and Filters -->
            <div class="search-filters">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="search">Search Products</label>
                        <input type="text" id="search" placeholder="Search by product name...">
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Stock Status</label>
                        <select id="status">
                            <option value="">All Status</option>
                            <option value="high">In Stock</option>
                            <option value="low">Low Stock</option>
                        </select>
                    </div>
                    <button class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>

            <!-- Products Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Price</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><img src="<?php echo $product['image'] ?: 'https://via.placeholder.com/50?text=No+Image'; ?>" class="product-image" alt=""></td>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['category']; ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td>MK<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo number_format($product['stock'] * $product['price'], 2); ?></td>
                                <td><span class="status status-<?php echo $product['stock'] < 50 ? 'low' : 'high'; ?>"><?php echo $product['stock'] < 50 ? 'Low' : 'High'; ?></span></td>
                                <td>
                                    <div class="actions">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-edit"><i class="fas fa-edit"></i></a>
                                        <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content fade-in">
            <div class="modal-header">
                <h3>Add New Product</h3>
                <button class="close" onclick="closeProductModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Product Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="stock">Stock Quantity</label>
                            <input type="number" id="stock" name="stock" required min="0">
                        </div>
                        <div class="form-group">
                            <label for="price">Price (MK)</label>
                            <input type="number" id="price" name="price" step="0.01" required min="0">
                        </div>
                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" placeholder="Enter product description..."></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label>Product Image</label>
                            <div class="file-upload">
                                <input type="file" id="image" name="image" accept="image/*">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload product image</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeProductModal()">Cancel</button>
                    <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>

    

    <script src="pro script.js"></script>
</script>
</body>
</html>