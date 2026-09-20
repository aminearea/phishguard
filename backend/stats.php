<?php
require __DIR__ . '/config.php';

$range = isset($_GET['range']) ? $_GET['range'] : '7';

try {
    $pdo = db();

    $total  = (int)$pdo->query("SELECT COUNT(*) FROM scans")->fetchColumn();
    $high   = (int)$pdo->query("SELECT COUNT(*) FROM scans WHERE risk_level='high'")->fetchColumn();
    $medium = (int)$pdo->query("SELECT COUNT(*) FROM scans WHERE risk_level='medium'")->fetchColumn();
    $low    = (int)$pdo->query("SELECT COUNT(*) FROM scans WHERE risk_level='low'")->fetchColumn();

    // Daily data based on range
    if ($range === 'today') {
        // Hourly breakdown for today
        $daily = $pdo->query("
          SELECT HOUR(created_at) as day,
                 SUM(risk_level='high')   as high,
                 SUM(risk_level='medium') as medium,
                 SUM(risk_level='low')    as low
          FROM scans
          WHERE DATE(created_at) = CURDATE()
          GROUP BY HOUR(created_at)
          ORDER BY day ASC
        ")->fetchAll();
    } elseif ($range === '30') {
        $daily = $pdo->query("
          SELECT DATE(created_at) as day,
                 SUM(risk_level='high')   as high,
                 SUM(risk_level='medium') as medium,
                 SUM(risk_level='low')    as low
          FROM scans
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          GROUP BY DATE(created_at)
          ORDER BY day ASC
        ")->fetchAll();
    } else {
        // Default: last 7 days
        $daily = $pdo->query("
          SELECT DATE(created_at) as day,
                 SUM(risk_level='high')   as high,
                 SUM(risk_level='medium') as medium,
                 SUM(risk_level='low')    as low
          FROM scans
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          GROUP BY DATE(created_at)
          ORDER BY day ASC
        ")->fetchAll();
    }

    // Top risky domains
    $topDomains = $pdo->query("
      SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(url, '/', 3), '/', -1) as domain,
             COUNT(*) as count
      FROM scans
      WHERE risk_level='high'
      GROUP BY domain
      ORDER BY count DESC
      LIMIT 5
    ")->fetchAll();

    jsonResponse([
        'success' => true,
        'range' => $range,
        'stats' => [
            'total'  => $total,
            'high'   => $high,
            'medium' => $medium,
            'low'    => $low
        ],
        'daily' => $daily,
        'topDomains' => $topDomains
    ]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Query failed'], 500);
}