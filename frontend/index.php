<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="StockAlert: gestión con alertas de vencimiento para negocios pequeños." />
    <title>StockAlert</title>
    <link rel="stylesheet" href="css/style.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <div class="app-shell">

        <header class="topbar">
            <div class="brand">StockAlert</div>
            <div class="topbar-right">
                <p>Sistema Web de Gestión de Inventario con Alertas de Vencimiento</p>
            </div>
        </header>

        <div id="pageAlert" class="page-alert hidden">
            <button id="pageAlertClose" class="page-alert-close" aria-label="Cerrar alerta">×</button>
            <p id="pageAlertText" class="page-alert-text"></p>
        </div>

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
                    <Label>
                        Fecha de Elaboración
                        <input type="date" id="fechaElaboracion" />
                    </Label>
                    <label>
                        Vencimiento
                        <input type="date" id="vencimiento" required />
                    </label>
                    <label>
                        Valor Neto
                        <input type="number" min="1" id="valorNeto" placeholder="Ej. 10000" required />
                    </label>
                    <label>
                        IVA/Impuestos(%)
                        <input type="number" min="0" id="impuesto" placeholder="Ej. 19" value="19" required />
                    </label>
                    <label>
                        Valor Final
                        <input type="text" id="valorFinal" readonly placeholder="Se calcula solo">
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
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Buscar por nombre..." class="search-input" />
                    </div>
                </div>

                <div class="tabs" id="statusTabs">
                    <button class="status-tab active" data-status="all">Todos</button>
                    <button class="status-tab" data-status="vigente">Vigentes</button>
                    <button class="status-tab" data-status="por_vencer">Por vencer</button>
                    <button class="status-tab" data-status="vencido">Vencidos</button>
                </div>

                <div class="dashboard-cards">
                    <div class="card total-card"><strong>Total</strong> <span id="totalCount">0</span></div>
                    <div class="card vigente-card"><strong>Vigentes</strong> <span id="vigenteCount">0</span></div>
                    <div class="card por-vencer-card"><strong>Por vencer</strong> <span id="porVencerCount">0</span></div>
                    <div class="card vencido-card"><strong>Vencidos</strong> <span id="vencidoCount">0</span></div>
                    <div class="card critic-card"><strong>Críticos</strong> <span id="criticCount">0</span></div>
                </div>

                <div class="chart-wrapper">
                    <div class="bar-group">
                        <span>Vigente</span>
                        <div class="bar-bg">
                            <div class="bar-fill" id="barVigente"></div>
                        </div>
                    </div>
                    <div class="bar-group">
                        <span>Por vencer</span>
                        <div class="bar-bg">
                            <div class="bar-fill warning" id="barPorVencer"></div>
                        </div>
                    </div>
                    <div class="bar-group">
                        <span>Vencido</span>
                        <div class="bar-bg">
                            <div class="bar-fill danger" id="barVencido"></div>
                        </div>
                    </div>
                </div>

                <div class="chart-container" style="max-width: 400px; margin: 20px auto; height: 350px;">
                    <h3 style="text-align: center; margin-bottom: 15px;">Top 5 Productos Más Vencidos</h3>
                    <canvas id="topExpiredChart" style="max-height: 300px;"></canvas>
                </div>

                <div class="table-wrap">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Cantidad</th>
                                <th>Vencimiento</th>
                                <th>Elaboración</th>
                                <th>Valor Final</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="productsGrid"></tbody>
                    </table>
                </div>

                <div class="stats" id="stats"></div>
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