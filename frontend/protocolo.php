<?php
// Protocolo del proyecto: Sistema Web de Gestión de Inventario con Alertas de Vencimiento
// Nombre de la página: StockAlert
// Descripción: Producto para pequeños comercios y tiendas de mascotas que gestiona productos perecibles, clasifica estados y lanza alertas visuales.

$proyecto = [
    'nombre' => 'StockAlert',
    'descripcion' => 'Aplicación web para controlar inventarios con fecha de vencimiento y alertas de stock crítico.',
    'objetivo' => 'Registrar, visualizar y controlar productos con fecha de vencimiento, alertando oportunamente sobre su estado.',
    'publico' => ['Dueños de almacenes', 'Tiendas de mascotas', 'Pequeños comercios'],
    'funcionalidades' => ['Registro de productos', 'Visualización de inventario', 'Identificación de productos críticos', 'Filtros por estado', 'Eliminación de productos'],
    'tecnologia' => 'PHP + MySQL para backend, HTML/CSS/JS para frontend',
    'alcance' => 'MVP funcional en 1 día',
];

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Protocolo StockAlert</title>
</head>
<body>
    <h1>Protocolo del proyecto: <?= htmlspecialchars($proyecto['nombre']) ?></h1>
    <p><strong>Descripción:</strong> <?= htmlspecialchars($proyecto['descripcion']) ?></p>
    <p><strong>Objetivo:</strong> <?= htmlspecialchars($proyecto['objetivo']) ?></p>
    <p><strong>Público objetivo:</strong> <?= implode(', ', $proyecto['publico']) ?></p>
    <h2>Funcionalidades</h2>
    <ul>
        <?php foreach ($proyecto['funcionalidades'] as $func): ?>
            <li><?= htmlspecialchars($func) ?></li>
        <?php endforeach; ?>
    </ul>
    <h2>Tecnología</h2>
    <p><?= htmlspecialchars($proyecto['tecnologia']) ?></p>
    <h2>API PHP</h2>
    <ul>
        <li>GET <code>backend/api.php/products</code> - Listar productos</li>
        <li>POST <code>backend/api.php/products</code> - Agregar producto</li>
        <li>DELETE <code>backend/api.php/products/{id}</code> - Eliminar producto</li>
    </ul>
</body>
</html>