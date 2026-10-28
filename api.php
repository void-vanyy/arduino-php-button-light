<?php

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$action = $_GET['action'] ?? 'command';

if ($action === 'state') {
    echo json_encode(loadState());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    $input = $_POST;
}

$key = strtolower((string) ($input['command'] ?? ''));
$map = ['on' => '1', 'off' => '0', '1' => '1', '0' => '0'];

if (!isset($map[$key])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown command.']);
    exit;
}

$result = sendArduinoCommand($map[$key]);

$state = loadState();

if ($result['success']) {
    $state['light'] = $map[$key] === '1' ? 'ON' : 'OFF';
}

recordEvent($state, 'WEB', $result['message'], $result['success']);
saveState($state);

echo json_encode(array_merge($result, [
    'light'   => $state['light'],
    'events'  => $state['events'],
]));
