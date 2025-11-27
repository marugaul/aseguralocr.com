<?php
// client/claims.php - Asistencias e Indemnización
require_once __DIR__ . '/includes/client_auth.php';
require_client_login();

require_once __DIR__ . '/../includes/db.php';

$clientId = get_current_client_id();
$clientData = get_client_data();

// Obtener los tipos de seguro del cliente
$clientInsuranceTypes = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT tipo_seguro FROM policies WHERE client_id = ? AND status IN ('vigente', 'por_vencer')");
    $stmt->execute([$clientId]);
    while ($row = $stmt->fetch()) {
        $clientInsuranceTypes[] = $row['tipo_seguro'];
    }
} catch (Exception $e) {
    $clientInsuranceTypes = [];
}

// Obtener información de asistencias
$assistanceInfo = [];
try {
    $stmt = $pdo->query("SELECT * FROM insurance_assistance WHERE activo = TRUE ORDER BY orden, tipo_seguro");
    $assistanceInfo = $stmt->fetchAll();
} catch (Exception $e) {
    // Si la tabla no existe, usar datos por defecto
    $assistanceInfo = [];
}

// Si no hay datos en BD, usar datos por defecto
if (empty($assistanceInfo)) {
    $assistanceInfo = [
        [
            'tipo_seguro' => 'hogar',
            'titulo' => 'Asistencia Seguro de Hogar INS',
            'descripcion' => 'En caso de siniestro en su hogar (incendio, robo, daños por agua, etc.), siga los pasos de indemnización.',
            'telefono_asistencia' => '800-CALL-INS (800-2255-467)',
            'horario_atencion' => '24 horas, los 7 días de la semana',
            'pasos_indemnizacion' => json_encode([
                '1. Notificar inmediatamente al INS llamando a la línea de emergencias',
                '2. No mover ni alterar evidencia del siniestro',
                '3. Tomar fotografías de los daños',
                '4. Completar el formulario de reclamo',
                '5. Reunir documentos requeridos',
                '6. Presentar reclamo en oficinas INS o en línea',
                '7. Esperar visita del perito ajustador',
                '8. Recibir resolución y pago'
            ]),
            'documentos_requeridos' => json_encode([
                'Copia de la póliza',
                'Cédula de identidad',
                'Formulario de reclamo firmado',
                'Fotografías de los daños',
                'Facturas originales de bienes afectados',
                'Denuncia ante OIJ (en caso de robo)',
                'Informe de Bomberos (en caso de incendio)'
            ])
        ],
        [
            'tipo_seguro' => 'auto',
            'titulo' => 'Asistencia Seguro de Automóvil INS',
            'descripcion' => 'En caso de accidente o avería de su vehículo, el INS le brinda asistencia en carretera las 24 horas.',
            'telefono_asistencia' => '800-CALL-INS (800-2255-467)',
            'horario_atencion' => '24 horas, los 7 días de la semana',
            'pasos_indemnizacion' => json_encode([
                '1. Verificar que no hay heridos y llamar al 911 si es necesario',
                '2. Llamar inmediatamente a la línea de asistencia INS',
                '3. No mover los vehículos hasta que llegue el perito',
                '4. Tomar fotografías del accidente y daños',
                '5. Intercambiar información con el otro conductor',
                '6. Completar el parte amistoso si aplica',
                '7. Presentar reclamo con documentos requeridos',
                '8. Llevar vehículo al taller autorizado'
            ]),
            'documentos_requeridos' => json_encode([
                'Copia de la póliza',
                'Licencia de conducir vigente',
                'Cédula de identidad',
                'Parte policial (si hay heridos)',
                'Formulario de reclamo',
                'Fotografías del accidente',
                'Parte amistoso firmado',
                'Información del otro conductor y vehículo'
            ])
        ],
        [
            'tipo_seguro' => 'vida',
            'titulo' => 'Asistencia Seguro de Vida INS',
            'descripcion' => 'El seguro de vida brinda protección financiera a sus beneficiarios en caso de fallecimiento o invalidez.',
            'telefono_asistencia' => '800-CALL-INS (800-2255-467)',
            'horario_atencion' => 'Lunes a Viernes 8:00 AM - 4:00 PM',
            'pasos_indemnizacion' => json_encode([
                '1. Notificar al INS del fallecimiento del asegurado',
                '2. Reunir documentos requeridos',
                '3. Presentar reclamo en oficinas INS',
                '4. Esperar verificación de documentos',
                '5. Recibir resolución del reclamo',
                '6. Pago a beneficiarios designados'
            ]),
            'documentos_requeridos' => json_encode([
                'Póliza original o copia',
                'Acta de defunción original',
                'Cédulas de beneficiarios',
                'Constancia de cuenta bancaria IBAN',
                'Formulario de reclamo',
                'Certificación de causa de muerte'
            ])
        ],
        [
            'tipo_seguro' => 'salud',
            'titulo' => 'Asistencia Seguro de Salud INS',
            'descripcion' => 'Cobertura médica para gastos hospitalarios, consultas y procedimientos médicos.',
            'telefono_asistencia' => '800-CALL-INS (800-2255-467)',
            'horario_atencion' => '24 horas para emergencias',
            'pasos_indemnizacion' => json_encode([
                '1. En emergencias, acudir al centro médico más cercano',
                '2. Presentar carné de asegurado',
                '3. Para consultas programadas, verificar red de proveedores',
                '4. Solicitar factura detallada',
                '5. Presentar reembolso si aplica',
                '6. Reunir documentos médicos',
                '7. Completar formulario de reclamo'
            ]),
            'documentos_requeridos' => json_encode([
                'Carné de asegurado',
                'Cédula de identidad',
                'Facturas médicas originales',
                'Recetas médicas',
                'Resultados de exámenes',
                'Epicrisis (si fue hospitalizado)',
                'Formulario de reembolso'
            ])
        ]
    ];
}

