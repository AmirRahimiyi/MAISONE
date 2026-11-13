
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MAISONÉ | Admin Panel</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/MAISONE/assets/css/style.css">
</head>

<body>

<div class="admin-layout">

    <aside class="admin-sidebar">

        <a href="index.php" class="admin-brand">
            MAISONÉ<span>.</span>
            <small>ADMINISTRATION</small>
        </a>

        <div class="sidebar-label">WORKSPACE</div>

        <nav class="admin-nav">

            <a href="index.php"
               class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <span class="nav-icon">▦</span>
                Dashboard
            </a>

            <a href="orders.php"
               class="<?= in_array($currentPage, ['orders.php', 'order-details.php']) ? 'active' : '' ?>">
                <span class="nav-icon">▤</span>
                Orders
            </a>

<a href="products.php"
   class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
    <span class="nav-icon">◇</span>
    Products
</a>

            <a href="#" class="nav-disabled">
                <span class="nav-icon">♙</span>
                Customers
                <small>SOON</small>
            </a>

            <div class="sidebar-label sidebar-label-spaced">
                MANAGEMENT
            </div>

            <a href="#" class="nav-disabled">
                <span class="nav-icon">◷</span>
                Analytics
                <small>SOON</small>
            </a>

            <a href="#" class="nav-disabled">
                <span class="nav-icon">⚙</span>
                Settings
                <small>SOON</small>
            </a>

        </nav>

        <div class="sidebar-bottom">
            <a href="/MAISONE/" target="_blank">
                <span class="nav-icon">↗</span>
                View Store
            </a>
        </div>

    </aside>

    <div class="admin-content">

        <div class="admin-topbar">
            <span>MAISONÉ / ADMIN</span>
            <span class="admin-topbar-status">
                <i></i> SYSTEM ONLINE
            </span>
        </div>

        <main class="admin-main">
