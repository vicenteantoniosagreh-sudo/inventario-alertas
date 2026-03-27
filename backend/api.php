<?php
// API simple en PHP para inventario con alertas de vencimiento
// Usa SQLite en database/inventario.sqlite (se crea automáticamente)

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

$dsn = 'sqlite:' . __DIR__ . '/../database/inventario.sqlite';
try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        cantidad INTEGER NOT NULL,
        vencimiento TEXT NOT NULL
    )');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de Base de Datos: ' . $e->getMessage()]);
    exit;
}

// Soporta /backend/api.php?resource=products y /backend/api.php/products
$resource = $_GET['resource'] ?? null;
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$resource) {
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $base = dirname($_SERVER['SCRIPT_NAME']);
    $raw = trim(substr($path, strlen($base)), '/');
    $parts = array_values(array_filter(explode('/', $raw)));

    if (count($parts) >= 1 && $parts[0] === 'products') {
        $resource = 'products';
        if (isset($parts[1])) {
            $id = intval($parts[1]);
        }
    }
}

function response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($resource !== 'products') {
    response(['error' => 'Recurso no encontrado'], 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = $pdo->query('SELECT * FROM products ORDER BY vencimiento ASC, nombre ASC');
    $all = $query->fetchAll(PDO::FETCH_ASSOC);
    response($all);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $nombre = trim($input['nombre'] ?? '');
    $cantidad = intval($input['cantidad'] ?? 0);
    $vencimiento = $input['vencimiento'] ?? '';

    if (!$nombre || $cantidad <= 0 || !$vencimiento) {
        response(['error' => 'Todos los campos son obligatorios'], 400);
    }

    $stmt = $pdo->prepare('INSERT INTO products (nombre, cantidad, vencimiento) VALUES (:nombre, :cantidad, :vencimiento)');
    $stmt->execute([':nombre' => $nombre, ':cantidad' => $cantidad, ':vencimiento' => $vencimiento]);
    $newId = $pdo->lastInsertId();

    $new = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $new->execute([':id' => $newId]);
    response($new->fetch(PDO::FETCH_ASSOC), 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $id !== null) {
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute([':id' => intval($id)]);

    if ($stmt->rowCount() === 0) {
        response(['error' => 'Producto no encontrado'], 404);
    }

    response(['success' => true]);
}

response(['error' => 'Método no soportado'], 405);
