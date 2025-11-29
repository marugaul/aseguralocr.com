<?php
/**
 * QuoteService - Servicio para vincular cotizaciones con clientes
 *
 * Cuando un usuario envía un formulario de cotización, este servicio:
 * 1. Busca si existe un cliente registrado con ese email
 * 2. Si existe, crea una entrada en la tabla 'quotes' vinculada al cliente
 * 3. También actualiza el campo client_id en 'submissions'
 */

class QuoteService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Mapeo de origen a tipo_seguro para la tabla quotes
     */
    private const TIPO_SEGURO_MAP = [
        'hogar' => 'hogar',
        'autos' => 'auto',
        'riesgos-trabajo' => 'otros', // RT no está en el ENUM, usamos 'otros'
    ];

    /**
     * Busca un cliente por email
     *
     * @param string $email
     * @return array|null Datos del cliente o null si no existe
     */
    public function findClientByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, nombre, correo FROM clients WHERE correo = ? LIMIT 1");
        $stmt->execute([$email]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        return $client ?: null;
    }

    /**
     * Vincula una cotización con un cliente existente
     *
     * @param string $email Email del solicitante
     * @param string $referencia Referencia de la submission
     * @param string $origen Tipo de formulario (hogar, autos, riesgos-trabajo)
     * @param array $payload Datos del formulario
     * @param string|null $pdfPath Ruta del PDF generado
     * @return array Resultado con 'linked' => bool y 'quote_id' si se creó
     */
    public function linkQuoteToClient(
        string $email,
        string $referencia,
        string $origen,
        array $payload,
        ?string $pdfPath = null
    ): array {
        $result = [
            'linked' => false,
            'client_id' => null,
            'quote_id' => null,
            'message' => ''
        ];

        try {
            // Buscar cliente por email
            $client = $this->findClientByEmail($email);

            if (!$client) {
                $result['message'] = 'No se encontró cliente registrado con este email';
                return $result;
            }

            $clientId = (int) $client['id'];
            $result['client_id'] = $clientId;

            // Actualizar submissions con el client_id
            $this->updateSubmissionClientId($referencia, $clientId);

            // Obtener submission_id
            $submissionId = $this->getSubmissionId($referencia);

            // Extraer monto estimado del payload
            $montoEstimado = $this->extractMontoEstimado($origen, $payload);
            $moneda = $this->extractMoneda($payload);

            // Generar número de cotización
            $numeroCotizacion = $this->generateQuoteNumber($origen);

            // Determinar tipo de seguro
            $tipoSeguro = self::TIPO_SEGURO_MAP[$origen] ?? 'otros';

            // URL del PDF (relativa)
            $archivoCotizacionUrl = null;
            if ($pdfPath && file_exists($pdfPath)) {
                $archivoCotizacionUrl = '/storage/pdfs/' . basename($pdfPath);
            }

            // Insertar en quotes
            $stmt = $this->pdo->prepare("
                INSERT INTO quotes (
                    client_id,
                    submission_id,
                    numero_cotizacion,
                    tipo_seguro,
                    monto_estimado,
                    moneda,
                    fecha_cotizacion,
                    fecha_vencimiento_cotizacion,
                    status,
                    archivo_cotizacion_url,
                    notas_cliente,
                    created_at
                ) VALUES (
                    :client_id,
                    :submission_id,
                    :numero_cotizacion,
                    :tipo_seguro,
                    :monto_estimado,
                    :moneda,
                    CURDATE(),
                    DATE_ADD(CURDATE(), INTERVAL 30 DAY),
                    'enviada',
                    :archivo_url,
                    :notas,
                    NOW()
                )
            ");

            $notas = $this->generateNotasCliente($origen, $payload);

            $stmt->execute([
                ':client_id' => $clientId,
                ':submission_id' => $submissionId,
                ':numero_cotizacion' => $numeroCotizacion,
                ':tipo_seguro' => $tipoSeguro,
                ':monto_estimado' => $montoEstimado,
                ':moneda' => $moneda,
                ':archivo_url' => $archivoCotizacionUrl,
                ':notas' => $notas
            ]);

            $result['linked'] = true;
            $result['quote_id'] = (int) $this->pdo->lastInsertId();
            $result['numero_cotizacion'] = $numeroCotizacion;
            $result['message'] = 'Cotización vinculada exitosamente al cliente';

            // Crear notificación para el cliente
            $this->createClientNotification($clientId, $numeroCotizacion, $origen, $result['quote_id']);

        } catch (Throwable $e) {
            error_log('[QuoteService] Error vinculando cotización: ' . $e->getMessage());
            $result['message'] = 'Error al vincular: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Actualiza el client_id en la tabla submissions
     */
    private function updateSubmissionClientId(string $referencia, int $clientId): void
    {
        $stmt = $this->pdo->prepare("UPDATE submissions SET client_id = ? WHERE referencia = ?");
        $stmt->execute([$clientId, $referencia]);
    }

    /**
     * Obtiene el ID de una submission por su referencia
     */
    private function getSubmissionId(string $referencia): ?int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM submissions WHERE referencia = ? LIMIT 1");
        $stmt->execute([$referencia]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['id'] : null;
    }

    /**
     * Extrae el monto estimado según el tipo de formulario
     */
    private function extractMontoEstimado(string $origen, array $payload): ?float
    {
        switch ($origen) {
            case 'hogar':
                // Suma de monto residencia + contenido
                $residencia = (float) ($payload['montoResidencia'] ?? 0);
                $contenido = (float) ($payload['montoContenido'] ?? 0);
                return $residencia + $contenido > 0 ? $residencia + $contenido : null;

            case 'autos':
                return !empty($payload['valorVehiculo']) ? (float) $payload['valorVehiculo'] : null;

            case 'riesgos-trabajo':
                // Planilla anual como referencia
                return !empty($payload['planillaAnual']) ? (float) $payload['planillaAnual'] : null;

            default:
                return null;
        }
    }

    /**
     * Extrae la moneda del payload
     */
    private function extractMoneda(array $payload): string
    {
        $moneda = strtolower($payload['moneda'] ?? 'colones');
        if (in_array($moneda, ['dolares', 'usd', 'dólares'])) {
            return 'dolares';
        }
        return 'colones';
    }

    /**
     * Genera un número único de cotización
     */
    private function generateQuoteNumber(string $origen): string
    {
        $prefixes = [
            'hogar' => 'QH',
            'autos' => 'QA',
            'riesgos-trabajo' => 'QRT'
        ];
        $prefix = $prefixes[$origen] ?? 'Q';
        return $prefix . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    /**
     * Genera notas descriptivas para el cliente
     */
    private function generateNotasCliente(string $origen, array $payload): string
    {
        switch ($origen) {
            case 'hogar':
                $tipo = $payload['tipoPropiedad'] ?? 'propiedad';
                $direccion = $payload['direccion'] ?? '';
                return "Seguro de Hogar - $tipo. $direccion";

            case 'autos':
                $marca = $payload['marca'] ?? '';
                $modelo = $payload['modelo'] ?? '';
                $ano = $payload['ano'] ?? '';
                $placa = $payload['placa'] ?? '';
                return "Seguro de Auto - $marca $modelo $ano. Placa: $placa";

            case 'riesgos-trabajo':
                $razon = $payload['razonSocial'] ?? '';
                $trabajadores = $payload['totalTrabajadores'] ?? '?';
                return "Seguro RT - $razon. Trabajadores: $trabajadores";

            default:
                return "Solicitud de cotización";
        }
    }

    /**
     * Crea una notificación para el cliente
     */
    private function createClientNotification(int $clientId, string $numeroCotizacion, string $origen, int $quoteId): void
    {
        $tipoNombre = [
            'hogar' => 'Hogar',
            'autos' => 'Automóvil',
            'riesgos-trabajo' => 'Riesgos del Trabajo'
        ];

        $tipo = $tipoNombre[$origen] ?? 'Seguro';

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO client_notifications (
                    client_id, tipo, titulo, mensaje, quote_id, created_at
                ) VALUES (
                    ?, 'cotizacion_lista', ?, ?, ?, NOW()
                )
            ");

            $titulo = "Nueva cotización recibida";
            $mensaje = "Tu solicitud de Seguro de $tipo ha sido recibida. Número de cotización: $numeroCotizacion. Pronto recibirás respuesta.";

            $stmt->execute([$clientId, $titulo, $mensaje, $quoteId]);
        } catch (Throwable $e) {
            // No fallar si no se puede crear la notificación
            error_log('[QuoteService] Error creando notificación: ' . $e->getMessage());
        }
    }
}
