
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

function escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Product statistics
$productStats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) AS active
    FROM products
")->fetch(PDO::FETCH_ASSOC);

// Order statistics
$orderStats = $pdo->query("
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
        COALESCE(SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END), 0) AS processing,
        COALESCE(SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END), 0) AS shipped,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed,
        COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled,
        COALESCE(SUM(
            CASE WHEN COALESCE(status, 'pending') <> 'cancelled'
            THEN total ELSE 0 END
        ), 0) AS revenue
    FROM orders
")->fetch(PDO::FETCH_ASSOC);

// Recent orders
$recentOrders = $pdo->query("
    SELECT id, customer_name, email, total, status, created_at
    FROM orders
    ORDER BY created_at DESC, id DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';

?>

<section class="admin-dashboard">

    <div class="admin-page-heading">
        <div>
            <span class="admin-eyebrow">STORE OVERVIEW</span>
            <h1>Dashboard</h1>
            <p>Welcome back, <?= escape($_SESSION['admin_username'] ?? 'Admin') ?>. Here's your store performance.</p>
        </div>
    </div>

    <!-- Main statistics -->
    <div class="dashboard-stats-grid">

        <div class="dashboard-stat-card">
            <span>Total Products</span>
            <strong><?= number_format((int)$productStats['total']) ?></strong>
            <small>All products in your store</small>
        </div>

        <div class="dashboard-stat-card">
            <span>Active Products</span>
            <strong><?= number_format((int)$productStats['active']) ?></strong>
            <small>Currently available products</small>
        </div>

        <div class="dashboard-stat-card">
            <span>Total Orders</span>
            <strong><?= number_format((int)$orderStats['total_orders']) ?></strong>
            <small>All customer orders</small>
        </div>

        <div class="dashboard-stat-card dashboard-revenue-card">
            <span>Total Revenue</span>
            <strong>€<?= number_format((float)$orderStats['revenue'], 2) ?></strong>
            <small>Excluding cancelled orders</small>
        </div>

    </div>

    <!-- Order status -->
    <div class="admin-section-heading">
        <div>
            <span class="admin-eyebrow">ORDER MANAGEMENT</span>
            <h2>Order Status</h2>
        </div>

        <a href="orders.php" class="admin-text-link">VIEW ORDERS ↗</a>
    </div>

    <div class="dashboard-status-grid">

        <div class="dashboard-status-card">
            <span>Pending</span>
            <strong><?= number_format((int)$orderStats['pending']) ?></strong>
        </div>

        <div class="dashboard-status-card">
            <span>Processing</span>
            <strong><?= number_format((int)$orderStats['processing']) ?></strong>
        </div>

        <div class="dashboard-status-card">
            <span>Shipped</span>
            <strong><?= number_format((int)$orderStats['shipped']) ?></strong>
        </div>

        <div class="dashboard-status-card">
            <span>Completed</span>
            <strong><?= number_format((int)$orderStats['completed']) ?></strong>
        </div>

        <div class="dashboard-status-card">
            <span>Cancelled</span>
            <strong><?= number_format((int)$orderStats['cancelled']) ?></strong>
        </div>

    </div>

    <!-- Recent orders -->
    <section class="dashboard-section">

        <div class="admin-section-heading">
            <div>
                <span class="admin-eyebrow">LATEST ACTIVITY</span>
                <h2>Recent Orders</h2>
            </div>

            <a href="orders.php" class="admin-text-link">VIEW ALL ↗</a>
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
                        <th>ACTION</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($recentOrders)): ?>

                        <tr>
                            <td colspan="6" class="empty-orders">
                                No orders have been placed yet.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($recentOrders as $order): ?>

                            <?php
                            $status = $order['status'] ?? 'pending';

                            $validStatuses = [
                                'pending',
                                'processing',
                                'shipped',
                                'completed',
                                'cancelled'
                            ];

                            if (!in_array($status, $validStatuses, true)) {
                                $status = 'pending';
                            }

                            $timestamp = !empty($order['created_at'])
                                ? strtotime($order['created_at'])
                                : false;
                            ?>

                            <tr>

                                <td>
                                    <strong>#<?= (int)$order['id'] ?></strong>
                                </td>

                                <td>
                                    <div class="dashboard-customer">
                                        <strong><?= escape($order['customer_name']) ?></strong>
                                        <small><?= escape($order['email']) ?></small>
                                    </div>
                                </td>

                                <td>
                                    <?= $timestamp ? date('M d, Y', $timestamp) : 'N/A' ?>
                                </td>

                                <td>
                                    €<?= number_format((float)$order['total'], 2) ?>
                                </td>

                                <td>
                                    <span class="status-badge status-<?= escape($status) ?>">
                                        <?= escape(ucfirst($status)) ?>
                                    </span>
                                </td>

                                <td>
                                    <a
                                        href="order-details.php?id=<?= (int)$order['id'] ?>"
                                        class="dashboard-view-link"
                                    >
                                        VIEW
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</section>