// Iconos y colores por tipo de seguro
$tipoConfig = [
    'hogar' => ['icon' => 'fa-house', 'color' => 'blue', 'gradient' => 'from-blue-500 to-blue-700'],
    'auto' => ['icon' => 'fa-car', 'color' => 'green', 'gradient' => 'from-green-500 to-green-700'],
    'vida' => ['icon' => 'fa-heart', 'color' => 'red', 'gradient' => 'from-red-500 to-red-700'],
    'salud' => ['icon' => 'fa-hospital', 'color' => 'teal', 'gradient' => 'from-teal-500 to-teal-700'],
    'viaje' => ['icon' => 'fa-plane', 'color' => 'purple', 'gradient' => 'from-purple-500 to-purple-700'],
    'otros' => ['icon' => 'fa-shield-alt', 'color' => 'gray', 'gradient' => 'from-gray-500 to-gray-700']
];

$selectedType = $_GET['tipo'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistencias e Indemnización - AseguraloCR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
    </style>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/includes/nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-hands-helping text-purple-600 mr-3"></i>Asistencias e Indemnización
            </h1>
            <p class="text-gray-600">Información sobre cómo proceder en caso de siniestro y servicios de asistencia</p>
        </div>

        <!-- Alerta de Emergencia -->
        <div class="bg-red-600 text-white rounded-2xl p-6 mb-8 shadow-lg">
            <div class="flex items-center gap-4 flex-wrap">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-phone-volume text-3xl animate-pulse"></i>
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold mb-1">¿Tienes una Emergencia?</h2>
                    <p class="text-red-100 mb-2">Llama inmediatamente a la línea de asistencia del INS</p>
                    <a href="tel:8002255467" class="inline-flex items-center text-2xl font-bold hover:underline">
                        <i class="fas fa-phone mr-2"></i>800-CALL-INS (800-2255-467)
                    </a>
                </div>
                <div class="flex gap-3">
                    <a href="tel:8002255467"
                       class="bg-white text-red-600 px-6 py-3 rounded-lg font-bold hover:bg-red-50 transition">
                        <i class="fas fa-phone mr-2"></i>Llamar Ahora
                    </a>
                </div>
            </div>
        </div>

        <!-- Selector de Tipo de Seguro -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Selecciona tu tipo de seguro</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach ($tipoConfig as $tipo => $config): ?>
                    <?php
                    $hasPolicy = in_array($tipo, $clientInsuranceTypes);
                    $isSelected = $selectedType === $tipo;
                    ?>
                    <a href="?tipo=<?= $tipo ?>"
                       class="p-4 rounded-xl text-center transition card-hover
                              <?= $isSelected ? 'bg-gradient-to-br ' . $config['gradient'] . ' text-white' : 'bg-gray-100 hover:bg-gray-200' ?>
                              <?= $hasPolicy ? '' : 'opacity-50' ?>">
                        <div class="w-12 h-12 <?= $isSelected ? 'bg-white/20' : 'bg-' . $config['color'] . '-100' ?> rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas <?= $config['icon'] ?> text-xl <?= $isSelected ? 'text-white' : 'text-' . $config['color'] . '-600' ?>"></i>
                        </div>
                        <p class="font-semibold text-sm"><?= ucfirst($tipo) ?></p>
                        <?php if ($hasPolicy): ?>
                            <span class="text-xs <?= $isSelected ? 'text-white/80' : 'text-green-600' ?>">
                                <i class="fas fa-check-circle"></i> Tienes póliza
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Información de Asistencia -->
        <?php foreach ($assistanceInfo as $info): ?>
            <?php
            $tipo = $info['tipo_seguro'];
            $config = $tipoConfig[$tipo] ?? $tipoConfig['otros'];
            $isVisible = empty($selectedType) || $selectedType === $tipo;
            $pasos = json_decode($info['pasos_indemnizacion'] ?? '[]', true) ?: [];
            $documentos = json_decode($info['documentos_requeridos'] ?? '[]', true) ?: [];
            ?>

            <div class="<?= $isVisible ? '' : 'hidden' ?> bg-white rounded-2xl shadow-lg overflow-hidden mb-8" id="section-<?= $tipo ?>">
                <!-- Header del tipo de seguro -->
                <div class="bg-gradient-to-r <?= $config['gradient'] ?> text-white p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center">
                            <i class="fas <?= $config['icon'] ?> text-3xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold"><?= htmlspecialchars($info['titulo']) ?></h2>
                            <p class="text-white/80"><?= htmlspecialchars($info['descripcion']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Información de contacto -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                        <div class="bg-gray-50 rounded-xl p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-<?= $config['color'] ?>-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-phone text-<?= $config['color'] ?>-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Teléfono de Asistencia</p>
                                    <p class="font-bold text-gray-800"><?= htmlspecialchars($info['telefono_asistencia']) ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-<?= $config['color'] ?>-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-clock text-<?= $config['color'] ?>-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Horario de Atención</p>
                                    <p class="font-bold text-gray-800"><?= htmlspecialchars($info['horario_atencion']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Pasos para indemnización -->
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-list-ol text-<?= $config['color'] ?>-600 mr-2"></i>
                                Pasos para Indemnización
                            </h3>
                            <div class="space-y-3">
                                <?php foreach ($pasos as $index => $paso): ?>
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 bg-<?= $config['color'] ?>-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                            <span class="text-<?= $config['color'] ?>-600 font-bold text-sm"><?= $index + 1 ?></span>
                                        </div>
                                        <p class="text-gray-700 pt-1"><?= htmlspecialchars(preg_replace('/^\d+\.\s*/', '', $paso)) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Documentos requeridos -->
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-file-alt text-<?= $config['color'] ?>-600 mr-2"></i>
                                Documentos Requeridos
                            </h3>
                            <div class="bg-gray-50 rounded-xl p-4">
                                <ul class="space-y-2">
                                    <?php foreach ($documentos as $doc): ?>
                                        <li class="flex items-center gap-2 text-gray-700">
                                            <i class="fas fa-check-circle text-green-500"></i>
                                            <?= htmlspecialchars($doc) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="tel:8002255467"
                           class="bg-<?= $config['color'] ?>-600 hover:bg-<?= $config['color'] ?>-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                            <i class="fas fa-phone mr-2"></i>Llamar a Asistencia
                        </a>
                        <?php if (!empty($info['url_formulario_reclamo'])): ?>
                            <a href="<?= htmlspecialchars($info['url_formulario_reclamo']) ?>"
                               target="_blank"
                               class="bg-white border-2 border-<?= $config['color'] ?>-600 text-<?= $config['color'] ?>-600 px-6 py-3 rounded-lg font-semibold hover:bg-<?= $config['color'] ?>-50 transition">
                                <i class="fas fa-file-download mr-2"></i>Descargar Formulario
                            </a>
                        <?php endif; ?>
                        <a href="https://wa.me/50688888888?text=Hola,%20necesito%20ayuda%20con%20un%20reclamo%20de%20mi%20seguro%20de%20<?= $tipo ?>"
                           target="_blank"
                           class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                            <i class="fab fa-whatsapp mr-2"></i>Asistencia por WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Sección de Asistencias Adicionales -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-concierge-bell text-purple-600 mr-3"></i>
                Servicios de Asistencia Incluidos
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Asistencia en Carretera -->
                <div class="border border-gray-200 rounded-xl p-5 hover:border-purple-300 transition">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-car-crash text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2">Asistencia en Carretera</h3>
                    <p class="text-sm text-gray-600 mb-3">Grúa, cambio de llanta, paso de corriente, cerrajería vehicular, envío de combustible.</p>
                    <p class="text-xs text-purple-600 font-semibold">Incluido en Seguro de Auto</p>
                </div>

                <!-- Asistencia al Hogar -->
                <div class="border border-gray-200 rounded-xl p-5 hover:border-purple-300 transition">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-tools text-green-600 text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2">Asistencia al Hogar</h3>
                    <p class="text-sm text-gray-600 mb-3">Plomería, electricidad, cerrajería, vidriería de emergencia las 24 horas.</p>
                    <p class="text-xs text-purple-600 font-semibold">Incluido en Seguro de Hogar</p>
                </div>

                <!-- Asistencia Médica -->
                <div class="border border-gray-200 rounded-xl p-5 hover:border-purple-300 transition">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-ambulance text-red-600 text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2">Asistencia Médica</h3>
                    <p class="text-sm text-gray-600 mb-3">Ambulancia, traslado médico, orientación médica telefónica 24/7.</p>
                    <p class="text-xs text-purple-600 font-semibold">Incluido en Seguro de Salud</p>
                </div>

                <!-- Asistencia Legal -->
                <div class="border border-gray-200 rounded-xl p-5 hover:border-purple-300 transition">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-gavel text-yellow-600 text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2">Asistencia Legal</h3>
                    <p class="text-sm text-gray-600 mb-3">Orientación legal telefónica en caso de accidentes de tránsito.</p>
                    <p class="text-xs text-purple-600 font-semibold">Incluido en Seguro de Auto</p>
                </div>

                <!-- Asistencia en Viaje -->
                <div class="border border-gray-200 rounded-xl p-5 hover:border-purple-300 transition">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-suitcase text-purple-600 text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2">Asistencia en Viaje</h3>
                    <p class="text-sm text-gray-600 mb-3">Cobertura médica internacional, repatriación, pérdida de equipaje.</p>
                    <p class="text-xs text-purple-600 font-semibold">Incluido en Seguro de Viaje</p>
                </div>

                <!-- Asistencia Funeraria -->
                <div class="border border-gray-200 rounded-xl p-5 hover:border-purple-300 transition">
                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-cross text-gray-600 text-xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2">Asistencia Funeraria</h3>
                    <p class="text-sm text-gray-600 mb-3">Servicios funerarios y trámites en caso de fallecimiento del asegurado.</p>
                    <p class="text-xs text-purple-600 font-semibold">Incluido en Seguro de Vida</p>
                </div>
            </div>
        </div>

        <!-- Preguntas Frecuentes -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-question-circle text-purple-600 mr-3"></i>
                Preguntas Frecuentes
            </h2>

            <div class="space-y-4">
                <details class="border border-gray-200 rounded-lg">
                    <summary class="p-4 cursor-pointer font-semibold text-gray-800 hover:bg-gray-50">
                        ¿Cuánto tiempo tengo para reportar un siniestro?
                    </summary>
                    <div class="p-4 pt-0 text-gray-600">
                        Debe reportar el siniestro lo antes posible, idealmente dentro de las primeras 24 a 72 horas después del evento. Para emergencias, llame inmediatamente a la línea de asistencia.
                    </div>
                </details>

                <details class="border border-gray-200 rounded-lg">
                    <summary class="p-4 cursor-pointer font-semibold text-gray-800 hover:bg-gray-50">
                        ¿Qué pasa si no tengo todos los documentos?
                    </summary>
                    <div class="p-4 pt-0 text-gray-600">
                        Puede iniciar el reclamo con los documentos que tenga disponibles. El INS le indicará qué documentos adicionales necesita presentar para completar el trámite.
                    </div>
                </details>

                <details class="border border-gray-200 rounded-lg">
                    <summary class="p-4 cursor-pointer font-semibold text-gray-800 hover:bg-gray-50">
                        ¿Cuánto tiempo tarda el proceso de indemnización?
                    </summary>
                    <div class="p-4 pt-0 text-gray-600">
                        El tiempo varía según el tipo de siniestro y la complejidad del caso. Generalmente, el INS tiene un plazo de 30 días hábiles para resolver una vez que el expediente está completo.
                    </div>
                </details>

                <details class="border border-gray-200 rounded-lg">
                    <summary class="p-4 cursor-pointer font-semibold text-gray-800 hover:bg-gray-50">
                        ¿Puedo dar seguimiento a mi reclamo en línea?
                    </summary>
                    <div class="p-4 pt-0 text-gray-600">
                        Sí, puede dar seguimiento a través del sitio web del INS (grupoins.com) o llamando a la línea de servicio al cliente. También puede contactarnos y le ayudamos con el seguimiento.
                    </div>
                </details>

                <details class="border border-gray-200 rounded-lg">
                    <summary class="p-4 cursor-pointer font-semibold text-gray-800 hover:bg-gray-50">
                        ¿Qué hago si mi reclamo es rechazado?
                    </summary>
                    <div class="p-4 pt-0 text-gray-600">
                        Si su reclamo es rechazado, tiene derecho a presentar una apelación. Contáctenos y le asesoraremos sobre los pasos a seguir para apelar la decisión.
                    </div>
                </details>
            </div>
        </div>
    </div>
</body>
</html>
