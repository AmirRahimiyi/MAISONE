
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Check cart
if (
    empty($_SESSION['cart']) ||
    !is_array($_SESSION['cart'])
) {
    header('Location: cart.php');
    exit;
}

// Validate cart and fetch products
$ids = array_keys($_SESSION['cart']);

$ids = array_filter($ids, function ($id) {
    return filter_var($id, FILTER_VALIDATE_INT) !== false;
});

if (empty($ids)) {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

$placeholders = implode(
    ',',
    array_fill(0, count($ids), '?')
);

$stmt = $pdo->prepare("
    SELECT id, name, price, stock, image
    FROM products
    WHERE id IN ($placeholders)
    AND is_active = 1
");

$stmt->execute(array_values($ids));

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Remove unavailable products and validate quantities
$validCart = [];
$availableProducts = [];
$total = 0;

foreach ($products as $product) {

    $id = (int)$product['id'];
    $stock = max(0, (int)$product['stock']);

    $quantity = filter_var(
        $_SESSION['cart'][$id] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $quantity === false ||
        $quantity < 1 ||
        $stock < 1
    ) {
        continue;
    }

    $quantity = min($quantity, $stock, 99);

    $validCart[$id] = $quantity;
    $availableProducts[] = $product;

    $total += (float)$product['price'] * $quantity;
}

$_SESSION['cart'] = $validCart;

$products = $availableProducts;

if (empty($products)) {
    header('Location: cart.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Checkout error
$checkoutError = $_SESSION['checkout_error'] ?? '';

unset($_SESSION['checkout_error']);

include '../includes/header.php';

?>

<main class="checkout-page">

    <!-- Heading -->
    <section class="checkout-heading">

        <span class="eyebrow">ALMOST YOURS</span>

        <h1>Checkout.</h1>

        <p>Complete your details to place your order.</p>

    </section>


    <div class="checkout-layout">

        <!-- Customer Form -->
        <section class="checkout-form-section">

            <h2>Delivery Information</h2>

            <?php if ($checkoutError !== ''): ?>

                <div class="checkout-error">
                    <?= htmlspecialchars(
                        $checkoutError,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            <?php endif; ?>


            <form
                action="place-order.php"
                method="POST"
                id="checkout-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >


                <div class="checkout-field">

                    <label for="customer_name">Full Name</label>

                    <input
                        type="text"
                        id="customer_name"
                        name="customer_name"
                        maxlength="150"
                        autocomplete="name"
                        required
                    >

                </div>


                <div class="checkout-field">

                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="190"
                        autocomplete="email"
                        required
                    >

                </div>


                <div class="checkout-field">

                    <label for="phone">Phone Number</label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        maxlength="30"
                        autocomplete="tel"
                        required
                    >

                </div>


                <div class="checkout-field">

                    <label for="address">Street Address</label>

                    <textarea
                        id="address"
                        name="address"
                        rows="3"
                        maxlength="500"
                        autocomplete="street-address"
                        required
                    ></textarea>

                </div>


                <div class="checkout-row">

                    <div class="checkout-field">

                        <label for="city">City</label>

                        <input
                            type="text"
                            id="city"
                            name="city"
                            maxlength="100"
                            autocomplete="address-level2"
                            required
                        >

                    </div>


                    <div class="checkout-field">

                        <label for="postal_code">Postal Code</label>

                        <input
                            type="text"
                            id="postal_code"
                            name="postal_code"
                            maxlength="30"
                            autocomplete="postal-code"
                            required
                        >

                    </div>

                </div>


                <button
                    type="submit"
                    class="place-order-btn"
                >
                    PLACE ORDER
                </button>

            </form>

        </section>


        <!-- Order Summary -->
        <aside class="checkout-summary">

            <h2>Order Summary</h2>


            <div class="checkout-summary-items">

                <?php foreach ($products as $product): ?>

                    <?php

                    $id = (int)$product['id'];

                    $quantity = (int)$_SESSION['cart'][$id];

                    $price = (float)$product['price'];

                    $subtotal = $price * $quantity;

                    ?>

                    <div
                        class="summary-item"
                        data-product-id="<?= $id ?>"
                        data-price="<?= htmlspecialchars(
                            (string)$price,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        data-stock="<?= (int)$product['stock'] ?>"
                    >

                        <div class="summary-item-info">

                            <span class="summary-product-name">
                                <?= htmlspecialchars(
                                    $product['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <small>
                                €<?= number_format($price, 2) ?> each
                            </small>

                        </div>


                        <div class="summary-product-price">

                            <div class="summary-quantity">

                                <button
                                    type="button"
                                    class="summary-qty-btn"
                                    data-action="minus"
                                    aria-label="Decrease quantity"
                                >
                                    −
                                </button>


                                <span class="summary-qty">
                                    <?= $quantity ?>
                                </span>


                                <button
                                    type="button"
                                    class="summary-qty-btn"
                                    data-action="plus"
                                    aria-label="Increase quantity"
                                >
                                    +
                                </button>

                            </div>


                            <strong class="summary-subtotal">
                                €<?= number_format($subtotal, 2) ?>
                            </strong>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


            <!-- Total -->
            <div class="summary-total">

                <span>Total</span>

                <strong class="checkout-grand-total">
                    €<?= number_format($total, 2) ?>
                </strong>

            </div>


            <a href="cart.php" class="checkout-back-link">
                EDIT SHOPPING BAG
            </a>

        </aside>

    </div>

</main>


<script>

document.addEventListener('DOMContentLoaded', () => {

    const summary = document.querySelector('.checkout-summary');
    const totalElement = document.querySelector('.checkout-grand-total');

    if (!summary || !totalElement) return;


    function formatPrice(value) {
        return '€' + value.toFixed(2);
    }


    function updateTotal() {

        let total = 0;

        document.querySelectorAll('.summary-item').forEach(item => {

            const price = parseFloat(item.dataset.price) || 0;

            const quantity = parseInt(
                item.querySelector('.summary-qty').textContent
            ) || 0;

            const subtotal = price * quantity;

            item.querySelector('.summary-subtotal').textContent =
                formatPrice(subtotal);

            total += subtotal;

        });

        totalElement.textContent = formatPrice(total);

    }


    async function saveCart() {

        const quantities = {};

        document.querySelectorAll('.summary-item').forEach(item => {

            const id = item.dataset.productId;

            const quantity = parseInt(
                item.querySelector('.summary-qty').textContent
            ) || 0;

            quantities[id] = quantity;

        });

        const formData = new URLSearchParams();

        Object.entries(quantities).forEach(([id, quantity]) => {
            formData.append(`quantities[${id}]`, quantity);
        });

        try {

            const response = await fetch('update-checkout-cart.php', {

                method: 'POST',

                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },

                body: formData

            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Could not update cart.'
                );
            }

            return true;

        } catch (error) {

            alert(error.message);
            window.location.reload();

            return false;

        }

    }


    summary.addEventListener('click', async event => {

        const button = event.target.closest('.summary-qty-btn');

        if (!button) return;

        const item = button.closest('.summary-item');

        const quantityElement = item.querySelector('.summary-qty');

        const currentQuantity = parseInt(
            quantityElement.textContent
        ) || 0;

        const maxStock = Math.min(
            parseInt(item.dataset.stock) || 0,
            99
        );

        let newQuantity = currentQuantity;

        if (button.dataset.action === 'plus') {
            newQuantity = Math.min(currentQuantity + 1, maxStock);
        }

        if (button.dataset.action === 'minus') {
            newQuantity = Math.max(currentQuantity - 1, 0);
        }

        if (newQuantity === currentQuantity) return;

        button.disabled = true;

        quantityElement.textContent = newQuantity;

        if (newQuantity === 0) {
            item.remove();
        }

        updateTotal();

        const saved = await saveCart();

        if (saved) {

            const remainingItems = document.querySelectorAll(
                '.summary-item'
            );

            if (remainingItems.length === 0) {
                window.location.href = 'cart.php';
                return;
            }

        }

        button.disabled = false;

    });

});

</script>

<?php include '../includes/footer.php'; ?>
