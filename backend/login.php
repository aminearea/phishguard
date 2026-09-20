<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    jsonResponse(['success' => false, 'error' => 'Invalid JSON'], 400);
}

$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if ($username === '' || $password === '') {
    jsonResponse(['success' => false, 'error' => 'Missing credentials'], 400);
}

try {
    $stmt = db()->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        jsonResponse(['success' => false, 'error' => 'Invalid credentials'], 401);
    }

    jsonResponse(['success' => true, 'admin' => ['id' => $admin['id'], 'username' => $admin['username']]]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Query failed'], 500);
}