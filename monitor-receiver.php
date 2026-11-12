
<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'POST required']));
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid JSON']));
}

$snapshot = [
    'updated_at' => date('c'),
    'count' => count($data),
    'events' => $data
];

$file = __DIR__ . '/monitor-data.json';

if (file_put_contents($file, json_encode($snapshot, JSON_PRETTY_PRINT), LOCK_EX) === false) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Unable to store latest results']));
}

echo json_encode([
    'success' => true,
    'message' => 'Latest monitoring results updated',
    'received_count' => count($data)
]);
