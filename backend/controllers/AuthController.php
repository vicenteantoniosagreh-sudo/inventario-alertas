<?php
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/logs.php';

function register($pdo, $input)
{
    $usuario  = trim($input['usuario'] ?? '');
    $password = $input['password'] ?? '';

    if (!$usuario || !$password) {
        response(['error' => 'Usuario y contraseña obligatorios'], 400);
    }
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $usuario)) {
        response(['error' => 'Usuario inválido: solo letras, números y _ (3-30 caracteres)'], 400);
    }
    if (strlen($password) < 8) {
        response(['error' => 'La contraseña debe tener al menos 8 caracteres'], 400);
    }
    if (strlen($password) > 72) {
        response(['error' => 'Contraseña demasiado larga'], 400);
    }

    $pass_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (usuario, pass_hash) VALUES (:usuario, :pass_hash)'
    );
    try {
        $stmt->execute([':usuario' => $usuario, ':pass_hash' => $pass_hash]);
        registrarLog($pdo, 'REGISTRO', $usuario);
        response(['success' => true, 'message' => 'Usuario registrado exitosamente'], 201);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            response(['error' => 'No se pudo registrar (el usuario ya existe)'], 400);
        } else {
            error_log('[REGISTRO ERROR] ' . $e->getMessage());
            response(['error' => 'Error de base de datos'], 500);
        }
    }
}

function login($pdo, $input)
{
    $usuario  = trim($input['usuario'] ?? '');
    $password = $input['password'] ?? '';

    if (!$usuario || !$password) {
        response(['error' => 'Usuario y contraseña obligatorios'], 400);
    }

    $stmt = $pdo->prepare(
        'SELECT id, usuario, pass_hash, rol, intentos_fallidos, bloqueado_hasta
         FROM usuarios WHERE usuario = :usuario'
    );
    $stmt->execute([':usuario' => $usuario]);
    $userRow = $stmt->fetch();

    if ($userRow && $userRow['bloqueado_hasta']) {
        if (new DateTime() < new DateTime($userRow['bloqueado_hasta'])) {
            registrarLog($pdo, 'LOGIN_BLOQUEADO', $usuario);
            response(['error' => 'Cuenta bloqueada. Intenta en 15 minutos.'], 429);
        }
    }

    if ($userRow && password_verify($password, $userRow['pass_hash'])) {
        $token  = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $pdo->prepare(
            'UPDATE usuarios
             SET token = :token, token_expira = :expira,
                 intentos_fallidos = 0, bloqueado_hasta = NULL
             WHERE id = :id'
        )->execute([':token' => $token, ':expira' => $expira, ':id' => $userRow['id']]);

        registrarLog($pdo, 'LOGIN_OK', $usuario);

        response([
            'success' => true,
            'token'   => $token,
            'usuario' => $userRow['usuario'],
            'rol'     => $userRow['rol']
        ]);
    } else {
        if ($userRow) {
            $pdo->prepare(
                'UPDATE usuarios
                 SET intentos_fallidos = intentos_fallidos + 1,
                     bloqueado_hasta = IF(intentos_fallidos >= 4,
                         DATE_ADD(NOW(), INTERVAL 15 MINUTE), NULL)
                 WHERE id = :id'
            )->execute([':id' => $userRow['id']]);
        }
        registrarLog($pdo, 'LOGIN_FALLIDO', $usuario);
        response(['error' => 'Credenciales inválidas'], 401);
    }
}
