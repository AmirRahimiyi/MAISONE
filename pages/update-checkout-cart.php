
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
        'message' => 'Invalid request method.'
    ], 405);
}

if (
    empty($_SESSION['cart']) ||
    !is_array($_SESSION['cart']) ||
    !isset($_POST['quantities']) ||
    !is_array($_POST['quantities'])
) {
    sendResponse([
        'success' => false,
        'message' => 'Invalid cart data.'
    ], 400);
}

try {

    require_once __DIR__ . '/../config/database.php';

    $quantities = $_POST['quantities'];

    $ids = [];

    foreach ($quantities as $id => $quantity) {

        $id = filter_var($id, FILTER_VALIDATE_INT);
        $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

        if (
            !$id ||
            $quantity === false ||
            $quantity < 0 ||
            $quantity > 99 ||
            !array_key_exists($id, $_SESSION['cart'])
        ) {
            sendResponse([
                'success' => false,
                'message' => 'Invalid product quantity.'
            ], 400);
        }

        $ids[$id] = $quantity;
    }

    if (count($ids) !== count($_SESSION['cart'])) {
        sendResponse([
            'success' => false,
            'message' => 'Cart data is incomplete.'
        ], 400);
    }

    $placeholders = implode(
        ',',
        array_fill(0, count($ids), '?')
    );

    $stmt = $pdo->prepare("
        SELECT id, stock
        FROM products
        WHERE id IN ($placeholders)
        AND is_active = 1
    ");

    $stmt->execute(array_keys($ids));

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($products) !== count($ids)) {
        sendResponse([
            'success' => false,
            'message' => 'One or more products are unavailable.'
        ], 400);
    }

    $newCart = [];

    foreach ($products as $product) {

        $id = (int)$product['id'];
        $quantity = $ids[$id];
        $stock = max(0, (int)$product['stock']);

        if ($quantity > $stock) {
            sendResponse([
                'success' => false,
                'message' => 'Not enough stock available.'
            ], 400);
        }

        if ($quantity > 0) {
            $newCart[$id] = $quantity;
        }
    }

    $_SESSION['cart'] = $newCart;

    sendResponse([
        'success' => true,
        'cart_count' => array_sum($newCart)
    ]);

} catch (Throwable $e) {

    error_log($e->getMessage());

    sendResponse([
        'success' => false,
        'message' => 'Server error.'
    ], 500);
}
