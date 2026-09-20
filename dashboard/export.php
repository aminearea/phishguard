<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

require __DIR__ . '/../backend/config.php';

try {
    $rows = db()->query("
        SELECT id, url, risk_score, risk_level, reasons, created_at
        FROM scans
        ORDER BY id DESC
    ")->fetchAll();
} catch (Exception $e) {
    die("Export failed.");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=phishguard_scans_' . date('Y-m-d') . '.csv');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // BOM for Excel UTF-8

fputcsv($out, ['ID', 'URL', 'Score', 'Level', 'Reasons', 'Date']);
foreach ($rows as $r) {
    $reasons = json_decode($r['reasons'], true) ?: [];
    fputcsv($out, [
        $r['id'],
        $r['url'],
        $r['risk_score'],
        $r['risk_level'],
        implode(' | ', $reasons),
        $r['created_at']
    ]);
}
fclose($out);