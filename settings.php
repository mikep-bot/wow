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

// Decode JSON fields
$preferences = json_decode($currentUser['preferences'], true) ?? [
    'currency' => 'MWK',
    'language' => 'en',
    'lowStockThreshold' => 50,
    'dateFormat' => 'd/m/Y',
    'timezone' => 'Africa/Blantyre',
    'theme' => 'light'
];

$notifications = json_decode($currentUser['notifications'], true) ?? [
    'email' => true,
    'lowStock' => true,
    'newProduct' => false,
    'weeklyReports' => true,
    'monthlyReports' => false,
    'systemUpdates' => true
];

// Handle form submissions
if (isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? '';
    $location = $_POST['location'] ?? '';
    
    // Handle avatar upload
    $avatar = $currentUser['avatar'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['avatar']['type'], $allowed_types)) {
            if ($_FILES['avatar']['size'] <= 5 * 1024 * 1024) { // 5MB limit
                $avatar = 'data:' . $_FILES['avatar']['type'] . ';base64,' . base64_encode(file_get_contents($_FILES['avatar']['tmp_name']));
            } else {
                $_SESSION['error'] = 'Image size must be less than 5MB';
            }
        } else {
            $_SESSION['error'] = 'Invalid image format. Allowed: JPG, PNG, GIF, WebP';
        }
    }
    
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, avatar = ?, phone = ?, location = ? WHERE id = ?");
    $stmt->execute([$name, $email, $avatar, $phone, $location, $user_id]);
    $_SESSION['success'] = 'Profile updated successfully!';
    header('Location: settings.php');
    exit;
}

if (isset($_POST['update_preferences'])) {
    $currency = $_POST['currency'];
    $language = $_POST['language'];
    $lowStockThreshold = $_POST['low_stock_threshold'];
    $dateFormat = $_POST['date_format'];
    $timezone = $_POST['timezone'];
    $theme = $_POST['theme'];
    $itemsPerPage = $_POST['items_per_page'];
    
    $preferences = json_encode([
        'currency' => $currency,
        'language' => $language,
        'lowStockThreshold' => $lowStockThreshold,
        'dateFormat' => $dateFormat,
        'timezone' => $timezone,
        'theme' => $theme,
        'itemsPerPage' => $itemsPerPage
    ]);
    
    $stmt = $pdo->prepare("UPDATE users SET preferences = ? WHERE id = ?");
    $stmt->execute([$preferences, $user_id]);
    $_SESSION['success'] = 'Preferences updated successfully!';
    header('Location: settings.php');
    exit;
}

if (isset($_POST['update_notifications'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $low_stock_alerts = isset($_POST['low_stock_alerts']) ? 1 : 0;
    $new_product_alerts = isset($_POST['new_product_alerts']) ? 1 : 0;
    $weekly_reports = isset($_POST['weekly_reports']) ? 1 : 0;
    $monthly_reports = isset($_POST['monthly_reports']) ? 1 : 0;
    $system_updates = isset($_POST['system_updates']) ? 1 : 0;
    $price_change_alerts = isset($_POST['price_change_alerts']) ? 1 : 0;
    
    $notifications = json_encode([
        'email' => $email_notifications,
        'lowStock' => $low_stock_alerts,
        'newProduct' => $new_product_alerts,
        'weeklyReports' => $weekly_reports,
        'monthlyReports' => $monthly_reports,
        'systemUpdates' => $system_updates,
        'priceChange' => $price_change_alerts
    ]);
    
    $stmt = $pdo->prepare("UPDATE users SET notifications = ? WHERE id = ?");
    $stmt->execute([$notifications, $user_id]);
    $_SESSION['success'] = 'Notification settings updated successfully!';
    header('Location: settings.php');
    exit;
}

if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!password_verify($current_password, $currentUser['password'])) {
        $_SESSION['error'] = 'Current password is incorrect!';
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'New passwords do not match!';
    } elseif (strlen($new_password) < 8) {
        $_SESSION['error'] = 'New password must be at least 8 characters long!';
    } elseif (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $_SESSION['error'] = 'Password must contain uppercase, lowercase letters and numbers!';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);
        
        // Log password change
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, timestamp) VALUES (?, ?, ?, NOW())");
        $log_stmt->execute([$user_id, 'PASSWORD_CHANGE', 'Password changed successfully']);
        
        $_SESSION['success'] = 'Password changed successfully!';
    }
    header('Location: settings.php');
    exit;
}

