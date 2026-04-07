<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no soportado']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? '';

match ($action) {
    'register' => register($pdo, $input),
    'login'    => login($pdo, $input),
    default    => (function () {
        http_response_code(404);
        echo json_encode(['error' => 'Acción no encontrada']);
        exit;
    })()
};
