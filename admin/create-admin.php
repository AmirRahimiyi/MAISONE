
<?php

require_once '../config/database.php';

$message = '';

$stmt = $pdo->query("SELECT COUNT(*) FROM admins");
$adminExists = (int) $stmt->fetchColumn() > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$adminExists) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($username) < 4) {
        $message = 'Username must have at least 4 characters.';
    } elseif (strlen($password) < 10) {
        $message = 'Password must have at least 10 characters.';
    } else {

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO admins (username, password_hash)
             VALUES (:username, :password)"
        );

        $stmt->execute([
            'username' => $username,
            'password' => $passwordHash
        ]);

        $message = 'Admin account created successfully!';
        $adminExists = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin | MAISONÉ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<main style="max-width:420px; margin:100px auto; padding:25px;">

    <h1>MAISONÉ.</h1>
    <p>Administrator Setup</p>

    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if (!$adminExists): ?>

        <form method="POST">

            <label>Username</label>
            <input
                type="text"
                name="username"
                required
                minlength="4"
                autocomplete="username"
            >

            <br><br>

            <label>Password</label>
            <input
                type="password"
                name="password"
                required
                minlength="10"
                autocomplete="new-password"
            >

            <br><br>

            <button type="submit">CREATE ADMIN ACCOUNT</button>

        </form>

    <?php else: ?>

        <p>An administrator already exists.</p>

    <?php endif; ?>

</main>

</body>
</html>
