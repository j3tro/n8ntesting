
<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode([
        'success' => false,
        'message' => 'POST method required'
    ]));
}

// Receive JSON from n8n
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data) || empty($data)) {
    http_response_code(400);
    exit(json_encode([
        'success' => false,
        'message' => 'Invalid or empty JSON data'
    ]));
}

// Validate monitoring records
foreach ($data as $item) {
    if (
        !isset($item['source'], $item['symbol'], $item['price']) ||
        !is_numeric($item['price'])
    ) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'message' => 'Invalid monitoring record'
        ]));
    }
}

// Prepare latest monitoring snapshot
$snapshot = [
    'source' => 'Binance',
    'updated_at' => date('c'),
    'count' => count($data),
    'events' => $data
];

// Save latest data
$file = __DIR__ . '/monitor-data.json';

$json = json_encode(
    $snapshot,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);

if ($json === false || file_put_contents($file, $json, LOCK_EX) === false) {
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'message' => 'Unable to save monitoring data'
    ]));
}

// Return confirmation to n8n
echo json_encode([
    'success' => true,
    'message' => 'Binance monitoring data updated',
    'source' => 'Binance',
    'received_count' => count($data),
    'symbol' => $data[0]['symbol'],
    'price' => (float) $data[0]['price'],
    'updated_at' => $snapshot['updated_at']
]);
