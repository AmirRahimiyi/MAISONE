
<?php

session_start();

require_once '../config/database.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT id, username, password_hash
         FROM admins
         WHERE username = :username
         LIMIT 1"
    );

    $stmt->execute(['username' => $username]);

    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {

        session_regenerate_id(true);

        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        header('Location: dashboard.php');
        exit;

    } else {
        $error = 'Invalid username or password.';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login | MAISONÉ</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .login-container {
            max-width: 420px;
            margin: 100px auto;
            padding: 35px;
            background: white;
            border: 1px solid #e8e5df;
        }

        .login-container h1 {
            font-family: 'Playfair Display', serif;
            font-size: 38px;
            margin-bottom: 10px;
        }

        .login-container p {
            color: #77746e;
            margin-bottom: 30px;
        }

        .login-container input {
            width: 100%;
            padding: 14px;
            margin: 10px 0 22px;
            border: 1px solid #e8e5df;
            outline: none;
        }

        .login-container button {
            width: 100%;
            padding: 15px;
            background: #24231f;
            color: white;
            border: none;
            cursor: pointer;
        }

        .login-error {
            color: #b42318;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

<div class="login-container">

    <h1>MAISONÉ.</h1>

    <p>Administrator access</p>

    <?php if ($error): ?>
        <div class="login-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Username</label>

        <input
            type="text"
            name="username"
            required
            autocomplete="username"
        >

        <label>Password</label>

        <input
            type="password"
            name="password"
            required
            autocomplete="current-password"
        >

        <button type="submit">
            SIGN IN
        </button>

    </form>

</div>

</body>
</html>
