<?php
// admin/actions/save-client.php - Crear/editar cliente desde admin
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/clients.php');
    exit;
}

try {
    $id = !empty($_POST['id']) ? intval($_POST['id']) : null;
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $canton = trim($_POST['canton'] ?? '');
    $distrito = trim($_POST['distrito'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $status = $_POST['status'] ?? 'active';

    // Validaciones
    if (empty($nombre)) {
        throw new Exception('El nombre es requerido');
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }

    // Verificar si email ya existe (para otro cliente)
    $checkStmt = $pdo->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
    $checkStmt->execute([$email, $id ?? 0]);
    if ($checkStmt->fetch()) {
        throw new Exception('Ya existe un cliente con ese email');
    }

    if ($id) {
        // Actualizar cliente existente
        $stmt = $pdo->prepare("
            UPDATE clients SET
                nombre_completo = ?,
                email = ?,
                telefono = ?,
                cedula = ?,
                provincia = ?,
                canton = ?,
                distrito = ?,
                direccion = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $nombre, $email, $telefono, $cedula,
            $provincia, $canton, $distrito, $direccion,
            $status, $id
        ]);
        $message = 'Cliente actualizado correctamente';
    } else {
        // Crear nuevo cliente
        $stmt = $pdo->prepare("
            INSERT INTO clients (nombre_completo, email, telefono, cedula, provincia, canton, distrito, direccion, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $nombre, $email, $telefono, $cedula,
            $provincia, $canton, $distrito, $direccion,
            $status
        ]);
        $id = $pdo->lastInsertId();
        $message = 'Cliente creado correctamente';
    }

    // Redirigir con mensaje de éxito
    header('Location: /admin/clients.php?success=' . urlencode($message));
    exit;

} catch (Exception $e) {
    header('Location: /admin/clients.php?error=' . urlencode($e->getMessage()));
    exit;
}
