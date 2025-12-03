<?php
// admin/actions/delete-policy.php - Eliminar póliza y sus pagos asociados
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$id = intval($_GET['id'] ?? 0);
$clientId = intval($_GET['client_id'] ?? 0);

if (!$id) {
    header('Location: /admin/clients.php?error=' . urlencode('ID de póliza inválido'));
    exit;
}

try {
    // Obtener póliza para verificar que existe
    $stmt = $pdo->prepare("SELECT * FROM policies WHERE id = ?");
    $stmt->execute([$id]);
    $policy = $stmt->fetch();

    if (!$policy) {
        throw new Exception('Póliza no encontrada');
    }

    $clientId = $clientId ?: $policy['client_id'];

    // Iniciar transacción
    $pdo->beginTransaction();

    // Eliminar pagos asociados primero
    $stmt = $pdo->prepare("DELETE FROM payments WHERE policy_id = ?");
    $stmt->execute([$id]);
    $deletedPayments = $stmt->rowCount();

    // Eliminar documentos asociados a la póliza (si tiene policy_id)
    try {
        $stmt = $pdo->prepare("DELETE FROM client_documents WHERE policy_id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        // Ignorar si la columna no existe
    }

    // Eliminar notificaciones asociadas (si tiene policy_id)
    try {
        $stmt = $pdo->prepare("DELETE FROM client_notifications WHERE policy_id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        // Ignorar si la columna no existe
    }

    // Eliminar la póliza
    $stmt = $pdo->prepare("DELETE FROM policies WHERE id = ?");
    $stmt->execute([$id]);

    // Confirmar transacción
    $pdo->commit();

    $msg = 'Póliza #' . $policy['numero_poliza'] . ' eliminada';
    if ($deletedPayments > 0) {
        $msg .= ' junto con ' . $deletedPayments . ' pagos';
    }

    header('Location: /admin/client-detail.php?id=' . $clientId . '&msg=' . urlencode($msg));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: /admin/client-detail.php?id=' . $clientId . '&error=' . urlencode($e->getMessage()));
    exit;
}
