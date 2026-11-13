
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($productId <= 0 || $quantity < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    exit;
}

// بررسی محصول و موجودی
$stmt = $pdo->prepare("
    SELECT id, price, stock
    FROM products
    WHERE id = ? AND is_active = 1
    LIMIT 1
");

$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product || $quantity > (int)$product['stock']) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Requested quantity is not available'
    ]);
    exit;
}

// بروزرسانی سبد خرید
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$_SESSION['cart'][$productId] = $quantity;

// محاسبه مجدد مبلغ کل از دیتابیس
$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$stmt = $pdo->prepare("
    SELECT id, price
    FROM products
    WHERE id IN ($placeholders)
    AND is_active = 1
");

$stmt->execute($ids);
$cartProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
$cartCount = 0;

foreach ($cartProducts as $item) {
    $id = (int)$item['id'];
    $itemQuantity = (int)($_SESSION['cart'][$id] ?? 0);

    $total += (float)$item['price'] * $itemQuantity;
    $cartCount += $itemQuantity;
}

echo json_encode([
    'success' => true,
    'quantity' => $quantity,
    'subtotal' => (float)$product['price'] * $quantity,
    'total' => $total,
    'cart_count' => $cartCount
]);
