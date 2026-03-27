<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="AniGuard Inventario: gestión con alertas de vencimiento para negocios pequeños." />
    <title>AniGuard Inventario | Control y Alertas</title>
    <link rel="stylesheet" href="css/style.css" />
</head>

<body>
    <div class="app-shell">
        <header class="topbar">
            <div class="brand">AniGuard Inventario</div>
            <p>Sistema Web de Gestión de Inventario con Alertas de Vencimiento</p>
        </header>

        <main class="container">
            <section class="panel form-panel">
                <h2>Registrar Producto</h2>
                <form id="productForm" class="form-grid">
                    <label>
                        Nombre
                        <input type="text" id="nombre" placeholder="Ej. Hueso 500g" required />
                    </label>
                    <label>
                        Cantidad
                        <input type="number" min="1" id="cantidad" placeholder="Ej. 10" required />
                    </label>
                    <label>
                        Vencimiento
                        <input type="date" id="vencimiento" required />
                    </label>
                    <label>
                        Stock mínimo crítico
                        <input type="number" min="1" id="stockCritico" value="5" />
                    </label>
                    <button type="submit" class="btn-primary">Agregar producto</button>
                </form>
                <div id="mensaje" class="alert"></div>
            </section>

            <section class="panel list-panel">
                <div class="toolbar">
                    <h2>Inventario</h2>
                    <div class="filters">
                        <label>
                            Ver:
                            <select id="filterStatus">
                                <option value="all">Todos</option>
                                <option value="vigente">Vigentes</option>
                                <option value="por_vencer">Por vencer</option>
                                <option value="vencido">Vencidos</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="stats" id="stats"></div>
                <div id="productsGrid" class="products-grid"></div>
            </section>
        </main>

        <footer class="footer">
            <span>Prototipo de gestión de inventario - 2026</span>
            <span>Alertas por vencimiento/autoclasificación y control de stock.</span>
        </footer>
    </div>
    <script src="js/app.js"></script>
</body>

</html>