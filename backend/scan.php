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

$url    = trim($data['url'] ?? '');
$score  = (int)($data['score'] ?? 0);
$level  = $data['level'] ?? 'low';
$reasons = $data['reasons'] ?? [];

if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
    jsonResponse(['success' => false, 'error' => 'Invalid URL'], 400);
}

// Validate level
if (!in_array($level, ['low', 'medium', 'high', 'unknown'])) {
    $level = 'low';
}

// Clamp score
$score = max(0, min(100, $score));

// Store reasons as a JSON string
$reasonsJson = json_encode($reasons, JSON_UNESCAPED_UNICODE);

try {
    $stmt = db()->prepare(
        "INSERT INTO scans (url, risk_score, risk_level, reasons) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$url, $score, $level, $reasonsJson]);
    jsonResponse(['success' => true, 'id' => db()->lastInsertId()]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Insert failed'], 500);
}