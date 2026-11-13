
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

$error = '';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId || $productId <= 0) {
    header('Location: products.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';
    $color = trim($_POST['color'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');

    if (
        $name === '' ||
        !in_array($category, ['women', 'men'], true) ||
        !is_numeric($price) ||
        (float)$price <= 0 ||
        filter_var($stock, FILTER_VALIDATE_INT) === false ||
        (int)$stock < 0
    ) {

        $error = 'Please enter valid product information.';

    } else {

        $imageName = $product['image'];
        $newImagePath = null;

        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            $file = $_FILES['image'];

            if ($file['error'] !== UPLOAD_ERR_OK) {

                $error = 'Image upload failed.';

            } elseif ($file['size'] > 5 * 1024 * 1024) {

                $error = 'Image must be smaller than 5MB.';

            } else {

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);

                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (
                    !isset($allowed[$mime]) ||
                    getimagesize($file['tmp_name']) === false
                ) {

                    $error = 'Only valid JPG, PNG and WEBP images are allowed.';

                } else {

                    $uploadDir = __DIR__ . '/../assets/images/products/';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $newImageName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];

                    $newImagePath = $uploadDir . $newImageName;

                    if (!move_uploaded_file($file['tmp_name'], $newImagePath)) {

                        $error = 'Could not upload the new image.';

                    } else {

                        $imageName = $newImageName;
                    }
                }
            }
        }

        if ($error === '') {

            try {

                $update = $pdo->prepare("
                    UPDATE products
                    SET
                        name = :name,
                        description = :description,
                        category = :category,
                        color = :color,
                        price = :price,
                        stock = :stock,
                        image = :image
                    WHERE id = :id
                ");

                $update->execute([
                    'name' => $name,
                    'description' => $description,
                    'category' => $category,
                    'color' => $color,
                    'price' => $price,
                    'stock' => $stock,
                    'image' => $imageName,
                    'id' => $productId
                ]);

                if ($newImagePath && !empty($product['image'])) {

                    $oldImagePath = __DIR__ . '/../assets/images/products/' . basename($product['image']);

                    if (is_file($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                header('Location: products.php');
                exit;

            } catch (PDOException $e) {

                if ($newImagePath && is_file($newImagePath)) {
                    unlink($newImagePath);
                }

                $error = 'Could not update the product.';
            }
        }
    }

    // Keep submitted values when validation fails.

    $product['name'] = $name;
    $product['description'] = $description;
    $product['category'] = $category;
    $product['color'] = $color;
    $product['price'] = $price;
    $product['stock'] = $stock;

    if (!empty($newImagePath) && is_file($newImagePath)) {
        $product['image'] = $imageName;
    }
}

include 'header.php';

?>

<section class="admin-add-product">

    <div class="admin-page-heading">

        <div>
            <span class="admin-eyebrow">INVENTORY MANAGEMENT</span>
            <h1>Edit Product</h1>
            <p>Update product information.</p>
        </div>

        <a href="products.php" class="admin-back-link">
            ← Back to Products
        </a>

    </div>

    <?php if ($error): ?>

        <div class="admin-error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <form
        method="POST"
        enctype="multipart/form-data"
        class="product-form"
    >

        <div class="product-form-grid">

            <div class="product-form-field">

                <label>PRODUCT NAME</label>

                <input
                    type="text"
                    name="name"
                    maxlength="150"
                    required
                    value="<?= htmlspecialchars($product['name']) ?>"
                >

            </div>

            <div class="product-form-field">

                <label>CATEGORY</label>

                <select name="category" required>

                    <option value="women" <?= $product['category'] === 'women' ? 'selected' : '' ?>>
                        Women
                    </option>

                    <option value="men" <?= $product['category'] === 'men' ? 'selected' : '' ?>>
                        Men
                    </option>

                </select>

            </div>

            <div class="product-form-field">

                <label>COLOR</label>

                <input
                    type="text"
                    name="color"
                    maxlength="50"
                    value="<?= htmlspecialchars($product['color'] ?? '') ?>"
                >

            </div>

            <div class="product-form-field">

                <label>PRICE (€)</label>

                <input
                    type="number"
                    name="price"
                    min="0.01"
                    step="0.01"
                    required
                    value="<?= htmlspecialchars((string)$product['price']) ?>"
                >

            </div>

            <div class="product-form-field">

                <label>STOCK</label>

                <input
                    type="number"
                    name="stock"
                    min="0"
                    step="1"
                    required
                    value="<?= htmlspecialchars((string)$product['stock']) ?>"
                >

            </div>

            <div class="product-form-field">

                <label>PRODUCT IMAGE</label>

                <div class="image-upload-area">

                    <div class="image-preview" id="imagePreview">

                        <?php if (!empty($product['image'])): ?>

                            <img
                                src="../assets/images/products/<?= htmlspecialchars(basename($product['image'])) ?>"
                                alt="Current Product Image"
                                id="previewImage"
                            >

                        <?php else: ?>

                            <span>IMAGE PREVIEW</span>

                        <?php endif; ?>

                    </div>

                    <input
                        type="file"
                        name="image"
                        id="productImage"
                        accept="image/jpeg,image/png,image/webp"
                    >

                    <small>
                        Leave empty to keep the current image.
                        JPG, PNG or WEBP · Maximum 5MB
                    </small>

                </div>

            </div>

            <div class="product-form-field full-width">

                <label>DESCRIPTION</label>

                <textarea
                    name="description"
                    rows="5"
                ><?= htmlspecialchars($product['description'] ?? '') ?></textarea>

            </div>

        </div>

        <div class="product-form-actions">

            <a href="products.php">
                CANCEL
            </a>

            <button type="submit">
                UPDATE PRODUCT
            </button>

        </div>

    </form>

</section>

<script>

const imageInput = document.getElementById('productImage');
const imagePreview = document.getElementById('imagePreview');

let currentImageUrl = null;

imageInput.addEventListener('change', function () {

    const file = this.files[0];

    if (!file) {
        return;
    }

    const allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    if (!allowedTypes.includes(file.type)) {

        alert('Only JPG, PNG and WEBP images are allowed.');

        this.value = '';
        return;
    }

    if (file.size > 5 * 1024 * 1024) {

        alert('Image must be smaller than 5MB.');

        this.value = '';
        return;
    }

    if (currentImageUrl) {
        URL.revokeObjectURL(currentImageUrl);
    }

    currentImageUrl = URL.createObjectURL(file);

    imagePreview.innerHTML = '';

    const img = document.createElement('img');

    img.src = currentImageUrl;
    img.alt = 'Product Preview';

    imagePreview.appendChild(img);

});

</script>

<?php include 'footer.php'; ?>
