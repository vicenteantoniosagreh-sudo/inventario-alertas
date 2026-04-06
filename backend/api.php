<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/response.php';

// Validación de Autorización Bearer JWT
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (empty($authHeader) && function_exists('apache_request_headers')) {
    $requestHeaders = apache_request_headers();
    $authHeader = $requestHeaders['Authorization'] ?? '';
}

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    response(['error' => 'No autorizado. Se requiere token Bearer.'], 401);
}

$token = $matches[1];

$stmtAuth = $pdo->prepare('SELECT id, usuario, rol, token_expira FROM usuarios WHERE token = :token');
$stmtAuth->execute([':token' => $token]);
$authUser = $stmtAuth->fetch();

if (!$authUser) {
    response(['error' => 'No autorizado. Token inválido.'], 401);
}
if (!$authUser['token_expira'] || new DateTime() > new DateTime($authUser['token_expira'])) {
    response(['error' => 'No autorizado. Sesión expirada. Vuelve a iniciar sesión.'], 401);
}

// ID del usuario autenticado, disponible para todo el script
$uid = $authUser['id'];

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
    $query = $pdo->prepare(
        'SELECT id, nombre, sku, cantidad,
                fecha_vencimiento AS vencimiento,
                fecha_elaboracion, valor_neto, impuesto, categoria
         FROM productos
         WHERE usuario_id = :uid
         ORDER BY fecha_vencimiento ASC'
    );
    $query->execute([':uid' => $uid]);
    response($query->fetchAll());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $nombre   = trim($input['nombre'] ?? '');
    $cantidad = intval($input['cantidad'] ?? 0);
    $vencimiento = $input['vencimiento'] ?? $input['fecha_vencimiento'] ?? '';

    if (!$nombre || $cantidad <= 0 || !$vencimiento) {
        response(['error' => 'Todos los campos obligatorios deben estar completos'], 400);
    }

    $fechaObj = DateTime::createFromFormat('Y-m-d', $vencimiento);
    if (!$fechaObj) {
        response(['error' => 'Formato de fecha inválido. Usa YYYY-MM-DD'], 400);
    }

    $fecha_elaboracion = !empty($input['fecha_elaboracion']) ? $input['fecha_elaboracion'] : null;
    $valor_neto = isset($input['valor_neto']) ? floatval($input['valor_neto']) : 0;
    $impuesto   = isset($input['impuesto'])   ? floatval($input['impuesto'])   : 0;
    $categoria  = trim($input['categoria'] ?? 'General');
    $sku        = trim($input['sku'] ?? uniqid('SKU-'));

    $stmt = $pdo->prepare(
        'INSERT INTO productos
            (usuario_id, nombre, sku, cantidad, fecha_vencimiento, fecha_elaboracion, valor_neto, impuesto, categoria)
         VALUES
            (:uid, :nombre, :sku, :cantidad, :vencimiento, :elaboracion, :valor_neto, :impuesto, :categoria)'
    );

    try {
        $stmt->execute([
            ':uid'         => $uid,
            ':nombre'      => $nombre,
            ':sku'         => $sku,
            ':cantidad'    => $cantidad,
            ':vencimiento' => $vencimiento,
            ':elaboracion' => $fecha_elaboracion,
            ':valor_neto'  => $valor_neto,
            ':impuesto'    => $impuesto,
            ':categoria'   => $categoria,
        ]);
    } catch (PDOException $e) {
        error_log('[INSERT ERROR] ' . $e->getMessage());
        response(['error' => 'No se pudo guardar el producto (posible SKU duplicado)'], 400);
    }

    $newId = $pdo->lastInsertId();
    $new = $pdo->prepare(
        'SELECT id, nombre, sku, cantidad,
                fecha_vencimiento AS vencimiento,
                fecha_elaboracion, valor_neto, impuesto, categoria
         FROM productos WHERE id = :id'
    );
    $new->execute([':id' => $newId]);
    response($new->fetch(), 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $id !== null) {
    $stmt = $pdo->prepare('DELETE FROM productos WHERE id = :id AND usuario_id = :uid');
    $stmt->execute([':id' => $id, ':uid' => $uid]);

    if ($stmt->rowCount() === 0) {
        response(['error' => 'Producto no encontrado o no tienes permiso para eliminarlo'], 404);
    }
    response(['success' => true]);
}

response(['error' => 'Método no soportado'], 405);
