
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function sendResponse($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse([
        'success' => false,
        'message' => 'Invalid request method'
    ], 405);
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || empty($_SESSION['cart'][$id])) {
    sendResponse([
        'success' => false,
        'message' => 'Product not found in cart'
    ], 400);
}

try {

    require_once __DIR__ . '/../config/database.php';

    // Prepare remaining cart IDs
    $remainingCart = $_SESSION['cart'];
    unset($remainingCart[$id]);

    $total = 0;
    $validCart = [];

    if (!empty($remainingCart)) {

        $ids = array_keys($remainingCart);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $pdo->prepare("
            SELECT id, price, stock
            FROM products
            WHERE id IN ($placeholders)
            AND is_active = 1
        ");

        $stmt->execute($ids);

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {

            $productId = (int)$product['id'];
            $stock = max(0, (int)$product['stock']);
            $quantity = min(
                max(0, (int)$remainingCart[$productId]),
                $stock,
                99
            );

            if ($quantity <= 0) {
                continue;
            }

            $validCart[$productId] = $quantity;

            $total += (float)$product['price'] * $quantity;
        }
    }

    // Update session after successful database processing
    $_SESSION['cart'] = $validCart;

    $cartCount = array_sum($validCart);

    sendResponse([
        'success' => true,
        'cart_count' => $cartCount,
        'total' => number_format($total, 2, '.', ''),
        'message' => 'Product removed successfully'
    ]);

} catch (Throwable $e) {

    error_log($e->getMessage());

    sendResponse([
        'success' => false,
        'message' => 'Server error. Please try again.'
    ], 500);
}
