<?php
require __DIR__ . '/config.php';

$limit  = isset($_GET['limit'])  ? (int)$_GET['limit']  : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$search = isset($_GET['q'])      ? trim($_GET['q'])     : '';
$level  = isset($_GET['level'])  ? trim($_GET['level']) : '';

$limit  = max(1, min($limit, 200));
$offset = max(0, $offset);

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "url LIKE ?";
    $params[] = "%$search%";
}
if (in_array($level, ['low', 'medium', 'high'])) {
    $where[] = "risk_level = ?";
    $params[] = $level;
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

try {
    // Count total
    $countStmt = db()->prepare("SELECT COUNT(*) FROM scans $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Fetch page
    $stmt = db()->prepare("
        SELECT id, url, risk_score, risk_level, reasons, created_at
        FROM scans
        $whereSql
        ORDER BY id DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['reasons'] = json_decode($row['reasons'], true) ?: [];
    }

    jsonResponse([
        'success' => true,
        'total'   => $total,
        'count'   => count($rows),
        'scans'   => $rows
    ]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Query failed'], 500);
}