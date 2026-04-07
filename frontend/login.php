<?php
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Autenticación - StockAlert" />
    <title>Login - StockAlert</title>
    <link rel="stylesheet" href="css/style.css" />
    <style>
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #0f172a;
        }
        .form-panel {
            background-color: #1e293b;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #334155;
        }
    </style>
</head>

<body>
    <div class="auth-wrapper">
        <div id="authContainer" class="panel form-panel" style="width: 100%; max-width: 400px; padding: 30px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h1 class="brand" style="color: #646cff; font-size: 2em; margin-bottom: 10px;">StockAlert</h1>
                <p>Gestión de Inventario</p>
            </div>
            <h2 id="authTitle" style="text-align: center; margin-bottom: 15px;">Iniciar Sesión</h2>
            <form id="authForm" class="form-grid">
                <label>
                    Usuario
                    <input type="text" id="authUsuario" required autocomplete="username" />
                </label>
                <label>
                    Contraseña
                    <input type="password" id="authPassword" required autocomplete="current-password" />
                </label>
                <button type="submit" class="btn-primary" id="authSubmit" style="margin-top: 10px;">Ingresar</button>
            </form>
            <p style="text-align:center; margin-top:20px; font-size: 14px;">
                <a href="#" id="toggleAuthBtn" style="color: #646cff; text-decoration: none; font-weight: bold;">¿No tienes cuenta? Regístrate</a>
            </p>
            <div id="authMensaje" class="alert"></div>
        </div>
    </div>
    <script src="js/auth.js"></script>
</body>

</html>
