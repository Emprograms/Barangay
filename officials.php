<?php
header('Content-Type: application/json');
require_once '../db_connection.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $result = $conn->query('SELECT id, full_name, position FROM officials ORDER BY created_at ASC');
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit;
    }

    $officials = [];
    while ($row = $result->fetch_assoc()) {
        $officials[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $officials]);
    exit;
}

if ($method === 'POST') {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['full_name'] ?? '');
    $position = trim($data['position'] ?? '');

    if (!$name || !$position) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Name and position are required']);
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO officials (full_name, position, created_at) VALUES (?, ?, NOW())');
    $stmt->bind_param('ss', $name, $position);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Official added']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid request']);
?>
