<?php
session_start();
if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Call login API
    $payload = json_encode(['username' => $username, 'password' => $password]);
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);

    $response = @file_get_contents('http://localhost/phishguard/backend/login.php', false, $ctx);
    $data = $response ? json_decode($response, true) : null;

    if ($data && !empty($data['success'])) {
        $_SESSION['admin'] = $data['admin']['username'];
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
  <meta charset="UTF-8" />
  <title>PhishGuard — Login</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="login-wrap">
    <form class="login-card" method="POST">
      <h1><img src="../extension/icons/icon128.png" alt="" class="login-logo" /> PhishGuard</h1>
      <p>Admin dashboard login</p>

      <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <input type="text" name="username" placeholder="Username" required autofocus />
      <input type="password" name="password" placeholder="Password" required />
      <button type="submit">Sign in</button>
    </form>
  </div>
</body>
</html>