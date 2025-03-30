<?php
// Force JSON response with no BOM or whitespace
ob_clean();
header('Content-Type: application/json; charset=utf-8');

// Simple JSON response
echo json_encode([
    'success' => true,
    'message' => 'This is a test JSON response',
    'time' => time()
]);
exit;
?> 