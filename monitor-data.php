
<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$file = __DIR__ . '/monitor-data.json';

if (!file_exists($file)) {
    echo json_encode([
        'updated_at' => null,
        'count' => 0,
        'events' => []
    ]);
    exit;
}

readfile($file);
