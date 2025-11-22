<?php
// admin/view_cotizacion.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT co.*, 
           cl.nombre AS cliente_nombre, 
           cl.correo AS cliente_correo, 
           cl.telefono AS cliente_telefono,
           cl.cedula AS cliente_cedula
    FROM cotizaciones co
    LEFT JOIN clients cl ON co.client_id = cl.id
    WHERE co.id = ?
");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) { 
    echo "Cotización no encontrada"; 
    exit; 
}

$payload = json_decode($row['payload'], true);
$type = htmlspecialchars($_GET['type'] ?? 'hogar');

// Determinar el tipo de seguro desde el payload si no viene en la URL
if (!isset($_GET['type']) && $payload) {
    if (isset($payload['tipo'])) {
        $type = strtolower($payload['tipo']);
    } elseif (isset($payload['type'])) {
        $type = strtolower($payload['type']);
    } elseif (isset($payload['categoria'])) {
        $type = strtolower($payload['categoria']);
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ver Cotización #<?= $id ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="p-8">
    <div class="max-w-5xl mx-auto bg-white rounded-lg shadow-lg">
      
      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6 rounded-t-lg">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-bold">Cotización #<?= $id ?></h1>
            <p class="text-blue-100 mt-1">Referencia: <?= htmlspecialchars($row['referencia']) ?></p>
          </div>
          <a href="/admin/dashboard.php?type=<?= $type ?>" 
             class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition">
            ← Volver al Dashboard
          </a>
        </div>
      </div>

      <div class="p-6">
        
        <!-- Información del Cliente -->
        <section class="mb-6">
          <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">Información del Cliente</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-50 p-4 rounded">
              <label class="text-sm text-gray-600 font-medium">Nombre:</label>
              <p class="text-gray-900 font-medium"><?= htmlspecialchars($row['cliente_nombre'] ?? 'No disponible') ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
              <label class="text-sm text-gray-600 font-medium">Correo:</label>
              <p class="text-gray-900"><?= htmlspecialchars($row['cliente_correo'] ?? 'No disponible') ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
              <label class="text-sm text-gray-600 font-medium">Teléfono:</label>
              <p class="text-gray-900"><?= htmlspecialchars($row['cliente_telefono'] ?? 'No disponible') ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
              <label class="text-sm text-gray-600 font-medium">Cédula:</label>
              <p class="text-gray-900"><?= htmlspecialchars($row['cliente_cedula'] ?? 'No disponible') ?></p>
            </div>
          </div>
        </section>

        <!-- Información de la Cotización -->
        <section class="mb-6">
          <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">Detalles de la Cotización</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 p-4 rounded border-l-4 border-blue-500">
              <label class="text-sm text-gray-600 font-medium">Monto:</label>
              <p class="text-2xl font-bold text-blue-600"><?= htmlspecialchars($row['moneda'] ?? 'USD') ?> <?= number_format($row['monto'] ?? 0, 2) ?></p>
            </div>
            <div class="bg-green-50 p-4 rounded border-l-4 border-green-500">
              <label class="text-sm text-gray-600 font-medium">Estado:</label>
              <p class="text-lg font-semibold text-green-600 capitalize"><?= htmlspecialchars($row['estado']) ?></p>
            </div>
            <div class="bg-purple-50 p-4 rounded border-l-4 border-purple-500">
              <label class="text-sm text-gray-600 font-medium">Fecha de creación:</label>
              <p class="text-lg font-medium text-purple-600"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></p>
            </div>
          </div>
        </section>

        <!-- Datos del Payload (JSON) -->
        <section class="mb-6">
          <h2 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">Datos Completos (Payload JSON)</h2>
          <div class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto">
            <pre class="text-sm font-mono"><?= htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
          </div>
        </section>

        <!-- Acciones -->
        <section class="mt-8 border-t pt-6">
          <h2 class="text-xl font-semibold mb-4 text-gray-800">Acciones</h2>
          
          <div class="flex flex-wrap gap-4">
            <!-- Generar PDF desde cotización -->
            <form action="/admin/pdf_generator.php" method="post" class="inline">
              <input type="hidden" name="cotizacion_id" value="<?= $id ?>">
              <button type="submit" 
                      class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition transform hover:scale-105 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                Generar PDF desde Cotización
              </button>
            </form>

            <!-- Buscar submission relacionada -->
            <?php 
            $stmtSub = $pdo->prepare("SELECT id FROM submissions WHERE referencia_cot = ? OR email = ? LIMIT 1");
            $stmtSub->execute([$id, $row['cliente_correo']]);
            $relatedSubmission = $stmtSub->fetch();
            
            if ($relatedSubmission): ?>
              <a href="/admin/view_submission.php?id=<?= $relatedSubmission['id'] ?>&type=<?= $type ?>" 
                 class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition transform hover:scale-105 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Ver Submission Relacionada
              </a>
            <?php endif; ?>
          </div>

          <!-- Mensajes de éxito -->
          <?php if (!empty($_GET['saved'])): ?>
            <div class="mt-6 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg flex items-center gap-3">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="font-medium">¡PDF generado y guardado exitosamente!</span>
            </div>
          <?php endif; ?>
        </section>

      </div>
    </div>
  </div>
</body>
</html>