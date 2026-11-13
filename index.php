
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

// Get featured products
$stmt = $pdo->query("
    SELECT id, name, description, category, color, price, image
    FROM products
    WHERE is_active = 1
    ORDER BY created_at DESC
    LIMIT 4
");

$featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get category images
$stmt = $pdo->query("
    SELECT image, category
    FROM products
    WHERE is_active = 1
    AND category IN ('women', 'men')
    ORDER BY created_at DESC
");

$categoryImages = [
    'women' => '',
    'men' => ''
];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
    if (
        empty($categoryImages[$item['category']]) &&
        !empty($item['image'])
    ) {
        $categoryImages[$item['category']] = basename($item['image']);
    }
}

$heroImage = !empty($featuredProducts[0]['image'])
    ? basename($featuredProducts[0]['image'])
    : '';

include 'includes/header.php';

?>

<main>

    <!-- HERO -->

    <section class="hero">

        <div class="hero-content">

            <span class="eyebrow">THE NEW SEASON — 2026</span>

            <h1>Less, but<br><em>better.</em></h1>

            <p>
                Discover a refined approach to everyday dressing.
                Timeless pieces, thoughtfully selected.
            </p>

            <a href="/MAISONE/pages/shop.php" class="primary-button">
                DISCOVER THE COLLECTION
                <span>↗</span>
            </a>

        </div>

        <div class="hero-image">

            <?php if ($heroImage): ?>

                <img
                    src="/MAISONE/assets/images/products/<?= htmlspecialchars($heroImage) ?>"
                    alt="MAISONÉ Collection"
                >

            <?php endif; ?>

        </div>

        <div class="hero-index">01 / 03</div>

    </section>


    <!-- CATEGORIES -->

    <section class="categories section-padding">

        <div class="section-heading">

            <span class="eyebrow">EXPLORE MAISONÉ</span>

            <h2>Designed for everyone.</h2>

        </div>

        <div class="category-grid">

            <a
                href="/MAISONE/pages/shop.php?category=women"
                class="category-card women"
            >

                <?php if ($categoryImages['women']): ?>

                    <img
                        class="category-image"
                        src="/MAISONE/assets/images/products/<?= htmlspecialchars($categoryImages['women']) ?>"
                        alt="Women's Collection"
                    >

                <?php endif; ?>

                <div class="category-overlay">

                    <span>01 — COLLECTION</span>

                    <h3>Women</h3>

                    <span>EXPLORE ↗</span>

                </div>

            </a>


            <a
                href="/MAISONE/pages/shop.php?category=men"
                class="category-card men"
            >

                <?php if ($categoryImages['men']): ?>

                    <img
                        class="category-image"
                        src="/MAISONE/assets/images/products/<?= htmlspecialchars($categoryImages['men']) ?>"
                        alt="Men's Collection"
                    >

                <?php endif; ?>

                <div class="category-overlay">

                    <span>02 — COLLECTION</span>

                    <h3>Men</h3>

                    <span>EXPLORE ↗</span>

                </div>

            </a>

        </div>

    </section>


    <!-- FEATURED PRODUCTS -->

    <section class="featured section-padding">

        <div class="section-heading">

            <span class="eyebrow">CURATED FOR YOU</span>

            <h2>Selected essentials.</h2>

            <p>Pieces that define modern simplicity.</p>

        </div>

        <div class="product-grid">

            <?php if (!empty($featuredProducts)): ?>

                <?php foreach ($featuredProducts as $product): ?>

                    <article class="product-card">

                        <a
                            href="/MAISONE/pages/product.php?id=<?= (int)$product['id'] ?>"
                            class="home-product-link"
                        >

                            <div class="product-image">

                                <?php if (!empty($product['image'])): ?>

                                    <img
                                        src="/MAISONE/assets/images/products/<?= htmlspecialchars(basename($product['image'])) ?>"
                                        alt="<?= htmlspecialchars($product['name']) ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <div class="product-no-image">
                                        IMAGE UNAVAILABLE
                                    </div>

                                <?php endif; ?>

                                <span class="product-tag">NEW</span>

                            </div>

                            <div class="product-info">

                                <div>

                                    <h3>
                                        <?= htmlspecialchars($product['name']) ?>
                                    </h3>

                                    <p>
                                        <?= htmlspecialchars(ucfirst($product['category'])) ?>
                                        ·
                                        <?= htmlspecialchars($product['color'] ?? '') ?>
                                    </p>

                                </div>

                                <span>
                                    €<?= number_format((float)$product['price'], 2) ?>
                                </span>

                            </div>

                        </a>

                    </article>

                <?php endforeach; ?>

            <?php else: ?>

                <p class="no-products">
                    No products available at the moment.
                </p>

            <?php endif; ?>

        </div>

        <div class="featured-view-all">

            <a href="/MAISONE/pages/shop.php">
                VIEW ALL PRODUCTS ↗
            </a>

        </div>

    </section>


    <!-- BRAND MESSAGE -->

    <section class="brand-message">

        <span class="eyebrow">OUR PHILOSOPHY</span>

        <h2>
            Style is not about more.<br>
            <em>It's about meaning.</em>
        </h2>

        <p>
            Modern essentials. Considered details. Lasting impressions.
        </p>

    </section>

</main>

<?php include 'includes/footer.php'; ?>
