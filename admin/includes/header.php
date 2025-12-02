<?php
// admin/includes/header.php - Header template for admin pages
if (!isset($pageTitle)) {
    $pageTitle = 'Admin - AseguraloCR';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - AseguraloCR</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

    <!-- Custom Admin Styles -->
    <style>
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;
        }

        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .navbar-admin {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .card {
            border-radius: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .table th {
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }

        .badge {
            font-weight: 500;
            padding: 0.4em 0.8em;
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border-color: #e2e8f0;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .modal-content {
            border-radius: 16px;
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-footer {
            border-top: 1px solid #e2e8f0;
        }

        /* Sidebar */
        .sidebar {
            min-height: 100vh;
            background: #1e293b;
        }

        .sidebar .nav-link {
            color: #94a3b8;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(59, 130, 246, 0.2);
            color: #fff;
        }

        .sidebar .nav-link i {
            width: 24px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-admin">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="/admin/dashboard.php">
                <i class="fas fa-shield-alt me-2"></i>AseguraloCR Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/clients.php">
                            <i class="fas fa-users me-1"></i> Clientes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/documents.php">
                            <i class="fas fa-file-alt me-1"></i> Documentos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/pdf_mapper.php">
                            <i class="fas fa-file-pdf me-1"></i> PDF Mapper
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/"><i class="fas fa-home me-2"></i>Ir al sitio</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/admin/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
