<?php
// admin/includes/header.php
if (!isset($pageTitle)) {
    $pageTitle = 'Admin';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - AseguraloCR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
        .admin-nav { background: linear-gradient(135deg, #1e40af, #3b82f6); }
        .admin-nav a { color: white !important; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .table th { background: #f1f5f9; font-size: 0.8rem; text-transform: uppercase; }
        .badge { padding: 6px 10px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg admin-nav mb-4">
        <div class="container-fluid">
            <a class="navbar-brand text-white fw-bold" href="/admin/dashboard.php">
                <i class="fas fa-shield-alt me-2"></i>AseguraloCR
            </a>
            <div class="d-flex gap-3">
                <a href="/admin/dashboard.php" class="text-white text-decoration-none"><i class="fas fa-home me-1"></i> Dashboard</a>
                <a href="/admin/clients.php" class="text-white text-decoration-none"><i class="fas fa-users me-1"></i> Clientes</a>
                <a href="/admin/documents.php" class="text-white text-decoration-none"><i class="fas fa-file me-1"></i> Documentos</a>
                <a href="/admin/logout.php" class="text-white text-decoration-none"><i class="fas fa-sign-out-alt me-1"></i> Salir</a>
            </div>
        </div>
    </nav>
    <div class="container-fluid px-4">
