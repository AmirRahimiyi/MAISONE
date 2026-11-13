
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Validate order access
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$lastOrderId = (int)($_SESSION['last_order_id'] ?? 0);

if (!$orderId || $orderId !== $lastOrderId) {
    header('Location: ../index.php');
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
    header('Location: ../index.php');
    exit;
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

// Calculate item count
$itemCount = 0;

foreach ($items as $item) {
    $itemCount += (int)$item['quantity'];
}

include '../includes/header.php';

?>

<main class="order-success-page">

    <!-- Success Icon -->
    <div class="success-icon">
        ✓
    </div>


    <!-- Heading -->
    <span class="eyebrow">
        MAISONÉ — ORDER CONFIRMED
    </span>

    <h1>Thank you for your order.</h1>

    <p class="success-message">
        Your order has been successfully placed.
        We appreciate your trust in MAISONÉ.
    </p>


    <!-- Order Details -->
    <div class="order-details">

        <div class="order-info">

            <span>ORDER NUMBER</span>

            <strong>
                #<?= (int)$order['id'] ?>
            </strong>

        </div>


        <div class="order-info">

            <span>ORDER DATE</span>

            <strong>
                <?= !empty($order['created_at'])
                    ? htmlspecialchars(
                        date('d M Y', strtotime($order['created_at'])),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    : 'N/A'
                ?>
            </strong>

        </div>


        <div class="order-info">

            <span>ITEMS</span>

            <strong>
                <?= $itemCount ?>
            </strong>

        </div>


        <div class="order-info">

            <span>ORDER TOTAL</span>

            <strong>
                €<?= number_format((float)$order['total'], 2) ?>
            </strong>

        </div>

    </div>


    <!-- Ordered Items -->
    <section class="success-items">

        <h2>Your Items</h2>

        <?php if (!empty($items)): ?>

            <?php foreach ($items as $item): ?>

                <?php

                $quantity = (int)$item['quantity'];
                $price = (float)$item['price'];

                ?>

                <div class="success-item">

                    <span>

                        <?= htmlspecialchars(
                            $item['product_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                        × <?= $quantity ?>

                    </span>


                    <strong>
                        €<?= number_format($price * $quantity, 2) ?>
                    </strong>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>No order items found.</p>

        <?php endif; ?>


        <div class="success-item success-total">

            <span>TOTAL</span>

            <strong>
                €<?= number_format((float)$order['total'], 2) ?>
            </strong>

        </div>

    </section>


    <!-- Delivery Information -->
    <section class="success-delivery">

        <h2>Delivery Information</h2>

        <div class="delivery-info">

            <p>
                <strong>Name:</strong>

                <?= htmlspecialchars(
                    $order['customer_name'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>


            <p>
                <strong>Email:</strong>

                <?= htmlspecialchars(
                    $order['email'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>


            <p>
                <strong>Phone:</strong>

                <?= htmlspecialchars(
                    $order['phone'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>


            <p>
                <strong>Address:</strong>

                <?= nl2br(htmlspecialchars(
                    $order['address'],
                    ENT_QUOTES,
                    'UTF-8'
                )) ?>
            </p>


            <p>
                <strong>City:</strong>

                <?= htmlspecialchars(
                    $order['city'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>


            <p>
                <strong>Postal Code:</strong>

                <?= htmlspecialchars(
                    $order['postal_code'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

        </div>

    </section>


    <!-- Continue Shopping -->
    <a
        href="../index.php"
        class="continue-shopping"
    >
        CONTINUE SHOPPING
    </a>

</main>

<?php include '../includes/footer.php'; ?>
