
<!-- edit_product.php example -->
<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $stock = $_POST['stock'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image = $product['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = 'data:' . $_FILES['image']['type'] . ';base64,' . base64_encode(file_get_contents($_FILES['image']['tmp_name']));
    }

    $stmt = $pdo->prepare("UPDATE products SET name = ?, category = ?, stock = ?, price = ?, description = ?, image = ? WHERE id = ?");
    $stmt->execute([$name, $category, $stock, $price, $description, $image, $id]);
    header('Location: products.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <style>
        /* Global Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
    color: #333;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Form Container */
.form-container {
    background: linear-gradient(180deg, #ffffff, #f9fafb);
    border: 1px solid #e2e8f0;
    padding: 30px;
    border-radius: 12px;
    max-width: 600px;
    width: 100%;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    animation: slideUp 0.6s ease-out;
}

/* Animations */
@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-container h2 {
    text-align: center;
    margin-bottom: 25px;
    font-size: 1.8rem;
    color: #2a4365;
}

/* Form Groups */
.form-group {
    margin-bottom: 20px;
    position: relative;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 0.95rem;
    color: #2d3748;
}

input[type="text"],
input[type="number"],
input[type="file"],
textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e0;
    border-radius: 8px;
    font-size: 1rem;
    background: #f8fafc;
    transition: all 0.3s ease;
}

input:focus,
textarea:focus {
    border-color: #4caf50;
    box-shadow: 0 0 8px rgba(76, 175, 80, 0.3);
    outline: none;
    background: #fff;
}

textarea {
    resize: vertical;
    min-height: 100px;
}

/* Product Image Preview */
.product-image {
    text-align: center;
    margin-bottom: 20px;
}

.product-image img {
    max-width: 180px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.4s ease, box-shadow 0.4s ease;
}

.product-image img:hover {
    transform: scale(1.08);
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
}

/* Form Actions */
.form-actions {
    text-align: center;
    margin-top: 25px;
}

button {
    background: linear-gradient(135deg, #4caf50, #45a049);
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: all 0.3s ease;
}

button:hover {
    background: #3e8e41;
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.3);
}

button:disabled {
    background: #cbd5e0;
    cursor: not-allowed;
    box-shadow: none;
}

/* Responsive Design */
@media (max-width: 600px) {
    .form-container {
        padding: 20px;
    }
    .form-container h2 {
        font-size: 1.5rem;
    }
    button {
        width: 100%;
    }
    .form-group input,
    .form-group textarea {
        font-size: 0.9rem;
    }
}

    </style>
</head>
<body>
    <div class="form-container">
        <h2>Edit Product</h2>
        <form action="edit_product.php?id=<?php echo $product['id']; ?>" method="post" enctype="multipart/form-data">
            
            <div class="product-image">
                <?php if (!empty($product['image'])): ?>
                    <img src="<?php echo $product['image']; ?>" alt="Product Image">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($product['category']); ?>" required>
            </div>

            <div class="form-group">
                <label for="stock">Stock</label>
                <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
            </div>

            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="image">Upload New Image</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>

            <div class="form-actions">
                <button type="submit" name="update_product">Update Product</button>
            </div>
        </form>
    </div>
</body>
</html>
