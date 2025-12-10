<!-- dashboard.php - Dashboard Page -->
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

$_SESSION['user_name'] = $currentUser['name'];

// Load products and categories
$products = $pdo->query("SELECT * FROM products")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

// Dashboard stats
$total_products = count($products);
$total_stock = array_sum(array_column($products, 'stock'));
$low_stock = count(array_filter($products, fn($p) => $p['stock'] < 50)); // Assuming threshold 50
$total_value = array_sum(array_map(fn($p) => $p['stock'] * $p['price'], $products));

// Recent products (last 5)
$recent_products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriStock Pro - Dashboard</title>

    
     <link rel="stylesheet" href="dashboard.css">
    <style>
        
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    
                    <h1>AgriStock Pro</h1>
                </div>
                <nav>
                    <ul>
                        <li><a href="dashboard.php" class="nav-link active">Dashboard</a></li>
                        <li><a href="products.php" class="nav-link">Products</a></li>
                        <li><a href="categories.php" class="nav-link">Categories</a></li>
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
            <h2>Dashboard &copy;</h2>
            <div class="dashboard">
                <div class="card">
                    <i class="fas fa-seedling"></i>
                    <h3>Total Products</h3>
                    <div class="value"><?php echo $total_products; ?></div>
                </div>
                <div class="card">
                    <i class="fas fa-boxes"></i>
                    <h3>Total Stock</h3>
                    <div class="value"><?php echo $total_stock; ?></div>
                </div>
                <div class="card">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Low Stock Items</h3>
                    <div class="value"><?php echo $low_stock; ?></div>
                </div>
                <div class="card">
                    <i class="fas fa-dollar-sign"></i>
                    <h3>Total Value</h3>
                    <div class="value">MK<?php echo number_format($total_value, 2); ?></div>
                </div>
            </div>
            <div class="section-header">
                <h2>Recent Products</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_products as $product): ?>
                            <tr>
                                <td><img src="<?php echo $product['image'] ?: 'https://via.placeholder.com/50'; ?>" class="product-image" alt=""></td>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['category']; ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td>MK<?php echo number_format($product['price'], 2); ?></td>
                                <td><span class="status status-<?php echo $product['stock'] < 50 ? 'low' : 'high'; ?>"><?php echo $product['stock'] < 50 ? 'Low' : 'High'; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>
