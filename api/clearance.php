<?php
header('Content-Type: application/json');
require_once '../db_connection.php';

$method = $_SERVER['REQUEST_METHOD'];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get user's clearance requests
if ($method === 'GET') {
    $user_id = $_SESSION['user_id'];
    
    $result = $conn->query("SELECT id, purpose, status, created_at, DATE_ADD(created_at, INTERVAL 6 MONTH) as expires_at FROM clearances WHERE user_id = $user_id ORDER BY created_at DESC");
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit;
    }

    $clearances = [];
    while ($row = $result->fetch_assoc()) {
        $clearances[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $clearances]);
    exit;
}

// Request clearance
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $_SESSION['user_id'];
    $purpose = trim($data['purpose'] ?? '');

    if (!$purpose) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Purpose is required']);
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO clearances (user_id, purpose, status, created_at) VALUES (?, ?, ?, NOW())');
    $status = 'pending';
    $stmt->bind_param('iss', $user_id, $purpose, $status);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Clearance request submitted']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid request']);
?>
