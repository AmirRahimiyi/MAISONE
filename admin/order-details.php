
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Escape output
$escape = static function ($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
};

// Allowed statuses
$allowedStatuses = [
    'pending',
    'processing',
    'shipped',
    'completed',
    'cancelled'
];

// Validate order ID
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId || $orderId <= 0) {
    header('Location: orders.php');
    exit;
}

// CSRF token
if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';
    $newStatus = $_POST['status'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['admin_csrf_token'], $submittedToken)
    ) {
        $_SESSION['order_status_error'] = 'Invalid security token. Please try again.';

        header('Location: order-details.php?id=' . $orderId);
        exit;
    }

    if (!in_array($newStatus, $allowedStatuses, true)) {

        $_SESSION['order_status_error'] = 'Invalid order status.';

    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE orders
                SET status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $newStatus,
                $orderId
            ]);

            $_SESSION['order_status_success'] = 'Order status updated successfully.';

            // Rotate token after successful update
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

        } catch (PDOException $e) {

            error_log('Order status update failed: ' . $e->getMessage());

            $_SESSION['order_status_error'] = 'Unable to update order status.';

        }
    }

    header('Location: order-details.php?id=' . $orderId);
    exit;
}

// Fetch order
$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$orderId]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Validate stored status for display
$currentStatus = $order['status'] ?? 'pending';

if (!in_array($currentStatus, $allowedStatuses, true)) {
    $currentStatus = 'pending';
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT *
    FROM order_items
    WHERE order_id = ?
    ORDER BY id ASC
");

$stmt->execute([$orderId]);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total quantity
$totalQuantity = 0;

foreach ($items as $item) {
    $totalQuantity += (int)$item['quantity'];
}

// Flash messages
$success = $_SESSION['order_status_success'] ?? '';
$error = $_SESSION['order_status_error'] ?? '';

unset(
    $_SESSION['order_status_success'],
    $_SESSION['order_status_error']
);

// Format date
$orderDate = !empty($order['created_at'])
    ? strtotime($order['created_at'])
    : false;

include __DIR__ . '/header.php';

?>

<section class="admin-order-details">

    <!-- Page Heading -->
    <div class="admin-page-heading">

        <div>

            <span class="admin-eyebrow">
                ORDER MANAGEMENT
            </span>

            <h1>
                Order #<?= (int)$order['id'] ?>
            </h1>

            <p>
                Placed on
                <?= $orderDate
                    ? date('F d, Y — H:i', $orderDate)
                    : 'N/A'
                ?>
            </p>

        </div>

        <a
            href="orders.php"
            class="back-orders-link"
        >
            ← BACK TO ORDERS
        </a>

    </div>


    <!-- Notifications -->
    <?php if ($success): ?>

        <div class="order-success-message">
            <?= $escape($success) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="order-error">
            <?= $escape($error) ?>
        </div>

    <?php endif; ?>


    <div class="order-detail-grid">

        <!-- Main Content -->
        <div class="order-detail-main">


            <!-- Order Items -->
            <div class="dashboard-section">

                <div class="section-heading">

                    <div>

                        <span class="admin-eyebrow">
                            PURCHASE
                        </span>

                        <h2>Order Items</h2>

                    </div>

                    <span class="order-items-count">
                        <?= $totalQuantity ?> ITEMS
                    </span>

                </div>


                <?php if (!empty($items)): ?>

                    <?php foreach ($items as $item): ?>

                        <?php

                        $quantity = max(0, (int)$item['quantity']);
                        $price = (float)$item['price'];
                        $subtotal = $price * $quantity;

                        ?>

                        <div class="admin-order-item">

                            <div class="order-item-info">

                                <strong>
                                    <?= $escape($item['product_name']) ?>
                                </strong>

                                <small>
                                    Quantity: <?= $quantity ?>
                                </small>

                            </div>


                            <div class="order-item-price">

                                €<?= number_format($price, 2) ?>

                            </div>


                            <div class="order-item-subtotal">

                                €<?= number_format($subtotal, 2) ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p class="empty-orders">
                        No items found for this order.
                    </p>

                <?php endif; ?>


                <div class="admin-order-total">

                    <span>ORDER TOTAL</span>

                    <strong>
                        €<?= number_format((float)$order['total'], 2) ?>
                    </strong>

                </div>

            </div>


            <!-- Customer Information -->
            <div class="dashboard-section customer-info-section">

                <span class="admin-eyebrow">
                    CUSTOMER INFORMATION
                </span>

                <h2>Customer Details</h2>


                <div class="order-detail-grid customer-detail-grid">

                    <div>

                        <small>FULL NAME</small>

                        <strong>
                            <?= $escape($order['customer_name'] ?? '') ?>
                        </strong>

                    </div>


                    <div>

                        <small>EMAIL ADDRESS</small>

                        <strong>
                            <?= $escape($order['email'] ?? '') ?>
                        </strong>

                    </div>


                    <div>

                        <small>PHONE NUMBER</small>

                        <strong>
                            <?= $escape($order['phone'] ?? '') ?>
                        </strong>

                    </div>


                    <div>

                        <small>CITY</small>

                        <strong>
                            <?= $escape($order['city'] ?? '') ?>
                        </strong>

                    </div>


                    <div>

                        <small>POSTAL CODE</small>

                        <strong>
                            <?= $escape($order['postal_code'] ?? '') ?>
                        </strong>

                    </div>


                    <div>

                        <small>SHIPPING ADDRESS</small>

                        <strong>
                            <?= nl2br($escape($order['address'] ?? '')) ?>
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- Sidebar -->
        <aside class="order-detail-sidebar">

            <div class="dashboard-section">

                <span class="admin-eyebrow">
                    ORDER STATUS
                </span>

                <h2>Update Status</h2>


                <div class="current-order-status">

                    <span class="status-badge status-<?= $escape($currentStatus) ?>">

                        <?= $escape(ucfirst($currentStatus)) ?>

                    </span>

                </div>


                <form
                    method="POST"
                    class="order-status-form"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= $escape($_SESSION['admin_csrf_token']) ?>"
                    >


                    <label for="order-status">
                        New Status
                    </label>


                    <select
                        name="status"
                        id="order-status"
                        required
                    >

                        <?php foreach ($allowedStatuses as $status): ?>

                            <option
                                value="<?= $escape($status) ?>"
                                <?= $currentStatus === $status ? 'selected' : '' ?>
                            >
                                <?= $escape(ucfirst($status)) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>


                    <button type="submit">
                        UPDATE STATUS
                    </button>

                </form>

            </div>


            <!-- Order Summary -->
            <div class="dashboard-section order-summary-section">

                <span class="admin-eyebrow">
                    ORDER SUMMARY
                </span>

                <h2>Summary</h2>


                <div class="order-summary-row">

                    <span>Order ID</span>

                    <strong>
                        #<?= (int)$order['id'] ?>
                    </strong>

                </div>


                <div class="order-summary-row">

                    <span>Total Items</span>

                    <strong>
                        <?= $totalQuantity ?>
                    </strong>

                </div>


                <div class="order-summary-row">

                    <span>Status</span>

                    <strong>
                        <?= $escape(ucfirst($currentStatus)) ?>
                    </strong>

                </div>


                <div class="order-summary-row">

                    <span>Total Amount</span>

                    <strong>
                        €<?= number_format((float)$order['total'], 2) ?>
                    </strong>

                </div>

            </div>

        </aside>

    </div>

</section>

<?php include __DIR__ . '/footer.php'; ?>
