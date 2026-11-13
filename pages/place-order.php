
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Only POST requests are allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

// Check cart
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// CSRF validation
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    $_SESSION['checkout_error'] = 'Your session has expired. Please try again.';
    header('Location: checkout.php');
    exit;
}

// Get customer information
$customerName = trim($_POST['customer_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$postalCode = trim($_POST['postal_code'] ?? '');

// Validate customer information
if (
    $customerName === '' ||
    strlen($customerName) > 150 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 190 ||
    $phone === '' ||
    strlen($phone) > 30 ||
    $address === '' ||
    strlen($address) > 500 ||
    $city === '' ||
    strlen($city) > 100 ||
    $postalCode === '' ||
    strlen($postalCode) > 30
) {
    $_SESSION['checkout_error'] = 'Please enter valid customer information.';
    header('Location: checkout.php');
    exit;
}

// Validate cart quantities
$cart = [];

foreach ($_SESSION['cart'] as $id => $quantity) {

    $id = filter_var($id, FILTER_VALIDATE_INT);
    $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

    if (!$id || $quantity === false || $quantity < 1 || $quantity > 99) {
        $_SESSION['checkout_error'] = 'Your cart contains invalid quantities.';
        header('Location: cart.php');
        exit;
    }

    $cart[$id] = $quantity;
}

if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

try {

    $pdo->beginTransaction();

    // Fetch and lock products
    $ids = array_keys($cart);

    $placeholders = implode(
        ',',
        array_fill(0, count($ids), '?')
    );

    $stmt = $pdo->prepare("
        SELECT id, name, price, stock
        FROM products
        WHERE id IN ($placeholders)
        AND is_active = 1
        FOR UPDATE
    ");

    $stmt->execute($ids);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($products) !== count($cart)) {
        throw new Exception(
            'One or more products are no longer available.'
        );
    }

    // Calculate total and validate stock
    $total = 0;

    foreach ($products as $product) {

        $id = (int)$product['id'];
        $quantity = $cart[$id];
        $stock = (int)$product['stock'];

        if ($quantity > $stock) {
            throw new Exception(
                'Insufficient stock for: ' . $product['name']
            );
        }

        $total += (float)$product['price'] * $quantity;
    }

    // Create order
    $stmt = $pdo->prepare("
        INSERT INTO orders
        (
            customer_name,
            email,
            phone,
            address,
            city,
            postal_code,
            total
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $customerName,
        $email,
        $phone,
        $address,
        $city,
        $postalCode,
        $total
    ]);

    $orderId = (int)$pdo->lastInsertId();

    // Prepare order items
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items
        (
            order_id,
            product_id,
            product_name,
            price,
            quantity
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    // Prepare stock update
    $stockStmt = $pdo->prepare("
        UPDATE products
        SET stock = stock - ?
        WHERE id = ?
        AND stock >= ?
    ");

    foreach ($products as $product) {

        $id = (int)$product['id'];
        $quantity = $cart[$id];

        $itemStmt->execute([
            $orderId,
            $id,
            $product['name'],
            $product['price'],
            $quantity
        ]);

        $stockStmt->execute([
            $quantity,
            $id,
            $quantity
        ]);

        if ($stockStmt->rowCount() !== 1) {
            throw new Exception(
                'Product stock changed. Please try again.'
            );
        }
    }

    // Commit order
    $pdo->commit();

    // Save order ID
    $_SESSION['last_order_id'] = $orderId;

    // Clear cart
    unset($_SESSION['cart']);

    // Rotate token to prevent resubmission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: order-success.php?id=' . $orderId);
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    $_SESSION['checkout_error'] = $e instanceof PDOException
        ? 'Something went wrong while placing your order. Please try again.'
        : $e->getMessage();

    header('Location: checkout.php');
    exit;
}
