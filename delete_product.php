<?php
session_start();
include 'db_connect.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch();


    $product_id = $_GET['id'];


    // Check if product exists
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    if ($stmt->rowCount() == 0) {
        $_SESSION['error'] = 'Product not found.';
        header('Location: products.php');
        echo "product not founf";
        exit;
    }else{
        echo "product not found";
    }

    // Delete the product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $_SESSION['success'] = 'Product deleted successfully!';
    header('Location: products.php');
    exit;

?>