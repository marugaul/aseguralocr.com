<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> - ASEGURA LO</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
        }

        body {
            background-color: #f8fafc;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 1rem;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar .logo {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            padding: 1rem 0;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.15);
        }

        .sidebar .nav-link i {
            width: 24px;
            margin-right: 0.5rem;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .top-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
        }

        .table th {
            font-weight: 600;
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
        }

        .badge-status-active { background-color: #10b981; }
        .badge-status-inactive { background-color: #6b7280; }
        .badge-status-vigente { background-color: #10b981; }
        .badge-status-vencida { background-color: #ef4444; }
        .badge-status-pendiente { background-color: #f59e0b; }
        .badge-status-pagado { background-color: #10b981; }

        .nav-section-title {
            color: rgba(255,255,255,0.5);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem 1rem 0.5rem;
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="logo">
            <i class="fas fa-shield-alt me-2"></i>ASEGURA LO
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="/admin/dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>

            <div class="nav-section-title">Gestión</div>

            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : '' ?>" href="/admin/clients.php">
                    <i class="fas fa-users"></i> Clientes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'policy') !== false ? 'active' : '' ?>" href="/admin/policies.php">
                    <i class="fas fa-file-contract"></i> Pólizas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'payment') !== false ? 'active' : '' ?>" href="/admin/payments.php">
                    <i class="fas fa-credit-card"></i> Pagos
                </a>
            </li>

            <div class="nav-section-title">Formularios</div>

            <li class="nav-item">
                <a class="nav-link" href="/admin/dashboard.php?type=hogar">
                    <i class="fas fa-house"></i> Hogar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/admin/dashboard.php?type=autos">
                    <i class="fas fa-car"></i> Autos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/admin/dashboard.php?type=rt">
                    <i class="fas fa-hard-hat"></i> RT
                </a>
            </li>

            <div class="nav-section-title">Sistema</div>

            <li class="nav-item">
                <a class="nav-link" href="/admin/view_logs.php">
                    <i class="fas fa-file-alt"></i> Logs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" href="/admin/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <button class="btn btn-link d-md-none" onclick="document.querySelector('.sidebar').classList.toggle('show')">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="text-muted"><?= date('d/m/Y H:i') ?></span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted">Admin: <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Usuario') ?></span>
            </div>
        </div>

        <!-- Page Content -->
        <div class="content-wrapper">
