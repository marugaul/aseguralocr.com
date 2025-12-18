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
    <link rel="icon" type="image/svg+xml" href="/imagenes/favicon.svg">
    <link rel="icon" type="image/png" href="/imagenes/favicon.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4f8;
            color: #1a202c;
            line-height: 1.6;
        }

        /* NAVBAR */
        .navbar {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-brand {
            color: white;
            font-size: 1.4rem;
            font-weight: 700;
            text-decoration: none;
        }

        .navbar-menu {
            display: flex;
            gap: 8px;
        }

        .navbar-menu a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .navbar-menu a:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        /* MAIN CONTENT */
        .main-content {
            padding: 32px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* PAGE HEADER */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
        }

        .page-header p {
            color: #64748b;
            margin-top: 4px;
        }

        /* BUTTONS */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            box-shadow: 0 4px 12px rgba(59,130,246,0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59,130,246,0.5);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        /* STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        }

        .stat-card .label {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1e293b;
        }

        .stat-card.blue { border-left: 4px solid #3b82f6; }
        .stat-card.green { border-left: 4px solid #10b981; }
        .stat-card.purple { border-left: 4px solid #8b5cf6; }
        .stat-card.orange { border-left: 4px solid #f59e0b; }

        /* CARD */
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
        }

        .card-body {
            padding: 0;
        }

        /* TABLE */
        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f8fafc;
            padding: 14px 20px;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }

        table tbody tr:hover {
            background: #f8fafc;
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        /* BADGES */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .badge-green { background: #d1fae5; color: #047857; }
        .badge-red { background: #fee2e2; color: #dc2626; }
        .badge-gray { background: #f1f5f9; color: #475569; }
        .badge-purple { background: #ede9fe; color: #7c3aed; }

        /* USER CELL */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-info .name {
            font-weight: 600;
            color: #1e293b;
        }

        .user-info .meta {
            font-size: 0.8rem;
            color: #64748b;
        }

        /* FORMS */
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="search"],
        select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.9rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        .search-box {
            width: 250px;
        }

        /* MODAL */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 200;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 0.9rem;
            color: #374151;
        }

        /* ACTION BUTTONS */
        .action-buttons {
            display: flex;
            gap: 6px;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .action-btn.view { background: #dbeafe; color: #2563eb; }
        .action-btn.edit { background: #fef3c7; color: #d97706; }
        .action-btn.delete { background: #fee2e2; color: #dc2626; }

        .action-btn:hover {
            transform: scale(1.1);
        }

        /* UTILITIES */
        .text-muted { color: #64748b; }
        .text-small { font-size: 0.85rem; }
        .mt-2 { margin-top: 8px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .d-flex { display: flex; }
        .gap-2 { gap: 8px; }
        .align-center { align-items: center; }
        .justify-between { justify-content: space-between; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/admin/dashboard.php" class="navbar-brand">⚡ AseguraloCR</a>
        <div class="navbar-menu">
            <a href="/admin/dashboard.php">📊 Dashboard</a>
            <a href="/admin/clients.php">👥 Clientes</a>
            <a href="/admin/documents.php">📄 Documentos</a>
            <a href="/admin/payment-settings.php">💳 Pagos</a>
            <a href="/admin/padron_importar_ajax.php">🪪 Padrón</a>
            <a href="/" target="_blank">🌐 Sitio</a>
            <a href="/admin/logout.php">🚪 Salir</a>
        </div>
    </nav>
    <div class="main-content">
