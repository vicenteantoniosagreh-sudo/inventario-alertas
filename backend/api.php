<?php
// API PHP para alerta-inventario (MySQL)
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

$host = '127.0.0.1';
$db   = 'alerta-inventario';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión DB: ' . $e->getMessage()]);
    exit;
}

// Asegura que exista la tabla
$pdo->exec("CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    sku VARCHAR(80) NOT NULL UNIQUE,
    cantidad INT NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    categoria VARCHAR(120) DEFAULT 'General',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

function response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$resource = $_GET['resource'] ?? null;
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$resource) {
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $parts = explode('/', $path);
    $last = end($parts);
    if ($last === 'products' || $last === 'productos') {
        $resource = $last;
    }
}

if ($resource !== 'productos' && $resource !== 'products') {
    response(['error' => 'Recurso no encontrado'], 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = $pdo->query('SELECT id, nombre, sku, cantidad, fecha_vencimiento AS vencimiento, categoria FROM productos ORDER BY fecha_vencimiento ASC');
    $all = $query->fetchAll();
    response($all);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $nombre = trim($input['nombre'] ?? '');
    $cantidad = intval($input['cantidad'] ?? 0);
    $vencimiento = $input['vencimiento'] ?? $input['fecha_vencimiento'] ?? '';
    $categoria = trim($input['categoria'] ?? 'General');
    $sku = trim($input['sku'] ?? uniqid('SKU-'));

    if (!$nombre || $cantidad <= 0 || !$vencimiento) {
        response(['error' => 'Todos los campos son obligatorios'], 400);
    }

    $stmt = $pdo->prepare('INSERT INTO productos (nombre, sku, cantidad, fecha_vencimiento, categoria) VALUES (:nombre, :sku, :cantidad, :fecha_vencimiento, :categoria)');
    try {
        $stmt->execute([':nombre' => $nombre, ':sku' => $sku, ':cantidad' => $cantidad, ':fecha_vencimiento' => $vencimiento, ':categoria' => $categoria]);
    } catch (PDOException $e) {
        response(['error' => 'No se pudo guardar (posible SKU duplicado)'], 400);
    }

    $newId = $pdo->lastInsertId();
    $new = $pdo->prepare('SELECT id, nombre, sku, cantidad, fecha_vencimiento AS vencimiento, categoria FROM productos WHERE id = :id');
    $new->execute([':id' => $newId]);
    response($new->fetch(), 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $id !== null) {
    $stmt = $pdo->prepare('DELETE FROM productos WHERE id = :id');
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) {
        response(['error' => 'Producto no encontrado'], 404);
    }

    response(['success' => true]);
}

response(['error' => 'Método no soportado'], 405);
