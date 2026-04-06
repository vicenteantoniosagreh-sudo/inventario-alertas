<?php
function registrarLog($pdo, $evento, $usuario, $detalles = null)
{
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
        $pdo->prepare(
            'INSERT INTO logs_auditoria (evento, usuario, ip, detalles)
             VALUES (:ev, :us, :ip, :det)'
        )->execute([
            ':ev'  => $evento,
            ':us'  => $usuario,
            ':ip'  => $ip,
            ':det' => $detalles
        ]);
    } catch (PDOException $e) {
        error_log('[LOG ERROR] ' . $e->getMessage());
    }
}
