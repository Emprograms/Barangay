<?php
// Set JSON header FIRST
header('Content-Type: application/json; charset=utf-8');

// Prevent any output before JSON
ob_start();

require_once '../db_connection.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Validate CSRF token
function validateCSRF() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            return false;
        }
    }
    return true;
}

// Register
if ($method === 'POST' && $action === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = trim($data['name'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    $confirm = $data['confirm'] ?? '';

    // Validation
    if (!$name || !$email || !$password) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
    }

    if ($password !== $confirm) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Passwords do not match']);
        exit;
    }

    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }

    // Check if email exists
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email already registered']);
        exit;
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Insert user
    $stmt = $conn->prepare('INSERT INTO users (full_name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
    $role = 'resident';
    $stmt->bind_param('ssss', $name, $email, $password_hash, $role);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Account created successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Registration failed']);
    }
    exit;
}

// Login
if ($method === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = strtolower(trim($data['email'] ?? ''));
    $password = $data['password'] ?? '';

    if (!$email || !$password) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email and password are required']);
        exit;
    }

    $stmt = $conn->prepare('SELECT id, full_name, email, password_hash, role FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
        exit;
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
    exit;
}

// Logout
if ($method === 'POST' && $action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// Get Current User
if ($method === 'GET' && $action === 'current-user') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role']
            ]
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid request']);
?>
