
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

// Calculate total cart quantity
$cartCount = 0;

if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = array_sum(array_map('intval', $_SESSION['cart']));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="MAISONÉ — Modern fashion, timeless elegance.">

    <title>MAISONÉ | Modern Fashion</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="/MAISONE/assets/css/style.css">
</head>

<body>

<div class="announcement">
    Complimentary shipping on orders over €150
</div>

<header class="header">

    <a href="/MAISONE/" class="logo">
        MAISONÉ<span>.</span>
    </a>

    <nav class="navigation">
        <a href="/MAISONE/">Home</a>
        <a href="/MAISONE/pages/shop.php?category=women">Women</a>
        <a href="/MAISONE/pages/shop.php?category=men">Men</a>
        <a href="/MAISONE/pages/shop.php?category=new">New Arrivals</a>
    </nav>

    <div class="header-actions">

        <a href="/MAISONE/pages/cart.php" class="cart-link">
            BAG (<?= $cartCount ?>)
        </a>

        <button class="menu-toggle" aria-label="Open menu">
            ☰
        </button>

    </div>

</header>

