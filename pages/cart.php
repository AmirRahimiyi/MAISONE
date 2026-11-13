
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Initialize cart
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Remove product using URL fallback
if (isset($_GET['remove'])) {

    $removeId = filter_input(INPUT_GET, 'remove', FILTER_VALIDATE_INT);

    if ($removeId && isset($_SESSION['cart'][$removeId])) {
        unset($_SESSION['cart'][$removeId]);
    }

    header('Location: cart.php');
    exit;
}

// Update quantities
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['quantities']) &&
    is_array($_POST['quantities'])
) {

    foreach ($_POST['quantities'] as $id => $quantity) {

        $id = filter_var($id, FILTER_VALIDATE_INT);
        $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

        if (!$id || !isset($_SESSION['cart'][$id])) {
            continue;
        }

        if ($quantity === false || $quantity <= 0) {
            unset($_SESSION['cart'][$id]);
            continue;
        }

        $stmt = $pdo->prepare("
            SELECT stock
            FROM products
            WHERE id = ?
            AND is_active = 1
            LIMIT 1
        ");

        $stmt->execute([$id]);

        $stock = $stmt->fetchColumn();

        if ($stock === false || (int)$stock <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            $_SESSION['cart'][$id] = min(
                $quantity,
                (int)$stock,
                99
            );
        }
    }

    header('Location: cart.php');
    exit;
}

// Fetch cart products
$cartProducts = [];
$total = 0;

if (!empty($_SESSION['cart'])) {

    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT *
        FROM products
        WHERE id IN ($placeholders)
        AND is_active = 1
    ");

    $stmt->execute($ids);

    $cartProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Remove unavailable products from session
    $validCart = [];

    foreach ($cartProducts as $product) {

        $id = (int)$product['id'];
        $stock = max(0, (int)$product['stock']);
        $quantity = min(
            max(0, (int)($_SESSION['cart'][$id] ?? 0)),
            $stock,
            99
        );

        if ($quantity > 0) {
            $validCart[$id] = $quantity;
        }
    }

    $_SESSION['cart'] = $validCart;

    // Keep only products that are still available
    $cartProducts = array_values(array_filter(
        $cartProducts,
        function ($product) use ($validCart) {
            return isset($validCart[(int)$product['id']]);
        }
    ));
}

include '../includes/header.php';

?>

<main class="cart-page">

    <!-- Cart Heading -->
    <section class="cart-heading">

        <span class="eyebrow">YOUR SELECTION</span>

        <h1>Shopping Bag.</h1>

    </section>


    <!-- Cart Content -->
    <section class="cart-content">

        <?php if (!empty($cartProducts)): ?>

            <form method="POST" class="cart-form">

                <div class="cart-items">

                    <?php foreach ($cartProducts as $product): ?>

                        <?php

                        $id = (int)$product['id'];

                        $quantity = (int)$_SESSION['cart'][$id];

                        $price = (float)$product['price'];

                        $subtotal = $price * $quantity;

                        $total += $subtotal;

                        $imageFile = trim($product['image'] ?? '');
                        $cartImagePath = '';

                        if ($imageFile !== '') {

                            $imageFile = str_replace('\\', '/', $imageFile);

                            $fullImagePath = __DIR__ .
                                '/../assets/images/' . $imageFile;

                            if (is_file($fullImagePath)) {

                                $cartImagePath = '../assets/images/' .
                                    implode(
                                        '/',
                                        array_map(
                                            'rawurlencode',
                                            explode('/', $imageFile)
                                        )
                                    );
                            }
                        }

                        ?>

                        <article
                            class="cart-item"
                            data-product-id="<?= $id ?>"
                        >

                            <!-- Product Image -->
                            <div class="cart-item-image">

                                <?php if ($cartImagePath !== ''): ?>

                                    <img
                                        src="<?= htmlspecialchars(
                                            $cartImagePath,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $product['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >

                                <?php else: ?>

                                    <span>Image unavailable</span>

                                <?php endif; ?>

                            </div>


                            <!-- Product Information -->
                            <div class="cart-item-info">

                                <h3>
                                    <?= htmlspecialchars(
                                        $product['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </h3>

                                <p>
                                    <?= htmlspecialchars(
                                        ucfirst($product['category']),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                    ·

                                    <?= htmlspecialchars(
                                        $product['color'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>

                                <p>
                                    €<?= number_format($price, 2) ?>
                                </p>

                            </div>


                            <!-- Quantity -->
                            <div class="cart-item-quantity">

                                <label for="quantity-<?= $id ?>">
                                    Quantity
                                </label>

                                <div
                                    class="quantity-control"
                                    data-price="<?= htmlspecialchars(
                                        (string)$price,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                                    <button
                                        type="button"
                                        class="quantity-btn"
                                        data-action="minus"
                                        aria-label="Decrease quantity"
                                    >
                                        −
                                    </button>

                                    <input
                                        type="number"
                                        id="quantity-<?= $id ?>"
                                        name="quantities[<?= $id ?>]"
                                        value="<?= $quantity ?>"
                                        min="0"
                                        max="<?= min(
                                            (int)$product['stock'],
                                            99
                                        ) ?>"
                                    >

                                    <button
                                        type="button"
                                        class="quantity-btn"
                                        data-action="plus"
                                        aria-label="Increase quantity"
                                    >
                                        +
                                    </button>

                                </div>

                            </div>


                            <!-- Subtotal -->
                            <div
                                class="cart-item-subtotal"
                                data-subtotal
                            >
                                €<?= number_format($subtotal, 2) ?>
                            </div>


                            <!-- Remove -->
                            <button
                                type="button"
                                class="remove-item"
                                data-product-id="<?= $id ?>"
                            >
                                Remove
                            </button>

                        </article>

                    <?php endforeach; ?>

                </div>


                <button
                    type="submit"
                    class="update-cart-btn"
                >
                    UPDATE BAG
                </button>

            </form>


            <!-- Cart Total -->
            <div class="cart-total">

                <span>Total</span>

                <strong id="cart-total">
                    €<?= number_format($total, 2) ?>
                </strong>

            </div>


            <!-- Cart Actions -->
            <div class="cart-actions">

                <a
                    href="shop.php"
                    class="continue-shopping"
                >
                    CONTINUE SHOPPING
                </a>

                <a
                    href="checkout.php"
                    class="checkout-btn"
                >
                    PROCEED TO CHECKOUT
                </a>

            </div>


        <?php else: ?>

            <!-- Empty Cart -->
            <div class="empty-cart">

                <h2>Your bag is empty.</h2>

                <p>Discover something you'll love.</p>

                <a href="shop.php">
                    EXPLORE COLLECTION
                </a>

            </div>

        <?php endif; ?>

    </section>

</main>


<script>

// Format prices
function formatPrice(value) {
    return '€' + value.toFixed(2);
}


// Update cart totals
function updateCartTotals() {

    let total = 0;

    document.querySelectorAll('.cart-item').forEach(item => {

        const control = item.querySelector('.quantity-control');
        const input = control.querySelector('input');
        const price = parseFloat(control.dataset.price) || 0;

        const quantity = Math.max(
            0,
            parseInt(input.value) || 0
        );

        const subtotal = price * quantity;

        const subtotalElement = item.querySelector('[data-subtotal]');

        subtotalElement.textContent = formatPrice(subtotal);

        total += subtotal;

    });

    const totalElement = document.querySelector('#cart-total');

    if (totalElement) {
        totalElement.textContent = formatPrice(total);
    }

}


// Quantity controls
document.querySelectorAll('.quantity-control').forEach(control => {

    const input = control.querySelector('input');

    control.querySelectorAll('.quantity-btn').forEach(button => {

        button.addEventListener('click', () => {

            let value = parseInt(input.value) || 0;

            const min = parseInt(input.min) || 0;
            const max = parseInt(input.max) || 99;

            if (button.dataset.action === 'plus') {
                value = Math.min(value + 1, max);
            }

            if (button.dataset.action === 'minus') {
                value = Math.max(value - 1, min);
            }

            input.value = value;

            updateCartTotals();

        });

    });

    input.addEventListener('input', updateCartTotals);

});


// Remove product with AJAX
const cartContent = document.querySelector('.cart-content');

if (cartContent) {

    cartContent.addEventListener('click', async event => {

        const button = event.target.closest('.remove-item');

        if (!button) return;

        const productId = button.dataset.productId;
        const productRow = button.closest('.cart-item');

        button.disabled = true;

        try {

            const response = await fetch('remove-from-cart.php', {

                method: 'POST',

                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },

                body: new URLSearchParams({
                    id: productId
                })

            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Could not remove product'
                );
            }

            // Remove row
            productRow.remove();

            // Update total
            const totalElement = document.querySelector('#cart-total');

            if (totalElement) {
                totalElement.textContent = formatPrice(
                    parseFloat(result.total) || 0
                );
            }

            // Update header bag count if available
            const bagElement = document.querySelector('.cart-link');

            if (bagElement) {
                bagElement.textContent = `BAG (${result.cart_count})`;
            }

            // Show empty cart
            if (!document.querySelector('.cart-item')) {

                cartContent.innerHTML = `
                    <div class="empty-cart">
                        <h2>Your bag is empty.</h2>
                        <p>Discover something you'll love.</p>
                        <a href="shop.php">EXPLORE COLLECTION</a>
                    </div>
                `;

            }

        } catch (error) {

            alert(error.message);

            button.disabled = false;

        }

    });

}

</script>

<?php include '../includes/footer.php'; ?>
