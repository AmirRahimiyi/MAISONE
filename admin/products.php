
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? 'all';
$availability = $_GET['availability'] ?? 'all';

if (!in_array($category, ['all', 'women', 'men'], true)) {
    $category = 'all';
}

if (!in_array($availability, ['all', 'active', 'inactive'], true)) {
    $availability = 'all';
}

$sql = "SELECT * FROM products WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
}

if ($category !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $category;
}

if ($availability === 'active') {
    $sql .= " AND is_active = 1";
} elseif ($availability === 'inactive') {
    $sql .= " AND is_active = 0";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active,
        SUM(CASE WHEN stock = 0 AND is_active = 1 THEN 1 ELSE 0 END) AS out_of_stock
    FROM products
");

$stats = $stmt->fetch(PDO::FETCH_ASSOC);

include 'header.php';

?>

<section class="admin-products">

    <div class="admin-page-heading">

        <div>
            <span class="admin-eyebrow">INVENTORY MANAGEMENT</span>
            <h1>Products</h1>
            <p>Manage your MAISONÉ product catalog.</p>
        </div>

        <a href="add-product.php" class="admin-primary-button">
            + ADD PRODUCT
        </a>

    </div>

    <div class="product-stats">

        <div class="product-stat-card">
            <span>Total Products</span>
            <strong><?= (int)$stats['total'] ?></strong>
        </div>

        <div class="product-stat-card">
            <span>Active Products</span>
            <strong><?= (int)$stats['active'] ?></strong>
        </div>

        <div class="product-stat-card">
            <span>Out of Stock</span>
            <strong><?= (int)$stats['out_of_stock'] ?></strong>
        </div>

    </div>

    <div class="orders-toolbar">

        <form method="GET" class="products-filter-form">

            <div class="orders-search">

                <input
                    type="text"
                    name="search"
                    placeholder="Search products..."
                    value="<?= htmlspecialchars($search) ?>"
                >

                <button type="submit">SEARCH</button>

            </div>

            <select name="category" onchange="this.form.submit()">
                <option value="all">All Categories</option>
                <option value="women" <?= $category === 'women' ? 'selected' : '' ?>>Women</option>
                <option value="men" <?= $category === 'men' ? 'selected' : '' ?>>Men</option>
            </select>

            <select name="availability" onchange="this.form.submit()">
                <option value="all">All Products</option>
                <option value="active" <?= $availability === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $availability === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>

        </form>

    </div>

    <div class="dashboard-section">

        <div class="admin-table-wrapper">

            <table class="admin-table products-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>PRODUCT</th>
                        <th>CATEGORY</th>
                        <th>COLOR</th>
                        <th>PRICE</th>
                        <th>STOCK</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($products)): ?>

                        <tr>
                            <td colspan="8" class="empty-orders">
                                No products found.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($products as $product): ?>

                            <tr>

                                <td>#<?= (int)$product['id'] ?></td>

                                <td>
                                    <div class="admin-product-cell">

                                        <?php if (!empty($product['image'])): ?>

                                            <img
                                                src="/MAISONE/assets/images/products/<?= rawurlencode(basename($product['image'])) ?>"
                                                alt="<?= htmlspecialchars($product['name']) ?>"
                                            >

                                        <?php else: ?>

                                            <div class="product-image-placeholder">—</div>

                                        <?php endif; ?>

                                        <strong>
                                            <?= htmlspecialchars($product['name']) ?>
                                        </strong>

                                    </div>
                                </td>

                                <td><?= htmlspecialchars(ucfirst($product['category'])) ?></td>

                                <td><?= htmlspecialchars($product['color']) ?></td>

                                <td>€<?= number_format((float)$product['price'], 2) ?></td>

                                <td><?= (int)$product['stock'] ?></td>

                                <td>
                                    <?php if ((int)$product['is_active'] === 1): ?>
                                        <span class="status-badge status-completed">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-cancelled">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="edit-product.php?id=<?= (int)$product['id'] ?>">
                                        EDIT
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</section>

<?php include 'footer.php'; ?>
