<?php
// Set JSON header FIRST
header('Content-Type: application/json; charset=utf-8');

// Prevent any output before JSON
ob_start();

require_once __DIR__ . '/../db_connection.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Response helper function
function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    ob_end_flush();
    exit;
}

try {
    // REGISTER
    if ($method === 'POST' && $action === 'register') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $name = trim($input['name'] ?? '');
        $email = strtolower(trim($input['email'] ?? ''));
        $password = $input['password'] ?? '';
        $confirm = $input['confirm'] ?? '';

        // Validation
        if (!$name || !$email || !$password) {
            sendResponse(false, ['error' => 'All fields are required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse(false, ['error' => 'Invalid email format'], 400);
        }

        if ($password !== $confirm) {
            sendResponse(false, ['error' => 'Passwords do not match'], 400);
        }

        if (strlen($password) < 8) {
            sendResponse(false, ['error' => 'Password must be at least 8 characters'], 400);
        }

        // Check if email exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        if (!$stmt) {
            sendResponse(false, ['error' => 'Database error: ' . $conn->error], 500);
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $stmt->close();
            sendResponse(false, ['error' => 'Email already registered'], 400);
        }
        $stmt->close();

        // Hash password and insert user
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare('INSERT INTO users (full_name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
        if (!$stmt) {
            sendResponse(false, ['error' => 'Database error: ' . $conn->error], 500);
        }

        $role = 'resident';
        $stmt->bind_param('ssss', $name, $email, $password_hash, $role);

        if ($stmt->execute()) {
            $stmt->close();
            sendResponse(true, ['message' => 'Account created successfully']);
        } else {
            $stmt->close();
            sendResponse(false, ['error' => 'Registration failed: ' . $conn->error], 500);
        }
    }

    // LOGIN
    else if ($method === 'POST' && $action === 'login') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $email = strtolower(trim($input['email'] ?? ''));
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            sendResponse(false, ['error' => 'Email and password are required'], 400);
        }

        $stmt = $conn->prepare('SELECT id, full_name, email, password_hash, role FROM users WHERE email = ? AND is_active = 1');
        if (!$stmt) {
            sendResponse(false, ['error' => 'Database error: ' . $conn->error], 500);
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            sendResponse(false, ['error' => 'Invalid email or password'], 401);
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        sendResponse(true, [
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    }

    // LOGOUT
    else if ($method === 'POST' && $action === 'logout') {
        session_destroy();
        sendResponse(true, ['message' => 'Logout successful']);
    }

    // GET CURRENT USER
    else if ($method === 'GET' && $action === 'current-user') {
        if (isset($_SESSION['user_id'])) {
            sendResponse(true, [
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'name' => $_SESSION['user_name'],
                    'email' => $_SESSION['user_email'],
                    'role' => $_SESSION['user_role']
                ]
            ]);
        } else {
            sendResponse(false, ['error' => 'No active session'], 401);
        }
    }

    // Invalid action
    else {
        sendResponse(false, ['error' => 'Invalid request'], 400);
    }

} catch (Exception $e) {
    sendResponse(false, ['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>
