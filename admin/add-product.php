
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

$name = '';
$description = '';
$category = '';
$color = '';
$price = '';
$stock = '';

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

    } elseif (
        !isset($_FILES['image']) ||
        $_FILES['image']['error'] !== UPLOAD_ERR_OK
    ) {

        $error = 'Please select a product image.';

    } else {

        $file = $_FILES['image'];

        if ($file['size'] > 5 * 1024 * 1024) {

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

                $imageName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];

                $destination = $uploadDir . $imageName;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {

                    $error = 'Image upload failed.';

                } else {

                    try {

                        $stmt = $pdo->prepare("
                            INSERT INTO products
                            (
                                name,
                                description,
                                category,
                                color,
                                price,
                                image,
                                stock,
                                is_active
                            )
                            VALUES
                            (
                                :name,
                                :description,
                                :category,
                                :color,
                                :price,
                                :image,
                                :stock,
                                1
                            )
                        ");

                        $stmt->execute([
                            'name' => $name,
                            'description' => $description,
                            'category' => $category,
                            'color' => $color,
                            'price' => $price,
                            'image' => $imageName,
                            'stock' => $stock
                        ]);

                        header('Location: products.php');
                        exit;

                    } catch (PDOException $e) {

                        if (is_file($destination)) {
                            unlink($destination);
                        }

                        $error = 'Could not save the product.';
                    }
                }
            }
        }
    }
}

include 'header.php';

?>

<section class="admin-add-product">

    <div class="admin-page-heading">

        <div>
            <span class="admin-eyebrow">INVENTORY MANAGEMENT</span>
            <h1>Add Product</h1>
            <p>Create a new MAISONÉ product.</p>
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

            <!-- PRODUCT NAME -->

            <div class="product-form-field">

                <label>PRODUCT NAME</label>

                <input
                    type="text"
                    name="name"
                    maxlength="150"
                    required
                    value="<?= htmlspecialchars($name) ?>"
                    placeholder="Enter product name"
                >

            </div>

            <!-- CATEGORY -->

            <div class="product-form-field">

                <label>CATEGORY</label>

                <select name="category" required>

                    <option value="">Select category</option>

                    <option
                        value="women"
                        <?= $category === 'women' ? 'selected' : '' ?>
                    >
                        Women
                    </option>

                    <option
                        value="men"
                        <?= $category === 'men' ? 'selected' : '' ?>
                    >
                        Men
                    </option>

                </select>

            </div>

            <!-- COLOR -->

            <div class="product-form-field">

                <label>COLOR</label>

                <input
                    type="text"
                    name="color"
                    maxlength="50"
                    value="<?= htmlspecialchars($color) ?>"
                    placeholder="e.g. Black"
                >

            </div>

            <!-- PRICE -->

            <div class="product-form-field">

                <label>PRICE (€)</label>

                <input
                    type="number"
                    name="price"
                    min="0.01"
                    step="0.01"
                    required
                    value="<?= htmlspecialchars($price) ?>"
                    placeholder="0.00"
                >

            </div>

            <!-- STOCK -->

            <div class="product-form-field">

                <label>STOCK</label>

                <input
                    type="number"
                    name="stock"
                    min="0"
                    step="1"
                    required
                    value="<?= htmlspecialchars($stock) ?>"
                    placeholder="Available quantity"
                >

            </div>

            <!-- IMAGE UPLOAD -->

            <div class="product-form-field">

                <label>PRODUCT IMAGE</label>

                <div class="image-upload-area">

                    <div class="image-preview" id="imagePreview">

                        <span>IMAGE PREVIEW</span>

                    </div>

                    <input
                        type="file"
                        name="image"
                        id="productImage"
                        accept="image/jpeg,image/png,image/webp"
                        required
                    >

                    <small>
                        JPG, PNG or WEBP · Maximum 5MB
                    </small>

                </div>

            </div>

            <!-- DESCRIPTION -->

            <div class="product-form-field full-width">

                <label>DESCRIPTION</label>

                <textarea
                    name="description"
                    rows="5"
                    placeholder="Write a description for this product..."
                ><?= htmlspecialchars($description) ?></textarea>

            </div>

        </div>

        <!-- FORM ACTIONS -->

        <div class="product-form-actions">

            <a href="products.php">
                CANCEL
            </a>

            <button type="submit">
                SAVE PRODUCT
            </button>

        </div>

    </form>

</section>

<!-- IMAGE PREVIEW SCRIPT -->

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

    const image = document.createElement('img');

    image.src = currentImageUrl;
    image.alt = 'Product Preview';

    imagePreview.appendChild(image);

});

</script>

<?php include 'footer.php'; ?>
