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
    fecha_elaboracion DATE NULL,
    valor_neto DECIMAL(10,2) DEFAULT 0,
    impuesto DECIMAL(5,2) DEFAULT 0,
    categoria VARCHAR(120) DEFAULT 'General',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Migración automática para prototipos (agrega columnas si no existen)
try {
    $pdo->exec("ALTER TABLE productos ALTER COLUMN Precio SET DEFAULT 0"); // Repara el campo Precio para que no exija un valor
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE productos ADD COLUMN fecha_elaboracion DATE NULL AFTER fecha_vencimiento");
    $pdo->exec("ALTER TABLE productos ADD COLUMN valor_neto DECIMAL(10,2) DEFAULT 0 AFTER fecha_elaboracion");
    $pdo->exec("ALTER TABLE productos ADD COLUMN impuesto DECIMAL(5,2) DEFAULT 0 AFTER valor_neto");
} catch (PDOException $e) {}

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
    if (isset($_GET['export']) && in_array($_GET['export'], ['csv', 'excel', 'xlsx'], true)) {
        $query = $pdo->query('SELECT id, nombre, sku, cantidad, fecha_vencimiento AS vencimiento, fecha_elaboracion, valor_neto, impuesto, categoria FROM productos ORDER BY nombre ASC');
        $productos = $query->fetchAll();

        if ($_GET['export'] === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=inventario_completo.csv');

            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ID', 'Nombre', 'SKU', 'Cantidad', 'Vencimiento', 'Elaboración', 'Valor Neto', 'Impuesto', 'Categoría'], ';');

            foreach ($productos as $producto) {
                fputcsv($output, [
                    $producto['id'],
                    $producto['nombre'],
                    $producto['sku'],
                    $producto['cantidad'],
                    $producto['vencimiento'],
                    $producto['fecha_elaboracion'] ?: '',
                    $producto['valor_neto'],
                    $producto['impuesto'],
                    $producto['categoria']
                ], ';');
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
                fputcsv($output, [
                    $producto['id'],
                    $producto['nombre'],
                    $producto['sku'],
                    $producto['cantidad'],
                    $producto['vencimiento'],
                    $producto['fecha_elaboracion'] ?: '',
                    $producto['valor_neto'],
                    $producto['impuesto'],
                    $producto['categoria']
                ]);
            }

            fclose($output);
            exit;
        }

        // Exportar a XLSX simple sin librerías externas
        $filename = 'inventario_completo.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        function escapeXml($value) {
            return str_replace(['&', '<', '>', '"', "'"], ['&amp;', '&lt;', '&gt;', '&quot;', '&apos;'], $value);
        }

        $rows = [];
        $rows[] = ['ID', 'Nombre', 'SKU', 'Cantidad', 'Vencimiento', 'Elaboración', 'Valor Neto', 'Impuesto', 'Categoría'];

        foreach ($productos as $producto) {
            $rows[] = [
                $producto['id'],
                $producto['nombre'],
                $producto['sku'],
                $producto['cantidad'],
                $producto['vencimiento'],
                $producto['fecha_elaboracion'] ?: '',
                $producto['valor_neto'],
                $producto['impuesto'],
                $producto['categoria']
            ];
        }

        $sheetRows = '';
        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $sheetRows .= '<row r="' . $rowNumber . '">';
            foreach ($row as $colIndex => $cellValue) {
                $colLetter = chr(65 + $colIndex);
                $cellRef = $colLetter . $rowNumber;
                if ($rowIndex > 0 && is_numeric($cellValue)) {
                    $sheetRows .= '<c r="' . $cellRef . '"><v>' . $cellValue . '</v></c>';
                } else {
                    $sheetRows .= '<c r="' . $cellRef . '" t="inlineStr"><is><t>' . escapeXml((string)$cellValue) . '</t></is></c>';
                }
            }
            $sheetRows .= '</row>';
        }

        $contentTypes = '<?xml version="1.0" encoding="UTF-8"?>\n<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">\n    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>\n    <Default Extension="xml" ContentType="application/xml"/>\n    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>\n    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>\n    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>\n</Types>';
        $rels = '<?xml version="1.0" encoding="UTF-8"?>\n<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">\n    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="/xl/workbook.xml"/>\n</Relationships>';
        $workbook = '<?xml version="1.0" encoding="UTF-8"?>\n<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">\n    <sheets>\n        <sheet name="Inventario" sheetId="1" r:id="rId1"/>\n    </sheets>\n</workbook>';
        $workbookRels = '<?xml version="1.0" encoding="UTF-8"?>\n<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">\n    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>\n    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>\n</Relationships>';
        $sheetXml = '<?xml version="1.0" encoding="UTF-8"?>\n<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">\n    <sheetData>\n' . $sheetRows . '\n    </sheetData>\n</worksheet>';
        $stylesXml = '<?xml version="1.0" encoding="UTF-8"?>\n<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">\n    <fonts count="1">\n        <font>\n            <sz val="11"/>\n            <color theme="1"/>\n            <name val="Calibri"/>\n        </font>\n    </fonts>\n    <fills count="2">\n        <fill>\n            <patternFill patternType="none"/>\n        </fill>\n        <fill>\n            <patternFill patternType="gray125"/>\n        </fill>\n    </fills>\n    <borders count="1">\n        <border>\n            <left/>\n            <right/>\n            <top/>\n            <bottom/>\n            <diagonal/>\n        </border>\n    </borders>\n    <cellStyleXfs count="1">\n        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>\n    </cellStyleXfs>\n    <cellXfs count="1">\n        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>\n    </cellXfs>\n    <cellStyles count="1">\n        <cellStyle name="Normal" xfId="0" builtinId="0"/>\n    </cellStyles>\n</styleSheet>';

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::CREATE) !== true) {
            response(['error' => 'No se pudo crear el archivo XLSX'], 500);
        }

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

    $query = $pdo->query('SELECT id, nombre, sku, cantidad, fecha_vencimiento AS vencimiento, fecha_elaboracion, valor_neto, impuesto, categoria FROM productos ORDER BY fecha_vencimiento ASC');
    $all = $query->fetchAll();
    response($all);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $nombre = trim($input['nombre'] ?? '');
    $cantidad = intval($input['cantidad'] ?? 0);
    $vencimiento = $input['vencimiento'] ?? $input['fecha_vencimiento'] ?? '';
    
    // Nuevos campos para Ampliación Financiera
    $fecha_elaboracion = !empty($input['fecha_elaboracion']) ? $input['fecha_elaboracion'] : null;
    $valor_neto = isset($input['valor_neto']) ? floatval($input['valor_neto']) : 0;
    $impuesto = isset($input['impuesto']) ? floatval($input['impuesto']) : 0;
    
    $categoria = trim($input['categoria'] ?? 'General');
    $sku = trim($input['sku'] ?? uniqid('SKU-'));

    if (!$nombre || $cantidad <= 0 || !$vencimiento) {
        response(['error' => 'Todos los campos obligatorios deben estar completos'], 400);
    }

    $stmt = $pdo->prepare('INSERT INTO productos (nombre, sku, cantidad, fecha_vencimiento, fecha_elaboracion, valor_neto, impuesto, categoria) VALUES (:nombre, :sku, :cantidad, :fecha_vencimiento, :fecha_elaboracion, :valor_neto, :impuesto, :categoria)');
    try {
        $stmt->execute([
            ':nombre' => $nombre, 
            ':sku' => $sku, 
            ':cantidad' => $cantidad, 
            ':fecha_vencimiento' => $vencimiento, 
            ':fecha_elaboracion' => $fecha_elaboracion,
            ':valor_neto' => $valor_neto,
            ':impuesto' => $impuesto,
            ':categoria' => $categoria
        ]);
    } catch (PDOException $e) {
        response(['error' => 'No se pudo guardar (posible SKU duplicado) - ' . $e->getMessage()], 400);
    }

    $newId = $pdo->lastInsertId();
    $new = $pdo->prepare('SELECT id, nombre, sku, cantidad, fecha_vencimiento AS vencimiento, fecha_elaboracion, valor_neto, impuesto, categoria FROM productos WHERE id = :id');
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
