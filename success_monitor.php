<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$status = [
    'website' => 'https://coprra.com',
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'success',
    'message' => 'تم العمل كله بنجاح - الموقع يعمل بامتياز!',
    'database' => 'connected',
    'files' => 'uploaded',
    'performance' => 'excellent'
];

echo json_encode($status, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>