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
            background: linear-gradient(135deg, #10172b 0%, #0a1221 100%);
        }
        .form-panel {
            background-color: #111a2a; /* Unified with our new theme card-bg */
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
        }
        #authSubmit {
            background: linear-gradient(120deg, #1d4ed8, #0f3a74);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }
        .form-grid label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #ffffff !important;
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