<style>
/* Dashboard additions only */

.admin-dashboard {
    width: 100%;
}

.admin-dashboard .dashboard-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin: 30px 0 45px;
}

.admin-dashboard .dashboard-stat-card {
    min-width: 0;
    padding: 25px;
    background: #fff;
    border: 1px solid var(--border, #E6E8EC);
}

.admin-dashboard .dashboard-stat-card > span,
.admin-dashboard .dashboard-status-card > span {
    display: block;
    color: var(--muted, #858891);
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.admin-dashboard .dashboard-stat-card strong {
    display: block;
    margin-top: 18px;
    font-family: var(--font-display, 'Playfair Display', serif);
    font-size: clamp(25px, 3vw, 36px);
    font-weight: 400;
    overflow-wrap: anywhere;
}

.admin-dashboard .dashboard-stat-card small {
    display: block;
    margin-top: 10px;
    color: var(--muted, #858891);
    font-size: 11px;
}

.admin-dashboard .dashboard-revenue-card {
    background: var(--black, #17191D);
    border-color: var(--black, #17191D);
    color: #fff;
}

.admin-dashboard .dashboard-revenue-card > span,
.admin-dashboard .dashboard-revenue-card small {
    color: #C1C4CB;
}

.admin-dashboard .admin-section-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin: 0 0 22px;
}

.admin-dashboard .admin-section-heading h2 {
    margin: 7px 0 0;
    font-family: var(--font-display, 'Playfair Display', serif);
    font-size: 26px;
    font-weight: 400;
}

.admin-dashboard .admin-text-link,
.admin-dashboard .dashboard-view-link {
    color: var(--black, #17191D);
    text-decoration: none;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: .8px;
    white-space: nowrap;
}

.admin-dashboard .dashboard-status-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 45px;
}

.admin-dashboard .dashboard-status-card {
    padding: 20px;
    background: #fff;
    border: 1px solid var(--border, #E6E8EC);
}

.admin-dashboard .dashboard-status-card strong {
    display: block;
    margin-top: 13px;
    font-family: var(--font-display, 'Playfair Display', serif);
    font-size: 29px;
    font-weight: 400;
}

.admin-dashboard .dashboard-section {
    min-width: 0;
}

.admin-dashboard .dashboard-customer strong,
.admin-dashboard .dashboard-customer small {
    display: block;
}

.admin-dashboard .dashboard-customer strong {
    font-size: 12px;
    font-weight: 600;
}

.admin-dashboard .dashboard-customer small {
    margin-top: 5px;
    color: var(--muted, #858891);
    font-size: 10px;
}

.admin-dashboard .admin-table {
    min-width: 720px;
}

.admin-dashboard .dashboard-view-link:hover,
.admin-dashboard .admin-text-link:hover {
    opacity: .55;
}

@media (max-width: 1100px) {
    .admin-dashboard .dashboard-stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .admin-dashboard .dashboard-status-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 650px) {
    .admin-dashboard .dashboard-stats-grid {
        gap: 10px;
        margin: 22px 0 35px;
    }

    .admin-dashboard .dashboard-stat-card {
        padding: 17px;
    }

    .admin-dashboard .dashboard-stat-card strong {
        font-size: 25px;
    }

    .admin-dashboard .dashboard-status-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .admin-dashboard .dashboard-status-card {
        padding: 16px;
    }

    .admin-dashboard .admin-section-heading {
        align-items: flex-start;
    }
}
</style>

<?php include __DIR__ . '/footer.php'; ?>
