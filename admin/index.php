
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

// Total revenue
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total), 0)
    FROM orders
    WHERE status != 'cancelled'
");
$totalRevenue = (float)$stmt->fetchColumn();

// Total orders
$stmt = $pdo->query("
    SELECT COUNT(*) FROM orders
");
$totalOrders = (int)$stmt->fetchColumn();

// Pending orders
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'pending'
");
$pendingOrders = (int)$stmt->fetchColumn();

// Active products
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE is_active = 1
");
$totalProducts = (int)$stmt->fetchColumn();

// Total customers
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT email)
    FROM orders
");
$totalCustomers = (int)$stmt->fetchColumn();

// Recent orders
$stmt = $pdo->query("
    SELECT id, customer_name, email, total, status, created_at
    FROM orders
    ORDER BY created_at DESC
    LIMIT 5
");
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';

?>

<section class="admin-dashboard">

    <div class="admin-page-heading">
        <div>
            <span class="admin-eyebrow">OVERVIEW</span>
            <h1>Dashboard</h1>
            <p>Welcome to your MAISONÉ administration panel.</p>
        </div>

        <span class="dashboard-date">
            <?= date('F d, Y') ?>
        </span>
    </div>

    <div class="dashboard-stats">

        <div class="stat-card">
            <span>Total Revenue</span>
            <h2>€<?= number_format($totalRevenue, 2) ?></h2>
            <small>From non-cancelled orders</small>
        </div>

        <div class="stat-card">
            <span>Total Orders</span>
            <h2><?= number_format($totalOrders) ?></h2>
            <small>All customer orders</small>
        </div>

        <div class="stat-card">
            <span>Pending Orders</span>
            <h2><?= number_format($pendingOrders) ?></h2>
            <small>Awaiting processing</small>
        </div>

        <div class="stat-card">
            <span>Products</span>
            <h2><?= number_format($totalProducts) ?></h2>
            <small>Active products</small>
        </div>

        <div class="stat-card">
            <span>Customers</span>
            <h2><?= number_format($totalCustomers) ?></h2>
            <small>Unique customer emails</small>
        </div>

    </div>

    <div class="dashboard-section">

        <div class="section-heading">
            <div>
                <span class="admin-eyebrow">LATEST ACTIVITY</span>
                <h2>Recent Orders</h2>
            </div>

            <a href="orders.php" class="admin-view-link">
                View All Orders →
            </a>
        </div>

        <div class="admin-table-wrapper">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>ORDER</th>
                        <th>CUSTOMER</th>
                        <th>DATE</th>
                        <th>TOTAL</th>
                        <th>STATUS</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($recentOrders)): ?>

                        <tr>
                            <td colspan="6" class="empty-orders">
                                No orders yet.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($recentOrders as $order): ?>

                            <tr>
                                <td>#<?= (int)$order['id'] ?></td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($order['customer_name']) ?>
                                    </strong>
                                    <small class="table-email">
                                        <?= htmlspecialchars($order['email']) ?>
                                    </small>
                                </td>

                                <td>
                                    <?= date('M d, Y', strtotime($order['created_at'])) ?>
                                </td>

                                <td>
                                    €<?= number_format((float)$order['total'], 2) ?>
                                </td>

                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                                        <?= htmlspecialchars(ucfirst($order['status'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <a href="order-details.php?id=<?= (int)$order['id'] ?>">
                                        View
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
