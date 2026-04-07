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

// Endpoint para obtener los 5 productos más vencidos
if ($resource === 'top-expired' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = $pdo->prepare('SELECT nombre, cantidad, fecha_vencimiento FROM productos WHERE usuario_id = :uid ORDER BY fecha_vencimiento ASC LIMIT 5');
    $query->execute([':uid' => $uid]);
    $topExpired = $query->fetchAll();
    response($topExpired);
}

if ($resource !== 'productos' && $resource !== 'products') {
    response(['error' => 'Recurso no encontrado'], 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['export']) && in_array($_GET['export'], ['csv', 'excel', 'xlsx'], true)) {
        $query = $pdo->prepare('SELECT id, nombre, sku, cantidad, fecha_vencimiento AS vencimiento, fecha_elaboracion, valor_neto, impuesto, categoria FROM productos WHERE usuario_id = :uid ORDER BY nombre ASC');
        $query->execute([':uid' => $uid]);
        $productos = $query->fetchAll();

        if ($_GET['export'] === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=inventario_completo.csv');
            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ID', 'Nombre', 'SKU', 'Cantidad', 'Vencimiento', 'Elaboración', 'Valor Neto', 'Impuesto', 'Categoría'], ';');
            foreach ($productos as $producto) {
                fputcsv($output, [$producto['id'], $producto['nombre'], $producto['sku'], $producto['cantidad'], $producto['vencimiento'], $producto['fecha_elaboracion'] ?: '', $producto['valor_neto'], $producto['impuesto'], $producto['categoria']], ';');
            }
            fclose($output);
            exit;
        }

        if ($_GET['export'] === 'excel' && !class_exists('ZipArchive')) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=inventario_completo.csv');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Nombre', 'SKU', 'Cantidad', 'Vencimiento', 'Elaboración', 'Valor Neto', 'Impuesto', 'Categoría']);
            foreach ($productos as $producto) {
                fputcsv($output, [$producto['id'], $producto['nombre'], $producto['sku'], $producto['cantidad'], $producto['vencimiento'], $producto['fecha_elaboracion'] ?: '', $producto['valor_neto'], $producto['impuesto'], $producto['categoria']]);
            }
            fclose($output);
            exit;
        }

        // XLSX minimal implementation
        $filename = 'inventario_completo.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        if (!function_exists('escapeXml')) {
            function escapeXml($value) {
                return str_replace(['&', '<', '>', '"', "'"], ['&amp;', '&lt;', '&gt;', '&quot;', '&apos;'], (string)$value);
            }
        }

        $sheetRows = '';
        $headers = ['ID', 'Nombre', 'SKU', 'Cantidad', 'Vencimiento', 'Elaboración', 'Valor Neto', 'Impuesto', 'Categoría'];
        
        // Header row
        $sheetRows .= '<row r="1">';
        foreach ($headers as $colIndex => $val) {
            $colLetter = chr(65 + $colIndex);
            $sheetRows .= '<c r="' . $colLetter . '1" t="inlineStr"><is><t>' . escapeXml($val) . '</t></is></c>';
        }
        $sheetRows .= '</row>';

        // Data rows
        foreach ($productos as $rowIndex => $p) {
            $rNum = $rowIndex + 2;
            $sheetRows .= '<row r="' . $rNum . '">';
            $vals = [$p['id'], $p['nombre'], $p['sku'], $p['cantidad'], $p['vencimiento'], $p['fecha_elaboracion'] ?: '', $p['valor_neto'], $p['impuesto'], $p['categoria']];
            foreach ($vals as $colIndex => $v) {
                $colLetter = chr(65 + $colIndex);
                if (is_numeric($v) && $colIndex != 2) { // SKU (index 2) as string
                    $sheetRows .= '<c r="' . $colLetter . $rNum . '"><v>' . $v . '</v></c>';
                } else {
                    $sheetRows .= '<c r="' . $colLetter . $rNum . '" t="inlineStr"><is><t>' . escapeXml($v) . '</t></is></c>';
                }
            }
            $sheetRows .= '</row>';
        }

        $contentTypes = '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
        $rels = '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="/xl/workbook.xml"/></Relationships>';
        $workbook = '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Inventario" sheetId="1" r:id="rId1"/></sheets></workbook>';
        $workbookRels = '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $sheetXml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $sheetRows . '</sheetData></worksheet>';
        $stylesXml = '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/></border></borders><cellXfs count="1"><xf fontId="0" fillId="0" borderId="0"/></cellXfs></styleSheet>';

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::CREATE) === true) {
            $zip->addFromString('[Content_Types].xml', $contentTypes);
            $zip->addFromString('_rels/.rels', $rels);
            $zip->addFromString('xl/workbook.xml', $workbook);
            $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
            $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
            $zip->addFromString('xl/styles.xml', $stylesXml);
            $zip->close();
            readfile($tmpFile);
            unlink($tmpFile);
            exit;
        }
    }

    $query = $pdo->prepare('SELECT id, nombre, sku, cantidad, fecha_vencimiento AS vencimiento, fecha_elaboracion, valor_neto, impuesto, categoria FROM productos WHERE usuario_id = :uid ORDER BY fecha_vencimiento ASC');
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
