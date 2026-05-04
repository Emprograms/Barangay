<?php
require_once __DIR__ . '/config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'error' => 'Database connection failed',
            'message' => $conn->connect_error
        ]));
    }
    
    // Set charset
    if (!$conn->set_charset("utf8mb4")) {
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'error' => 'Charset setting failed'
        ]));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'Database connection error',
        'message' => $e->getMessage()
    ]));
}
?>
