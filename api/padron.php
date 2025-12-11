<?php
/**
 * API para consultar el Padrón Electoral
 *
 * GET /api/padron.php?cedula=123456789
 *
 * Retorna datos del ciudadano si existe en el padrón
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');

// Rate limiting simple
session_start();
$requestKey = 'padron_requests_' . date('YmdH');
$_SESSION[$requestKey] = ($_SESSION[$requestKey] ?? 0) + 1;
if ($_SESSION[$requestKey] > 100) {
    http_response_code(429);
    echo json_encode(['error' => 'Demasiadas solicitudes. Intente más tarde.']);
    exit;
}

// Validar cédula
$cedula = $_GET['cedula'] ?? '';
$cedula = preg_replace('/[^0-9]/', '', $cedula);

if (empty($cedula) || strlen($cedula) < 9) {
    http_response_code(400);
    echo json_encode(['error' => 'Cédula inválida. Debe tener 9 dígitos.']);
    exit;
}

// Pad con ceros a la izquierda si es necesario
$cedula = str_pad($cedula, 9, '0', STR_PAD_LEFT);

try {
    require_once __DIR__ . '/../includes/db.php';
    global $pdo;

    // Buscar en padrón
    $stmt = $pdo->prepare("
        SELECT
            p.cedula,
            p.nombre,
            p.primer_apellido,
            p.segundo_apellido,
            p.nombre_completo,
            p.fecha_vencimiento,
            p.codelec,
            d.provincia,
            d.canton,
            d.distrito
        FROM padron_electoral p
        LEFT JOIN padron_distritos d ON p.codelec = d.codelec
        WHERE p.cedula = ?
    ");
    $stmt->execute([$cedula]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            'encontrado' => true,
            'cedula' => $result['cedula'],
            'nombre' => trim($result['nombre']),
            'apellido1' => trim($result['primer_apellido']),
            'apellido2' => trim($result['segundo_apellido']),
            'nombre_completo' => trim($result['nombre_completo']),
            'fecha_vencimiento' => $result['fecha_vencimiento'],
            'provincia' => $result['provincia'],
            'canton' => $result['canton'],
            'distrito' => $result['distrito']
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'encontrado' => false,
            'cedula' => $cedula,
            'mensaje' => 'Cédula no encontrada en el padrón electoral'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {
    // Tabla no existe o error de BD
    http_response_code(500);
    echo json_encode([
        'error' => 'Base de datos no disponible',
        'detalle' => 'El padrón electoral no ha sido importado aún'
    ], JSON_UNESCAPED_UNICODE);
}
