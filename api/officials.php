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

    // GET Officials
    if ($method === 'GET') {
        $result = $conn->query('SELECT id, full_name, position, contact_info FROM officials ORDER BY id ASC');
        
        if (!$result) {
            sendResponse(false, ['error' => $conn->error], 500);
        }

        $officials = [];
        while ($row = $result->fetch_assoc()) {
            $officials[] = $row;
        }

        sendResponse(true, ['data' => $officials]);
    }

    // CREATE Official (Admin only)
    else if ($method === 'POST') {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            sendResponse(false, ['error' => 'Unauthorized'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $name = trim($input['full_name'] ?? '');
        $position = trim($input['position'] ?? '');
        $contact = trim($input['contact_info'] ?? '');

        if (!$name || !$position) {
            sendResponse(false, ['error' => 'Name and position are required'], 400);
        }

        $stmt = $conn->prepare('INSERT INTO officials (full_name, position, contact_info, created_at) VALUES (?, ?, ?, NOW())');
        if (!$stmt) {
            sendResponse(false, ['error' => $conn->error], 500);
        }

        $stmt->bind_param('sss', $name, $position, $contact);

        if ($stmt->execute()) {
            $stmt->close();
            sendResponse(true, ['message' => 'Official added successfully', 'id' => $conn->insert_id]);
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
