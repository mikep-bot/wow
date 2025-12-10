

<!-- index.php - Authentication Page -->
<?php
session_start();
include 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$login_error = $signup_error = '';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: dashboard.php');
        exit;
    } else {
        $login_error = 'Invalid email or password';
    }
}

if (isset($_POST['signup'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $signup_error = 'Passwords do not match';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, avatar, preferences, notifications) VALUES (?, ?, ?, ?, ?, ?)");
            $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=4caf50&color=fff';
            $preferences = json_encode(['currency' => 'USD', 'language' => 'en', 'lowStockThreshold' => 50]);
            $notifications = json_encode(['email' => true, 'lowStock' => true, 'newProduct' => false]);
            $stmt->execute([$name, $email, $hashed_password, $avatar, $preferences, $notifications]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            header('Location: dashboard.php');
            exit;
        } catch (PDOException $e) {
            $signup_error = 'Email already exists';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriStock Pro - Login/Signup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Include the style from the provided code -->
     <link rel="stylesheet" href="index.css">
    <style>
        /* Paste the entire style block from the user's provided code here */
    </style>
</head>
<body>
    <div id="auth-page" class="page active">
        <div class="container">
            <div class="auth-container">
                <div class="auth-form">
                    <h2>AgriStock Pro</h2>
                    <div class="auth-tabs">
                        <div class="auth-tab active" id="login-tab">Login</div>
                        <div class="auth-tab" id="signup-tab">Sign Up</div>
                    </div>
                  
                    <div class="auth-content active" id="login-content">
                        <form method="POST">
                            <input type="hidden" name="login" value="1">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                                <div class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" required>
                                <div class="error-message"></div>
                            </div>
                            <?php if ($login_error): ?>
                                <div class="error-message" style="text-align: center; margin-bottom: 15px;"><?php echo $login_error; ?></div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                        </form>
                    </div>
                  
                    <div class="auth-content" id="signup-content">
                        <form method="POST">
                            <input type="hidden" name="signup" value="1">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" required>
                                <div class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                                <div class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" required minlength="6">
                                <div class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                                <div class="error-message"></div>
                            </div>
                            <?php if ($signup_error): ?>
                                <div class="error-message" style="text-align: center; margin-bottom: 15px;"><?php echo $signup_error; ?></div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Sign Up</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

<script src="index script.js"></script>




<!-- HTML form for edit -->

<!-- Similar for delete_product.php, etc. -->