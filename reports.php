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

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$category_filter = $_GET['category'] ?? 'all';

// Build WHERE clause for filters
$where_conditions = [];
$params = [];

if ($category_filter !== 'all') {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(' AND ', $where_conditions);
}

// Get report data
$products = $pdo->query("SELECT * FROM products $where_clause")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

// Calculate statistics
$total_products = count($products);
$total_stock = array_sum(array_column($products, 'stock'));
$total_value = array_sum(array_map(fn($p) => $p['stock'] * $p['price'], $products));
$low_stock_count = count(array_filter($products, fn($p) => $p['stock'] < 50));
$out_of_stock_count = count(array_filter($products, fn($p) => $p['stock'] == 0));

// Category-wise statistics
$category_stats = [];
foreach ($categories as $category) {
    $cat_products = array_filter($products, fn($p) => $p['category'] === $category['name']);
    $cat_count = count($cat_products);
    $cat_stock = array_sum(array_column($cat_products, 'stock'));
    $cat_value = array_sum(array_map(fn($p) => $p['stock'] * $p['price'], $cat_products));
    
    if ($cat_count > 0) {
        $category_stats[] = [
            'name' => $category['name'],
            'color' => $category['color'],
            'product_count' => $cat_count,
            'total_stock' => $cat_stock,
            'total_value' => $cat_value,
            'percentage' => round(($cat_count / $total_products) * 100, 1)
        ];
    }
}

// Stock level distribution
$stock_levels = [
    'out_of_stock' => $out_of_stock_count,
    'low_stock' => $low_stock_count,
    'in_stock' => $total_products - $low_stock_count - $out_of_stock_count
];

// Top products by value
usort($products, fn($a, $b) => ($b['stock'] * $b['price']) <=> ($a['stock'] * $a['price']));
$top_products = array_slice($products, 0, 5);

// Low stock products
$low_stock_products = array_filter($products, fn($p) => $p['stock'] < 50 && $p['stock'] > 0);
usort($low_stock_products, fn($a, $b) => $a['stock'] <=> $b['stock']);

