<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();

require_once __DIR__ . '/../db_connection.php';

function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    ob_end_flush();
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, ['error' => 'Unauthorized'], 401);
    }

    // GET Clearances
    if ($method === 'GET') {
        $user_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare('SELECT id, purpose, status, created_at, DATE_ADD(created_at, INTERVAL 6 MONTH) as expires_at FROM clearances WHERE user_id = ? ORDER BY created_at DESC');
        if (!$stmt) {
            sendResponse(false, ['error' => $conn->error], 500);
        }

        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $clearances = [];
        while ($row = $result->fetch_assoc()) {
            $clearances[] = $row;
        }
        $stmt->close();

        sendResponse(true, ['data' => $clearances]);
    }

    // CREATE Clearance Request
    else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $user_id = $_SESSION['user_id'];
        $purpose = trim($input['purpose'] ?? '');

        if (!$purpose) {
            sendResponse(false, ['error' => 'Purpose is required'], 400);
        }

        $stmt = $conn->prepare('INSERT INTO clearances (user_id, purpose, status, created_at) VALUES (?, ?, ?, NOW())');
        if (!$stmt) {
            sendResponse(false, ['error' => $conn->error], 500);
        }

        $status = 'pending';
        $stmt->bind_param('iss', $user_id, $purpose, $status);

        if ($stmt->execute()) {
            $stmt->close();
            sendResponse(true, ['message' => 'Clearance request submitted successfully', 'id' => $conn->insert_id]);
        } else {
            $stmt->close();
            sendResponse(false, ['error' => $conn->error], 500);
        }
    }

    else {
        sendResponse(false, ['error' => 'Method not allowed'], 405);
    }

} catch (Exception $e) {
    sendResponse(false, ['error' => $e->getMessage()], 500);
}
?>
