<?php
// admin/dashboard.php (versión corregida: parámetros únicos y logging)

// dependencias
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// === Configurar logging de errores ===
$logDir = __DIR__ . '/../logs';
$logFile = $logDir . '/dashboard_errors.log';
$debugLog = $logDir . '/admin_login_debug.log';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
if (!file_exists($logFile)) {
    @file_put_contents($logFile, "");
}
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $logFile);
error_reporting(E_ALL);

function dashboard_log($msg) {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    $entry = "[$time] $msg\n\n";
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

// Debug: Log session state on dashboard load
$time = date('Y-m-d H:i:s');
@file_put_contents($debugLog, "[$time] === Dashboard loaded ===\n", FILE_APPEND);
@file_put_contents($debugLog, "[$time] Session ID: " . session_id() . "\n", FILE_APPEND);
@file_put_contents($debugLog, "[$time] Session data: " . json_encode($_SESSION) . "\n", FILE_APPEND);
@file_put_contents($debugLog, "[$time] admin_logged: " . ($_SESSION['admin_logged'] ?? 'NOT SET') . "\n", FILE_APPEND);

try {
    require_admin();

    // tipo para filtrar (hogar/autos/rt)
    $type = strtolower(trim($_GET['type'] ?? 'hogar'));
    $allowed = ['hogar','autos','rt'];
    if (!in_array($type, $allowed)) $type = 'hogar';

    // búsqueda q (id numérico o referencia)
    $q = trim($_GET['q'] ?? '');

    // ---------- Cotizaciones ----------
   
// ---------- Cotizaciones (mejorada: chequeo + fallback) ----------
try {
    // Primero comprobamos si hay registros que cumplan el filtro JSON (tipo=hogar/etc.)
    $countSql = "
      SELECT COUNT(*) AS cnt
      FROM cotizaciones co
      WHERE (
        JSON_UNQUOTE(JSON_EXTRACT(co.payload, '$.tipo')) = :t1
        OR JSON_UNQUOTE(JSON_EXTRACT(co.payload, '$.type')) = :t2
        OR JSON_UNQUOTE(JSON_EXTRACT(co.payload, '$.categoria')) = :t3
      )
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute(['t1' => $type, 't2' => $type, 't3' => $type]);
    $matches = (int) $countStmt->fetchColumn();

    // Si hay matches aplicamos la consulta filtrada; si no, mostramos las últimas cotizaciones (fallback)
    if ($matches > 0) {
        $sqlCotBase = "
          SELECT co.id, co.referencia, co.client_id, co.monto, co.moneda, co.estado, co.payload, co.created_at,
                 cl.nombre_completo AS cliente_nombre, cl.email AS cliente_correo, cl.telefono AS cliente_telefono
          FROM cotizaciones co
          LEFT JOIN clients cl ON co.client_id = cl.id
          WHERE (
            JSON_UNQUOTE(JSON_EXTRACT(co.payload, '$.tipo')) = :t1
            OR JSON_UNQUOTE(JSON_EXTRACT(co.payload, '$.type')) = :t2
            OR JSON_UNQUOTE(JSON_EXTRACT(co.payload, '$.categoria')) = :t3
          )
        ";
        $paramsCot = ['t1' => $type, 't2' => $type, 't3' => $type];

        // aplicar búsqueda q si existe
        if ($q !== '') {
            if (ctype_digit($q)) {
                $sqlCotBase .= " AND (co.id = :qnum OR co.referencia = :qstr)";
                $paramsCot['qnum'] = intval($q);
                $paramsCot['qstr'] = $q;
            } else {
                $sqlCotBase .= " AND co.referencia LIKE :qstr";
                $paramsCot['qstr'] = "%{$q}%";
            }
        }
        $sqlCotBase .= " ORDER BY co.created_at DESC LIMIT 200";

        $stmtC = $pdo->prepare($sqlCotBase);
        $stmtC->execute($paramsCot);
        $cots = $stmtC->fetchAll();

    } else {
        // Fallback: no hay cotizaciones con key tipo/type/categoria = $type
        // Mostramos las últimas cotizaciones; si se pasó q aplicamos filtro por id/referencia
        $sqlCotBase = "
          SELECT co.id, co.referencia, co.client_id, co.monto, co.moneda, co.estado, co.payload, co.created_at,
                 cl.nombre_completo AS cliente_nombre, cl.email AS cliente_correo, cl.telefono AS cliente_telefono
          FROM cotizaciones co
          LEFT JOIN clients cl ON co.client_id = cl.id
        ";
        $paramsCot = [];

        if ($q !== '') {
            // si q es numérico buscamos por id o referencia o referencia LIKE
            if (ctype_digit($q)) {
                $sqlCotBase .= " WHERE (co.id = :qnum OR co.referencia = :qstr)";
                $paramsCot['qnum'] = intval($q);
                $paramsCot['qstr'] = $q;
            } else {
                $sqlCotBase .= " WHERE co.referencia LIKE :qstr";
                $paramsCot['qstr'] = "%{$q}%";
            }
        }
        $sqlCotBase .= " ORDER BY co.created_at DESC LIMIT 200";

        $stmtC = $pdo->prepare($sqlCotBase);
        $stmtC->execute($paramsCot);
        $cots = $stmtC->fetchAll();
    }

} catch (\PDOException $e) {
    dashboard_log("Error en query Cotizaciones (mejora/fallback): " . $e->getMessage() . "\nSQL: " . ($sqlCotBase ?? '') . "\nParams: " . json_encode($paramsCot ?? []) . "\nTrace: " . $e->getTraceAsString());
    throw $e;
}
    // ---------- Submissions ----------
    $sqlSubBase = "
      SELECT s.id, s.referencia, s.origen, s.email, s.payload, s.created_at, s.pdf_path, s.referencia_cot,
             cl.id AS client_id, cl.nombre_completo AS cliente_nombre, cl.email AS cliente_correo, cl.telefono AS cliente_telefono
      FROM submissions s
      LEFT JOIN clients cl ON cl.email COLLATE utf8mb4_unicode_ci = s.email COLLATE utf8mb4_unicode_ci OR cl.id = s.referencia_cot
      WHERE (
        JSON_UNQUOTE(JSON_EXTRACT(s.payload, '$.tipo')) = :t1
        OR JSON_UNQUOTE(JSON_EXTRACT(s.payload, '$.type')) = :t2
        OR s.origen = :t3
      )
    ";
    $paramsSub = ['t1' => $type, 't2' => $type, 't3' => $type];

    if ($q !== '') {
        if (ctype_digit($q)) {
            $sqlSubBase .= " AND (s.id = :qnum OR s.referencia = :qstr OR s.referencia_cot = :qnum)";
            $paramsSub['qnum'] = intval($q);
            $paramsSub['qstr'] = $q;
        } else {
            $sqlSubBase .= " AND s.referencia LIKE :qstr";
            $paramsSub['qstr'] = "%{$q}%";
        }
    }
    $sqlSubBase .= " ORDER BY s.created_at DESC LIMIT 200";

    try {
        $stmtS = $pdo->prepare($sqlSubBase);
        $stmtS->execute($paramsSub);
        $subs = $stmtS->fetchAll();
    } catch (\PDOException $e) {
        dashboard_log("Error en query Submissions: " . $e->getMessage() . "\nSQL: " . $sqlSubBase . "\nParams: " . json_encode($paramsSub) . "\nTrace: " . $e->getTraceAsString());
        throw $e;
    }

    // =======================
    // Render HTML
    // =======================
    ?>
    <!doctype html>
    <html>
    <head>
      <meta charset="utf-8"><title>Dashboard Admin</title>
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="p-6 bg-gray-50 min-h-screen">
      <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-2xl font-bold">Dashboard — Cotizaciones / Submissions (<?= htmlspecialchars($type) ?>)</h1>
          <div class="flex items-center space-x-3">
            <a href="/admin/pdf_mapper.php" class="bg-purple-600 text-white px-3 py-2 rounded">PDF Mapper</a>
            <a href="/admin/login.php" class="text-sm text-gray-700 underline">Login</a>
            <a href="/admin/logout.php" class="bg-red-600 text-white px-3 py-2 rounded">Salir</a>
            <a href="/admin/view_logs.php" class="text-sm text-gray-700 underline">Ver logs</a>
          </div>
        </div>

        <form method="get" class="mb-4 flex gap-2 items-center">
          <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
          <select name="type" onchange="this.form.submit()" class="border p-2 rounded">
            <option value="hogar" <?= $type === 'hogar' ? 'selected' : '' ?>>Hogar</option>
            <option value="autos" <?= $type === 'autos' ? 'selected' : '' ?>>Autos</option>
            <option value="rt" <?= $type === 'rt' ? 'selected' : '' ?>>Riesgos Trabajo</option>
          </select>

          <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por id o referencia" class="border p-2 rounded flex-1">
          <button class="bg-gray-800 text-white px-3 py-2 rounded">Buscar</button>
          <a href="/admin/dashboard.php?type=<?= $type ?>" class="ml-2 text-sm text-gray-600">Limpiar</a>
        </form>

        <!-- Cotizaciones -->
        <section class="mb-8">
          <h2 class="text-xl font-semibold mb-3">Últimas Cotizaciones</h2>
          <div class="bg-white rounded shadow overflow-x-auto">
            <table class="min-w-full">
              <thead class="bg-gray-100">
                <tr>
                  <th class="p-3 text-left">ID</th>
                  <th class="p-3 text-left">Ref</th>
                  <th class="p-3 text-left">Cliente</th>
                  <th class="p-3 text-left">Monto</th>
                  <th class="p-3 text-left">Estado</th>
                  <th class="p-3 text-left">Fecha</th>
                  <th class="p-3 text-left">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($cots)): ?>
                  <tr><td colspan="7" class="p-4">No hay cotizaciones</td></tr>
                <?php else: foreach($cots as $r): ?>
                  <tr class="border-t">
                    <td class="p-3"><?= $r['id'] ?></td>
                    <td class="p-3"><?= htmlspecialchars($r['referencia']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($r['cliente_nombre'] ?? $r['cliente_correo'] ?? '') ?></td>
                    <td class="p-3"><?= ($r['moneda'] ?? '') . ' ' . number_format($r['monto'] ?? 0, 2) ?></td>
                    <td class="p-3"><?= htmlspecialchars($r['estado']) ?></td>
                    <td class="p-3"><?= $r['created_at'] ?></td>
                    <td class="p-3">
                      <a class="px-3 py-1 bg-blue-600 text-white rounded" href="/admin/view_cotizacion.php?id=<?= $r['id'] ?>&type=<?= $type ?>">Ver</a>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Submissions -->
        <section>
          <h2 class="text-xl font-semibold mb-3">Últimas Submissions (formularios enviados)</h2>
          <div class="bg-white rounded shadow overflow-x-auto">
            <table class="min-w-full">
              <thead class="bg-gray-100">
                <tr>
                  <th class="p-3 text-left">ID</th>
                  <th class="p-3 text-left">Ref</th>
                  <th class="p-3 text-left">Origen</th>
                  <th class="p-3 text-left">Email</th>
                  <th class="p-3 text-left">Fecha</th>
                  <th class="p-3 text-left">PDF</th>
                  <th class="p-3 text-left">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($subs)): ?>
                  <tr><td colspan="7" class="p-4">No hay submissions</td></tr>
                <?php else: foreach($subs as $s): ?>
                  <tr class="border-t">
                    <td class="p-3"><?= $s['id'] ?></td>
                    <td class="p-3"><?= htmlspecialchars($s['referencia']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($s['origen']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($s['email'] ?? $s['cliente_correo'] ?? '') ?></td>
                    <td class="p-3"><?= $s['created_at'] ?></td>
                    <td class="p-3">
                      <?php if ($s['pdf_path']): ?>
                        <a class="text-green-700 hover:underline" href="<?= htmlspecialchars($s['pdf_path']) ?>" target="_blank">Ver PDF</a>
                        <a class="ml-2 px-2 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700" href="<?= htmlspecialchars($s['pdf_path']) ?>" download>Descargar</a>
                      <?php else: ?>
                        -
                      <?php endif; ?>
                    </td>
                    <td class="p-3">
                      <a class="px-3 py-1 bg-blue-600 text-white rounded" href="/admin/view_submission.php?id=<?= $s['id'] ?>&type=<?= $type ?>">Ver</a>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </body>
    </html>
    <?php

} catch (\Throwable $e) {
    $msg = "Uncaught exception: " . $e->getMessage() . "\nURL: " . ($_SERVER['REQUEST_URI'] ?? '') .
           "\nGET: " . json_encode($_GET) . "\nPOST: " . json_encode($_POST) .
           "\nTrace: " . $e->getTraceAsString();
    dashboard_log($msg);

    http_response_code(500);
    echo "<h2>La página no puede procesar la solicitud ahora (Error 500)</h2>";
    echo "<p>Se ha registrado el error. Si eres el administrador, revisa /logs/dashboard_errors.log o el panel de logs.</p>";
    exit;
}