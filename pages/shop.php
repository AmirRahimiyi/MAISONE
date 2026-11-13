
<?php

require_once '../config/database.php';

// Category filter
$category = $_GET['category'] ?? 'all';

$allowedCategories = ['all', 'women', 'men'];

if (!in_array($category, $allowedCategories, true)) {
    $category = 'all';
}

// Fetch products
if ($category === 'all') {

    $stmt = $pdo->query("
        SELECT *
        FROM products
        WHERE is_active = 1
        ORDER BY id DESC
    ");

} else {

    $stmt = $pdo->prepare("
        SELECT *
        FROM products
        WHERE is_active = 1
        AND category = :category
        ORDER BY id DESC
    ");

    $stmt->execute([
        'category' => $category
    ]);
}

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';

?>

<main class="shop-page">

    <!-- Shop Heading -->
    <section class="shop-heading">

        <span class="eyebrow">THE MAISONÉ COLLECTION</span>

        <h1>
            <?php
            if ($category === 'women') {
                echo 'Women';
            } elseif ($category === 'men') {
                echo 'Men';
            } else {
                echo 'All Products';
            }
            ?>
        </h1>

        <p>Discover pieces designed to last beyond seasons.</p>

    </section>


    <!-- Shop Content -->
    <section class="shop-content">

        <!-- Toolbar -->
        <div class="shop-toolbar">

            <span><?= count($products) ?> Products</span>

            <div class="shop-filters">

                <a
                    href="shop.php?category=all"
                    class="<?= $category === 'all' ? 'active' : '' ?>"
                >
                    All
                </a>

                <a
                    href="shop.php?category=women"
                    class="<?= $category === 'women' ? 'active' : '' ?>"
                >
                    Women
                </a>

                <a
                    href="shop.php?category=men"
                    class="<?= $category === 'men' ? 'active' : '' ?>"
                >
                    Men
                </a>

            </div>

        </div>


        <!-- Product Grid -->
        <div class="product-grid">

            <?php if (!empty($products)): ?>

                <?php foreach ($products as $product): ?>

                    <?php

                    // Product image
                    $imageFile = trim($product['image'] ?? '');
                    $productImagePath = '';

                    if ($imageFile !== '') {

                        $imageFile = str_replace('\\', '/', $imageFile);

                        $imagePath = __DIR__ . '/../assets/images/' . $imageFile;

                        if (is_file($imagePath)) {

                            $productImagePath = '../assets/images/' .
                                implode(
                                    '/',
                                    array_map(
                                        'rawurlencode',
                                        explode('/', $imageFile)
                                    )
                                );
                        }
                    }

                    ?>

                    <!-- Product Card -->
                    <article class="product-card">

                        <a
                            href="product.php?id=<?= (int)$product['id'] ?>"
                            class="product-link"
                        >

                            <!-- Product Image -->
                            <div
                                class="product-image"
                                <?php if ($productImagePath !== ''): ?>
                                    style="background-image: url('<?= htmlspecialchars($productImagePath, ENT_QUOTES, 'UTF-8') ?>')"
                                <?php endif; ?>
                            >

                                <?php if ($productImagePath === ''): ?>

                                    <span class="image-placeholder">
                                        Image unavailable
                                    </span>

                                <?php endif; ?>

                                <span class="product-tag">
                                    MAISONÉ
                                </span>

                                <span class="product-view">
                                    VIEW PRODUCT
                                </span>

                            </div>


                            <!-- Product Information -->
                            <div class="product-info">

                                <div class="product-details">

                                    <h3>
                                        <?= htmlspecialchars(
                                            $product['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </h3>

                                    <p>
                                        <?= htmlspecialchars(
                                            ucfirst($product['category']),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        ·

                                        <?= htmlspecialchars(
                                            $product['color'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>

                                </div>


                                <!-- Price -->
                                <span class="product-price">
                                    €<?= number_format(
                                        (float)$product['price'],
                                        2
                                    ) ?>
                                </span>

                            </div>

                        </a>

                    </article>

                <?php endforeach; ?>

            <?php else: ?>

                <p class="no-products">
                    No products found.
                </p>

            <?php endif; ?>

        </div>

    </section>

</main>

<?php include '../includes/footer.php'; ?>