// Monthly trends (simulated data for demo)
$monthly_data = [
    ['month' => 'Jan', 'products' => 45, 'value' => 12500],
    ['month' => 'Feb', 'products' => 52, 'value' => 14200],
    ['month' => 'Mar', 'products' => 48, 'value' => 13800],
    ['month' => 'Apr', 'products' => 55, 'value' => 15200],
    ['month' => 'May', 'products' => 60, 'value' => 16800],
    ['month' => 'Jun', 'products' => 58, 'value' => 16200],
    ['month' => 'Jul', 'products' => 70, 'value' => 17500],
    ['month' => 'Aug', 'products' => 65, 'value' => 18200],
    ['month' => 'Sep', 'products' => 68, 'value' => 19800],
    ['month' => 'Oct', 'products' => 54, 'value' => 20200],
    ['month' => 'Nov', 'products' => 69, 'value' => 21800],
    ['month' => 'Dev', 'products' => 58, 'value' => 22200],
    
    
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriStock Pro- reports </title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="reports.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-tractor"></i>
                    <h1>AgriStock Pro</h1>
                </div>
                <nav>
                    <ul>
                        <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                        <li><a href="products.php" class="nav-link">Products</a></li>
                        <li><a href="categories.php" class="nav-link">Categories</a></li>
                        <li><a href="reports.php" class="nav-link active">Reports</a></li>
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
            <div class="section-header">
                <h2>Inventory Reports & Analytics &copy;</h2>
                <div class="report-actions">
                    <button class="btn btn-secondary" onclick="printReport()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                    <button class="btn btn-primary" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                </div>
            </div>

            <!-- Report Filters -->
            <div class="report-filters">
                <form method="GET" class="filter-form">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                <option value="all">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['name']; ?>" <?php echo $category_filter === $category['name'] ? 'selected' : ''; ?>>
                                        <?php echo $category['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Key Metrics -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon primary">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <div class="metric-info">
                        <h3>Total Products</h3>
                        <div class="metric-value"><?php echo $total_products; ?></div>
                        <div class="metric-change positive">
                            <i class="fas fa-arrow-up"></i> 12% from last month
                        </div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon success">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="metric-info">
                        <h3>Total Stock</h3>
                        <div class="metric-value"><?php echo number_format($total_stock); ?></div>
                        <div class="metric-change positive">
                            <i class="fas fa-arrow-up"></i> 8% from last month
                        </div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-card">
                        <div class="metric-icon warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="metric-info">
                            <h3>Low Stock Items</h3>
                            <div class="metric-value"><?php echo $low_stock_count; ?></div>
                            <div class="metric-change negative">
                                <i class="fas fa-arrow-up"></i> Needs attention
                            </div>
                        </div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-card">
                        <div class="metric-card">
                            <div class="metric-icon info">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="metric-info">
                                <h3>Total Value</h3>
                                <div class="metric-value">MK<?php echo number_format($total_value, 2); ?></div>
                                <div class="metric-change positive">
                                    <i class="fas fa-arrow-up"></i> 15% from last month
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-grid">
                <!-- Category Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Products by Category</h3>
                        <span class="chart-subtitle">Category-wise distribution</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>

                <!-- Stock Levels -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Stock Level Distribution</h3>
                        <span class="chart-subtitle">Current inventory status</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="stockLevelChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Trends -->
                <div class="chart-card full-width">
                    <div class="chart-header">
                        <h3>Monthly Inventory Trends</h3>
                        <span class="chart-subtitle">Last 12 months performance</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Detailed Reports -->
            <div class="reports-grid">
                <!-- Top Products by Value -->
                <div class="report-card">
                    <div class="report-header">
                        <h3>Top Products by Inventory Value</h3>
                        <span class="report-count">Top 5</span>
                    </div>
                    <div class="report-content">
                        <div class="product-ranking">
                            <?php foreach ($top_products as $index => $product): ?>
                                <div class="rank-item">
                                    <div class="rank-number"><?php echo $index + 1; ?></div>
                                    <img src="<?php echo $product['image'] ?: 'https://via.placeholder.com/40?text=No+Image'; ?>" 
                                         alt="<?php echo $product['name']; ?>" class="product-thumb">
                                    <div class="product-info">
                                        <h4><?php echo $product['name']; ?></h4>
                                        <span class="product-category"><?php echo $product['category']; ?></span>
                                    </div>
                                    <div class="product-value">
                                        mk<?php echo number_format($product['stock'] * $product['price'], 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="report-card">
                    <div class="report-header">
                        <h3>Low Stock Alerts</h3>
                        <span class="report-count alert"><?php echo count($low_stock_products); ?> items</span>
                    </div>
                    <div class="report-content">
                        <?php if (!empty($low_stock_products)): ?>
                            <div class="alert-list">
                                <?php foreach ($low_stock_products as $product): ?>
                                    <div class="alert-item">
                                        <div class="alert-icon">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <div class="alert-info">
                                            <h4><?php echo $product['name']; ?></h4>
                                            <span class="alert-detail">
                                                Only <?php echo $product['stock']; ?> units left
                                            </span>
                                        </div>
                                        <div class="alert-stock">
                                            <div class="stock-bar">
                                                <div class="stock-fill" style="width: <?php echo min(($product['stock'] / 50) * 100, 100); ?>%"></div>
                                            </div>
                                            <span class="stock-text"><?php echo $product['stock']; ?>/50</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-alert">
                                <i class="fas fa-check-circle"></i>
                                <p>All products are well stocked</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Category Performance -->
                <div class="report-card full-width">
                    <div class="report-header">
                        <h3>Category Performance</h3>
                        <span class="report-count"><?php echo count($category_stats); ?> categories</span>
                    </div>
                    <div class="report-content">
                        <div class="performance-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Products</th>
                                        <th>Total Stock</th>
                                        <th>Total Value</th>
                                        <th>Percentage</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category_stats as $stat): ?>
                                        <tr>
                                            <td>
                                                <div class="category-with-color">
                                                    <span class="color-dot" style="background: <?php echo $stat['color']; ?>"></span>
                                                    <?php echo $stat['name']; ?>
                                                </div>
                                            </td>
                                            <td><?php echo $stat['product_count']; ?></td>
                                            <td><?php echo number_format($stat['total_stock']); ?></td>
                                            <td>MK<?php echo number_format($stat['total_value'], 2); ?></td>
                                            <td>
                                                <div class="percentage-bar">
                                                    <div class="percentage-fill" style="width: <?php echo $stat['percentage']; ?>%"></div>
                                                    <span class="percentage-text"><?php echo $stat['percentage']; ?>%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $stat['product_count'] > 10 ? 'status-good' : 'status-average'; ?>">
                                                    <?php echo $stat['product_count'] > 10 ? 'Good' : 'Average'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    

    
       <script> // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Category Distribution Chart
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            const categoryChart = new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($category_stats, 'name')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($category_stats, 'product_count')); ?>,
                        backgroundColor: <?php echo json_encode(array_column($category_stats, 'color')); ?>,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });

            // Stock Level Chart
            const stockCtx = document.getElementById('stockLevelChart').getContext('2d');
            const stockChart = new Chart(stockCtx, {
                type: 'pie',
                data: {
                    labels: ['Out of Stock', 'Low Stock', 'In Stock'],
                    datasets: [{
                        data: [<?php echo $stock_levels['out_of_stock']; ?>, 
                               <?php echo $stock_levels['low_stock']; ?>, 
                               <?php echo $stock_levels['in_stock']; ?>],
                        backgroundColor: ['#e53e3e', '#ed8936', '#48bb78'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });

            // Trend Chart
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            const trendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($monthly_data, 'month')); ?>,
                    datasets: [
                        {
                            label: 'Number of Products',
                            data: <?php echo json_encode(array_column($monthly_data, 'products')); ?>,
                            borderColor: '#4caf50',
                            backgroundColor: 'rgba(76, 175, 80, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Inventory Value (mk)',
                            data: <?php echo json_encode(array_column($monthly_data, 'value')); ?>,
                            borderColor: '#2196f3',
                            backgroundColor: 'rgba(33, 150, 243, 0.1)',
                            tension: 0.4,
                            fill: true,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Number of Products'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Inventory Value (MK)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        });

        // Report Actions
        function printReport() {
            window.print();
        }

        function exportToPDF() {
            alert('PDF export functionality would be implemented here with a library like jsPDF');
            // In a real implementation, you would use jsPDF to generate and download PDF
        }

        // Auto-refresh charts on window resize
        window.addEventListener('resize', function() {
            // Charts automatically handle resize due to responsive: true
        });</script>
    
</body>
</html>