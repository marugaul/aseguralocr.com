<?php
// admin/actions/save-client.php - Crear o actualizar cliente
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/clients.php');
    exit;
}

try {
    $id = intval($_POST['id'] ?? 0);
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
    $direccion = trim($_POST['direccion'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $canton = trim($_POST['canton'] ?? '');
    $distrito = trim($_POST['distrito'] ?? '');
    $status = $_POST['status'] ?? 'active';

    // Validaciones básicas
    if (empty($nombre_completo) || empty($email)) {
        throw new Exception('Nombre y email son requeridos');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }

    // Verificar email único (excepto para el mismo cliente)
    $checkStmt = $pdo->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
    $checkStmt->execute([$email, $id]);
    if ($checkStmt->fetch()) {
        throw new Exception('Ya existe un cliente con ese email');
    }

    if ($id > 0) {
        // Actualizar cliente existente
        $stmt = $pdo->prepare("
            UPDATE clients SET
                nombre_completo = ?,
                email = ?,
                telefono = ?,
                cedula = ?,
                fecha_nacimiento = ?,
                direccion = ?,
                provincia = ?,
                canton = ?,
                distrito = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $nombre_completo,
            $email,
            $telefono ?: null,
            $cedula ?: null,
            $fecha_nacimiento ?: null,
            $direccion ?: null,
            $provincia ?: null,
            $canton ?: null,
            $distrito ?: null,
            $status,
            $id
        ]);

        $_SESSION['flash_message'] = 'Cliente actualizado exitosamente';
        $_SESSION['flash_type'] = 'success';
        header('Location: /admin/client-detail.php?id=' . $id);

    } else {
        // Crear nuevo cliente
        $stmt = $pdo->prepare("
            INSERT INTO clients (nombre_completo, email, telefono, cedula, fecha_nacimiento, direccion, provincia, canton, distrito, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $nombre_completo,
            $email,
            $telefono ?: null,
            $cedula ?: null,
            $fecha_nacimiento ?: null,
            $direccion ?: null,
            $provincia ?: null,
            $canton ?: null,
            $distrito ?: null,
            $status
        ]);

        $newId = $pdo->lastInsertId();
        $_SESSION['flash_message'] = 'Cliente creado exitosamente';
        $_SESSION['flash_type'] = 'success';
        header('Location: /admin/client-detail.php?id=' . $newId);
    }

} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';

    if ($id > 0) {
        header('Location: /admin/client-detail.php?id=' . $id);
    } else {
        header('Location: /admin/clients.php');
    }
}
exit;
