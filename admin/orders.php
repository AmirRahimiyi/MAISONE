
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
function escape($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Search and filter
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';

$allowedStatuses = [
    'all',
    'pending',
    'processing',
    'shipped',
    'completed',
    'cancelled'
];

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

// Build query
$sql = "
    SELECT *
    FROM orders
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {

    $sql .= "
        AND (
            CAST(id AS CHAR) LIKE ?
            OR customer_name LIKE ?
            OR email LIKE ?
        )
    ";

    $term = '%' . $search . '%';

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($status !== 'all') {

    $sql .= "
        AND status = ?
    ";

    $params[] = $status;
}

$sql .= "
    ORDER BY created_at DESC, id DESC
";

// Fetch orders
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';

?>

<section class="admin-orders">

    <!-- Page Heading -->
    <div class="admin-page-heading">

        <div>

            <span class="admin-eyebrow">
                MANAGEMENT
            </span>

            <h1>Orders</h1>

            <p>
                Manage and monitor all customer orders.
            </p>

        </div>


        <div class="orders-count">

            <?= count($orders) ?> ORDERS FOUND

        </div>

    </div>


    <!-- Search and Filters -->
    <div class="orders-toolbar">

        <form
            method="GET"
            class="orders-filter-form"
        >

            <div class="orders-search">

                <input
                    type="text"
                    name="search"
                    placeholder="Search by order ID or customer..."
                    value="<?= escape($search) ?>"
                    maxlength="100"
                >

                <button type="submit">
                    SEARCH
                </button>

            </div>


            <select
                name="status"
                onchange="this.form.submit()"
            >

                <option
                    value="all"
                    <?= $status === 'all' ? 'selected' : '' ?>
                >
                    All Statuses
                </option>


                <?php foreach (array_slice($allowedStatuses, 1) as $option): ?>

                    <option
                        value="<?= escape($option) ?>"
                        <?= $status === $option ? 'selected' : '' ?>
                    >

                        <?= escape(ucfirst($option)) ?>

                    </option>

                <?php endforeach; ?>

            </select>


            <?php if ($search !== '' || $status !== 'all'): ?>

                <a
                    href="orders.php"
                    class="clear-filters"
                >
                    CLEAR FILTERS
                </a>

            <?php endif; ?>

        </form>

    </div>


    <!-- Orders Table -->
    <div class="dashboard-section orders-table-section">

        <div class="admin-table-wrapper">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th>ORDER ID</th>
                        <th>CUSTOMER</th>
                        <th>DATE</th>
                        <th>TOTAL</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>

                </thead>


                <tbody>

                    <?php if (empty($orders)): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="empty-orders"
                            >
                                No orders found.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($orders as $order): ?>

                            <?php

                            $orderStatus = $order['status'] ?? 'pending';

                            if (!in_array(
                                $orderStatus,
                                array_slice($allowedStatuses, 1),
                                true
                            )) {
                                $orderStatus = 'pending';
                            }

                            $orderDate = !empty($order['created_at'])
                                ? strtotime($order['created_at'])
                                : false;

                            ?>

                            <tr>

                                <!-- Order ID -->
                                <td>

                                    <strong>
                                        #<?= (int)$order['id'] ?>
                                    </strong>

                                </td>


                                <!-- Customer -->
                                <td>

                                    <strong>
                                        <?= escape($order['customer_name'] ?? '') ?>
                                    </strong>

                                    <small class="table-email">

                                        <?= escape($order['email'] ?? '') ?>

                                    </small>

                                </td>


                                <!-- Date -->
                                <td>

                                    <?= $orderDate
                                        ? date('M d, Y', $orderDate)
                                        : 'N/A'
                                    ?>

                                </td>


                                <!-- Total -->
                                <td>

                                    €<?= number_format(
                                        (float)$order['total'],
                                        2
                                    ) ?>

                                </td>


                                <!-- Status -->
                                <td>

                                    <span class="status-badge status-<?= escape($orderStatus) ?>">

                                        <?= escape(ucfirst($orderStatus)) ?>

                                    </span>

                                </td>


                                <!-- Action -->
                                <td>

                                    <a
                                        href="order-details.php?id=<?= (int)$order['id'] ?>"
                                    >
                                        VIEW DETAILS
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</section>

<?php include __DIR__ . '/footer.php'; ?>