if (isset($_POST['export_data'])) {
    // Export user data
    $userData = [
        'profile' => [
            'name' => $currentUser['name'],
            'email' => $currentUser['email'],
            'created_at' => $currentUser['created_at']
        ],
        'preferences' => $preferences,
        'notifications' => $notifications
    ];
    
    $filename = 'agristock_user_data_' . date('Y-m-d') . '.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($userData, JSON_PRETTY_PRINT);
    exit;
}

if (isset($_POST['delete_account'])) {
    $confirmation = $_POST['delete_confirmation'];
    if ($confirmation === 'DELETE') {
        // Archive user data before deletion
        $archiveData = [
            'user' => $currentUser,
            'deleted_at' => date('Y-m-d H:i:s')
        ];
        
        // Insert into deleted_users table
        $stmt = $pdo->prepare("INSERT INTO deleted_users (user_id, data, deleted_at) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, json_encode($archiveData), date('Y-m-d H:i:s')]);
        
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        session_destroy();
        header('Location: index.php?message=account_deleted');
        exit;
    } else {
        $_SESSION['error'] = 'Please type DELETE to confirm account deletion';
        header('Location: settings.php#security');
        exit;
    }
}

// Get login history with Malawian locations
$login_history = [
    ['ip' => '197.221.254.100', 'location' => 'Lilongwe, Malawi', 'device' => 'Chrome on Windows', 'time' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
    ['ip' => '197.221.254.100', 'location' => 'Blantyre, Malawi', 'device' => 'Chrome on Windows', 'time' => date('Y-m-d H:i:s', strtotime('-1 day'))],
    ['ip' => '41.86.146.101', 'location' => 'Mzuzu, Malawi', 'device' => 'Safari on iPhone', 'time' => date('Y-m-d H:i:s', strtotime('-3 days'))],
];

// Malawian timezones
$timezones = [
    'Africa/Blantyre', 'Africa/Maputo', 'Africa/Harare', 'Africa/Johannesburg',
    'Africa/Lusaka', 'Africa/Nairobi', 'Africa/Kigali', 'Africa/Dar_es_Salaam'
];

// Malawian languages
$languages = [
    'en' => 'English',
    'ny' => 'Chichewa',
    'tum' => 'Tumbuka',
    'yao' => 'Yao'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriStock Pro - Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="settings.css">
    <style>
        /* Malawian flag colors */
        :root {
            --malawi-green: #179647;
            --malawi-red: #E30B17;
            --malawi-black: #000000;
            --malawi-sun: #FDDA25;
        }
        
        .logo i {
            color: var(--malawi-green);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--malawi-green), #0d6b30);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--malawi-green), #0d6b30);
        }
        
        .feature-icon {
            background: linear-gradient(135deg, var(--malawi-green), #0d6b30);
            color: white;
        }
        
        .stat-item i {
            background: linear-gradient(135deg, var(--malawi-green), #0d6b30);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-seedling"></i>
                    <h1>AgriStock Pro </h1>
                </div>
                <nav>
                    <ul>
                        <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                        <li><a href="products.php" class="nav-link">Products</a></li>
                        <li><a href="categories.php" class="nav-link">Categories</a></li>
                        <li><a href="reports.php" class="nav-link">Reports</a></li>
                        <li><a href="settings.php" class="nav-link active">Settings</a></li>
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
                <h2>Settings</h2>
                <p class="section-subtitle">Manage your account and application preferences</p>
            </div>

            <!-- Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="settings-container">
                <!-- Sidebar Navigation -->
                <div class="settings-sidebar">
                    <nav class="settings-nav">
                        <a href="#profile" class="nav-item active" data-tab="profile">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                        <a href="#preferences" class="nav-item" data-tab="preferences">
                            <i class="fas fa-cog"></i>
                            <span>Preferences</span>
                        </a>
                        <a href="#notifications" class="nav-item" data-tab="notifications">
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                        </a>
                        <a href="#security" class="nav-item" data-tab="security">
                            <i class="fas fa-shield-alt"></i>
                            <span>Security</span>
                        </a>
                        <a href="#about" class="nav-item" data-tab="about">
                            <i class="fas fa-info-circle"></i>
                            <span>About</span>
                        </a>
                    </nav>
                </div>

                <!-- Settings Content -->
                <div class="settings-content">
                    <!-- Profile Tab -->
                    <div class="tab-content active" id="profile-tab">
                        <div class="tab-header">
                            <h3>Profile Settings</h3>
                            <p>Update your personal information and profile picture</p>
                        </div>
                        <form method="POST" enctype="multipart/form-data" class="settings-form">
                            <div class="form-section">
                                <div class="avatar-section">
                                    <div class="avatar-upload">
                                        <div class="avatar-preview">
                                            <img src="<?php echo $currentUser['avatar']; ?>" alt="Current Avatar" id="avatarPreview">
                                            <div class="avatar-overlay">
                                                <i class="fas fa-camera"></i>
                                                <span>Change Photo</span>
                                            </div>
                                        </div>
                                        <input type="file" id="avatar" name="avatar" accept="image/*" class="avatar-input">
                                        <div class="avatar-info">
                                            <p><i class="fas fa-info-circle"></i> Max size: 5MB. Allowed: JPG, PNG, GIF, WebP</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="name">
                                            <i class="fas fa-user"></i> Full Name
                                        </label>
                                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">
                                            <i class="fas fa-envelope"></i> Email Address
                                        </label>
                                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">
                                            <i class="fas fa-phone"></i> Phone Number (Malawi)
                                        </label>
                                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" placeholder="+265 XXX XXX XXX">
                                    </div>
                                    <div class="form-group">
                                        <label for="location">
                                            <i class="fas fa-map-marker-alt"></i> Location in Malawi
                                        </label>
                                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($currentUser['location'] ?? ''); ?>" placeholder="e.g., Lilongwe, Blantyre, Mzuzu">
                                    </div>
                                </div>
                                
                                <div class="profile-stats">
                                    <h4 class="section-title">Account Information</h4>
                                    <div class="stats-grid">
                                        <div class="stat-item">
                                            <i class="fas fa-calendar"></i>
                                            <div>
                                                <span class="stat-label">Member Since</span>
                                                <span class="stat-value"><?php echo date('F j, Y', strtotime($currentUser['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-user-check"></i>
                                            <div>
                                                <span class="stat-label">Account Status</span>
                                                <span class="stat-value status-active">Active</span>
                                            </div>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-clock"></i>
                                            <div>
                                                <span class="stat-label">Last Login</span>
                                                <span class="stat-value">Just now</span>
                                            </div>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-id-card"></i>
                                            <div>
                                                <span class="stat-label">User ID</span>
                                                <span class="stat-value">#<?php echo $currentUser['id']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Preferences Tab -->
                    <div class="tab-content" id="preferences-tab">
                        <div class="tab-header">
                            <h3>Application Preferences</h3>
                            <p>Customize your AgriStock Pro experience for Malawi</p>
                        </div>
                        <form method="POST" class="settings-form">
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-globe-africa"></i> Regional Settings
                                </h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="currency">Default Currency</label>
                                        <select id="currency" name="currency" required>
                                            <option value="MWK" <?php echo ($preferences['currency'] ?? 'MWK') === 'MWK' ? 'selected' : ''; ?>>Malawian Kwacha (MWK)</option>
                                            <option value="USD" <?php echo ($preferences['currency'] ?? 'MWK') === 'USD' ? 'selected' : ''; ?>>US Dollar ($)</option>
                                            <option value="ZAR" <?php echo ($preferences['currency'] ?? 'MWK') === 'ZAR' ? 'selected' : ''; ?>>South African Rand (R)</option>
                                            <option value="EUR" <?php echo ($preferences['currency'] ?? 'MWK') === 'EUR' ? 'selected' : ''; ?>>Euro (€)</option>
                                            <option value="GBP" <?php echo ($preferences['currency'] ?? 'MWK') === 'GBP' ? 'selected' : ''; ?>>British Pound (£)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="language">Language</label>
                                        <select id="language" name="language" required>
                                            <?php foreach ($languages as $code => $name): ?>
                                                <option value="<?php echo $code; ?>" <?php echo ($preferences['language'] ?? 'en') === $code ? 'selected' : ''; ?>>
                                                    <?php echo $name; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="timezone">Timezone (Malawi)</label>
                                        <select id="timezone" name="timezone" required>
                                            <?php foreach ($timezones as $tz): ?>
                                                <option value="<?php echo $tz; ?>" <?php echo ($preferences['timezone'] ?? 'Africa/Blantyre') === $tz ? 'selected' : ''; ?>>
                                                    <?php echo $tz; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="date_format">Date Format</label>
                                        <select id="date_format" name="date_format" required>
                                            <option value="d/m/Y" <?php echo ($preferences['dateFormat'] ?? 'd/m/Y') === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY (31/12/2023)</option>
                                            <option value="Y-m-d" <?php echo ($preferences['dateFormat'] ?? 'd/m/Y') === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD (2023-12-31)</option>
                                            <option value="d M, Y" <?php echo ($preferences['dateFormat'] ?? 'd/m/Y') === 'd M, Y' ? 'selected' : ''; ?>>31 Dec, 2023</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <h4 class="section-title">
                                    <i class="fas fa-sliders-h"></i> Display & Interface
                                </h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="theme">Theme</label>
                                        <select id="theme" name="theme" required>
                                            <option value="light" <?php echo ($preferences['theme'] ?? 'light') === 'light' ? 'selected' : ''; ?>>Light Mode</option>
                                            <option value="dark" <?php echo ($preferences['theme'] ?? 'light') === 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
                                            <option value="auto" <?php echo ($preferences['theme'] ?? 'light') === 'auto' ? 'selected' : ''; ?>>Auto (System)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="items_per_page">Items Per Page</label>
                                        <select id="items_per_page" name="items_per_page" required>
                                            <option value="10" <?php echo ($preferences['itemsPerPage'] ?? 10) == 10 ? 'selected' : ''; ?>>10 items</option>
                                            <option value="25" <?php echo ($preferences['itemsPerPage'] ?? 10) == 25 ? 'selected' : ''; ?>>25 items</option>
                                            <option value="50" <?php echo ($preferences['itemsPerPage'] ?? 10) == 50 ? 'selected' : ''; ?>>50 items</option>
                                            <option value="100" <?php echo ($preferences['itemsPerPage'] ?? 10) == 100 ? 'selected' : ''; ?>>100 items</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="low_stock_threshold">Low Stock Threshold</label>
                                    <div class="range-container">
                                        <input type="range" id="low_stock_threshold" name="low_stock_threshold" 
                                               min="1" max="1000" step="1" 
                                               value="<?php echo $preferences['lowStockThreshold'] ?? 50; ?>"
                                               oninput="updateThresholdValue(this.value)">
                                        <div class="range-values">
                                            <span>1</span>
                                            <span class="threshold-value" id="thresholdValue"><?php echo $preferences['lowStockThreshold'] ?? 50; ?></span>
                                            <span>1000</span>
                                        </div>
                                        <div class="input-help">Products with stock below this number will be marked as low stock</div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="update_preferences" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Preferences
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Notifications Tab -->
                    <div class="tab-content" id="notifications-tab">
                        <div class="tab-header">
                            <h3>Notification Settings</h3>
                            <p>Choose how you want to be notified about important events</p>
                        </div>
                        <form method="POST" class="settings-form">
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-envelope"></i> Email Notifications
                                </h4>
                                
                                <div class="toggle-group">
                                    <div class="toggle-item">
                                        <div class="toggle-info">
                                            <h5>Email Notifications</h5>
                                            <p>Receive important updates via email</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="email_notifications" <?php echo ($notifications['email'] ?? true) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                
                                <h4 class="section-title">
                                    <i class="fas fa-exclamation-triangle"></i> Alert Settings
                                </h4>
                                
                                <div class="toggle-group">
                                    <div class="toggle-item">
                                        <div class="toggle-info">
                                            <h5>Low Stock Alerts</h5>
                                            <p>Get notified when products are running low</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="low_stock_alerts" <?php echo ($notifications['lowStock'] ?? true) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="toggle-item">
                                        <div class="toggle-info">
                                            <h5>New Product Alerts</h5>
                                            <p>Notifications when new products are added</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="new_product_alerts" <?php echo ($notifications['newProduct'] ?? false) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="toggle-item">
                                        <div class="toggle-info">
                                            <h5>Price Change Alerts</h5>
                                            <p>Get notified when product prices change</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="price_change_alerts" <?php echo ($notifications['priceChange'] ?? false) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                
                                <h4 class="section-title">
                                    <i class="fas fa-chart-line"></i> Report Settings
                                </h4>
                                
                                <div class="toggle-group">
                                    <div class="toggle-item">
                                        <div class="toggle-info">
                                            <h5>Weekly Reports</h5>
                                            <p>Receive weekly inventory summary reports</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="weekly_reports" <?php echo ($notifications['weeklyReports'] ?? true) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="toggle-item">
                                        <div class="toggle-info">
                                            <h5>Monthly Reports</h5>
                                            <p>Receive monthly comprehensive reports</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="monthly_reports" <?php echo ($notifications['monthlyReports'] ?? false) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                
                                <h4 class="section-title">
                                    <i class="fas fa-cogs"></i> System Updates
                                </h4>
                                
                                <div class="toggle-group">
                                    <div class="toggle-item">
                                        <div class="toggle-info">
                                            <h5>System Updates</h5>
                                            <p>Notifications about system updates and maintenance</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="system_updates" <?php echo ($notifications['systemUpdates'] ?? true) ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="notification-schedule">
                                    <h4 class="section-title">
                                        <i class="fas fa-clock"></i> Notification Schedule
                                    </h4>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="notification_time">Preferred Time</label>
                                            <input type="time" id="notification_time" name="notification_time" value="09:00">
                                        </div>
                                        <div class="form-group">
                                            <label for="notification_frequency">Frequency</label>
                                            <select id="notification_frequency" name="notification_frequency">
                                                <option value="daily">Daily</option>
                                                <option value="weekly">Weekly (Monday)</option>
                                                <option value="biweekly">Bi-weekly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="update_notifications" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Notification Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Security Tab -->
                    <div class="tab-content" id="security-tab">
                        <div class="tab-header">
                            <h3>Security Settings</h3>
                            <p>Manage your password and account security</p>
                        </div>
                        
                        <div class="security-sections">
                            <!-- Password Change -->
                            <form method="POST" class="settings-form">
                                <div class="form-section">
                                    <h4 class="section-title">
                                        <i class="fas fa-key"></i> Change Password
                                    </h4>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="current_password">Current Password</label>
                                            <input type="password" id="current_password" name="current_password" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="new_password">New Password</label>
                                            <input type="password" id="new_password" name="new_password" required minlength="8">
                                            <div class="password-strength">
                                                <div class="strength-bar"></div>
                                                <span class="strength-text">Password strength</span>
                                            </div>
                                            <div class="password-requirements">
                                                <p><i class="fas fa-info-circle"></i> Password must contain:</p>
                                                <ul>
                                                    <li id="req-length">At least 8 characters</li>
                                                    <li id="req-uppercase">One uppercase letter</li>
                                                    <li id="req-lowercase">One lowercase letter</li>
                                                    <li id="req-number">One number</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="confirm_password">Confirm New Password</label>
                                            <input type="password" id="confirm_password" name="confirm_password" required>
                                            <div class="password-match">
                                                <span id="match-text"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="fas fa-key"></i> Change Password
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Two-Factor Authentication -->
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-mobile-alt"></i> Two-Factor Authentication
                                </h4>
                                <div class="security-card">
                                    <div class="security-info">
                                        <i class="fas fa-shield-alt"></i>
                                        <div>
                                            <h5>Enhanced Security</h5>
                                            <p>Add an extra layer of security to your account</p>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-secondary" onclick="setup2FA()">
                                        <i class="fas fa-qrcode"></i> Setup 2FA
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Login History -->
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-history"></i> Login History
                                </h4>
                                <div class="login-history">
                                    <?php foreach ($login_history as $login): ?>
                                        <div class="login-item">
                                            <div class="login-info">
                                                <div class="login-details">
                                                    <h5><?php echo $login['device']; ?></h5>
                                                    <span class="login-ip"><i class="fas fa-map-marker-alt"></i> <?php echo $login['location']; ?></span>
                                                    <span class="login-time"><i class="far fa-clock"></i> <?php echo $login['time']; ?></span>
                                                </div>
                                                <div class="login-status">
                                                    <span class="status-badge status-success">Successful</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Account Security -->
                            <div class="form-section">
                                <h4 class="section-title">
                                    <i class="fas fa-user-shield"></i> Account Security
                                </h4>
                                <div class="security-actions">
                                    <form method="POST" class="export-form">
                                        <button type="submit" name="export_data" class="btn btn-secondary">
                                            <i class="fas fa-download"></i> Export My Data
                                        </button>
                                    </form>
                                    
                                    <button type="button" class="btn btn-secondary" onclick="showDeleteModal()">
                                        <i class="fas fa-user-slash"></i> Delete Account
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- About Tab -->
                    <div class="tab-content" id="about-tab">
                        <div class="tab-header">
                            <h3>About AgriStock Pro Malawi</h3>
                            <p>Learn more about your inventory management system</p>
                        </div>
                        
                        <div class="about-content">
                            <!-- App Info -->
                            <div class="app-info">
                                <div class="app-logo-large">
                                    <i class="fas fa-seedling"></i>
                                </div>
                                <div class="app-details">
                                    <h2>AgriStock Pro Malawi</h2>
                                    <p class="version">Version 2.1.0</p>
                                    <p class="description">
                                        Comprehensive agricultural inventory management system designed specifically 
                                        for Malawian farmers and agricultural businesses. Track products, manage stock 
                                        levels, and generate insightful reports tailored to the Malawian market.
                                    </p>
                                    <div class="app-meta">
                                        <span><i class="far fa-calendar"></i> Released: December 2023</span>
                                        <span><i class="fas fa-code-branch"></i> Build: 2023.12.01</span>
                                        <span><i class="fas fa-database"></i> Database: MySQL 8.0</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Features Grid -->
                            <div class="features-grid">
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <h4>Malawian Crop Management</h4>
                                    <p>Track maize, tobacco, tea, and other Malawian crops with real-time updates</p>
                                </div>
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <h4>Market Analytics</h4>
                                    <p>Generate reports with Malawian market insights and pricing trends</p>
                                </div>
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                    <h4>Local Market Alerts</h4>
                                    <p>Get notified about price changes and market conditions in Malawi</p>
                                </div>
                                <div class="feature-card">
                                    <div class="feature-icon">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <h4>Mobile-First Design</h4>
                                    <p>Access your inventory from any device, optimized for Malawian networks</p>
                                </div>
                            </div>
                            
                            <!-- System Information -->
                            <div class="system-info">
                                <h4>System Information</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">PHP Version</span>
                                        <span class="info-value"><?php echo phpversion(); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Server Software</span>
                                        <span class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Database</span>
                                        <span class="info-value">MySQL <?php echo $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Users</span>
                                        <span class="info-value">
                                            <?php 
                                            $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                                            echo $userCount;
                                            ?>
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Total Products</span>
                                        <span class="info-value">
                                            <?php 
                                            $productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                                            echo $productCount;
                                            ?>
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Malawi Time</span>
                                        <span class="info-value"><?php echo date('H:i', strtotime('+2 hours')); ?> CAT</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Support & Resources -->
                            <div class="support-section">
                                <h4>Support & Resources</h4>
                                <div class="support-links">
                                    <a href="#" class="support-link">
                                        <i class="fas fa-book"></i>
                                        <span>User Guide (English/Chichewa)</span>
                                    </a>
                                    <a href="#" class="support-link">
                                        <i class="fas fa-video"></i>
                                        <span>Malawian Farming Tutorials</span>
                                    </a>
                                    <a href="#" class="support-link">
                                        <i class="fas fa-envelope"></i>
                                        <span>Malawi Support Team</span>
                                    </a>
                                    <a href="#" class="support-link">
                                        <i class="fas fa-shield-alt"></i>
                                        <span>Privacy Policy</span>
                                    </a>
                                    <a href="#" class="support-link">
                                        <i class="fas fa-file-contract"></i>
                                        <span>Terms of Service</span>
                                    </a>
                                    <a href="#" class="support-link">
                                        <i class="fas fa-phone"></i>
                                        <span>Call: +265 888 123 456</span>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Credits -->
                            <div class="credits-section">
                                <h4>Local Partners & Acknowledgments</h4>
                                <div class="credits-grid">
                                    <div class="credit-item">
                                        <i class="fas fa-hands-helping"></i>
                                        <span>Ministry of Agriculture</span>
                                    </div>
                                    <div class="credit-item">
                                        <i class="fas fa-university"></i>
                                        <span>Lilongwe University</span>
                                    </div>
                                    <div class="credit-item">
                                        <i class="fas fa-leaf"></i>
                                        <span>Malawian Farmers Union</span>
                                    </div>
                                    <div class="credit-item">
                                        <i class="fas fa-tractor"></i>
                                        <span>Agricultural Development</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Account Modal -->
    <div id="deleteAccountModal" class="modal">
        <div class="modal-content fade-in" style="max-width: 500px;">
            <div class="modal-header" style="background: #e53e3e;">
                <h3>Delete Account</h3>
                <button class="close" onclick="closeDeleteAccountModal()">&times;</button>
            </div>
            <form method="POST" onsubmit="return confirmAccountDeletion()">
                <div class="modal-body">
                    <div class="delete-warning">
                        <div class="warning-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="warning-content">
                            <h4>Permanent Account Deletion</h4>
                            <p>This action will permanently delete your account and all associated data. This cannot be undone.</p>
                            <div class="warning-details">
                                <div class="warning-item">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span>All your data will be permanently deleted</span>
                                </div>
                                <div class="warning-item">
                                    <i class="fas fa-chart-line"></i>
                                    <span>All reports and analytics will be lost</span>
                                </div>
                                <div class="warning-item">
                                    <i class="fas fa-user-slash"></i>
                                    <span>You will lose access to AgriStock Pro</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="delete_confirmation">
                                    Type <strong>DELETE</strong> to confirm
                                </label>
                                <input type="text" id="delete_confirmation" name="delete_confirmation" 
                                       placeholder="Type DELETE here" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteAccountModal()">Cancel</button>
                    <button type="submit" name="delete_account" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Permanently Delete Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    

    <script srs="sett script,js"></script>
</body>
</html>