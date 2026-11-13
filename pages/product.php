
<?php

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Get product ID
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId || $productId <= 0) {
    header('Location: shop.php');
    exit;
}

// Fetch product
$stmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id = :id
    AND is_active = 1
    LIMIT 1
");

$stmt->execute([
    'id' => $productId
]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Product not found
if (!$product) {
    http_response_code(404);
    exit('Product not found.');
}

// Resolve product image
$imageFile = trim($product['image'] ?? '');
$imagePath = '';

if ($imageFile !== '') {

    $imageFile = str_replace('\\', '/', $imageFile);

    $fullPath = __DIR__ . '/../assets/images/' . $imageFile;

    if (is_file($fullPath)) {

        $imagePath = '../assets/images/' .
            implode(
                '/',
                array_map(
                    'rawurlencode',
                    explode('/', $imageFile)
                )
            );
    }
}

// Product stock
$stock = max(0, (int)($product['stock'] ?? 0));

// Current cart quantity
$currentQuantity = (int)($_SESSION['cart'][$productId] ?? 0);

// Add product to cart
$cartMessage = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_to_cart'])
) {

    if ($stock <= 0) {

        $cartMessage = 'This product is currently out of stock.';

    } elseif ($currentQuantity >= $stock) {

        $cartMessage = 'You have reached the available stock limit.';

    } else {

        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $_SESSION['cart'][$productId] = $currentQuantity + 1;

        header('Location: cart.php');
        exit;
    }
}

include '../includes/header.php';

?>

<main class="product-detail">

    <!-- Product Image -->
    <div class="product-detail-image">

        <?php if ($imagePath !== ''): ?>

            <img
                src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>"
                alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
            >

        <?php else: ?>

            <div class="image-placeholder">
                Image unavailable
            </div>

        <?php endif; ?>

    </div>


    <!-- Product Information -->
    <div class="product-detail-info">

        <p class="product-category">
            <?= htmlspecialchars(
                ucfirst($product['category']),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
            Collection
        </p>


        <h1>
            <?= htmlspecialchars(
                $product['name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h1>


        <p class="product-price">
            €<?= number_format((float)$product['price'], 2) ?>
        </p>


        <!-- Description -->
        <div class="product-description">

            <?= nl2br(
                htmlspecialchars(
                    $product['description'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                )
            ) ?>

        </div>


        <!-- Color -->
        <p class="product-color">

            Color:

            <?= htmlspecialchars(
                $product['color'] ?? '—',
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </p>


        <!-- Stock -->
        <p class="product-stock">

            <?= $stock > 0 ? 'In Stock' : 'Out of Stock' ?>

        </p>


        <!-- Cart Message -->
        <?php if ($cartMessage !== ''): ?>

            <p class="cart-message">
                <?= htmlspecialchars(
                    $cartMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

        <?php endif; ?>


        <!-- Add To Cart -->
        <form method="POST">

            <button
                type="submit"
                name="add_to_cart"
                value="1"
                class="add-to-cart-btn"
                <?= $stock <= 0 ? 'disabled' : '' ?>
            >

                <?= $stock > 0 ? 'ADD TO BAG' : 'OUT OF STOCK' ?>

            </button>

        </form>


        <!-- Back To Shop -->
        <a href="shop.php" class="back-to-shop">
            ← Back to Collection
        </a>

    </div>

</main>

<?php include '../includes/footer.php'; ?>
