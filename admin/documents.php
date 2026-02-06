<?php
// admin/documents.php - Gestión de documentos para clientes
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

// Obtener cliente seleccionado
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;
$client = null;
$documents = [];

// Obtener lista de clientes para el selector
$clientsStmt = $pdo->query("SELECT id, nombre_completo, email FROM clients ORDER BY nombre_completo");
$clients = $clientsStmt->fetchAll();

// Si hay cliente seleccionado, obtener sus datos y documentos
if ($clientId) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();

    if ($client) {
        // Obtener pólizas del cliente con sus documentos
        $polStmt = $pdo->prepare("SELECT id, numero_poliza, tipo_seguro FROM policies WHERE client_id = ? ORDER BY created_at DESC");
        $polStmt->execute([$clientId]);
        $policies = $polStmt->fetchAll();

        // Obtener documentos agrupados por póliza
        $policiesWithDocs = [];
        $totalDocs = 0;

        foreach ($policies as $policy) {
            $docStmt = $pdo->prepare("
                SELECT d.*
                FROM client_documents d
                WHERE d.client_id = ? AND d.policy_id = ?
                ORDER BY d.created_at DESC
            ");
            $docStmt->execute([$clientId, $policy['id']]);
            $docs = $docStmt->fetchAll();

            $policiesWithDocs[] = [
                'policy' => $policy,
                'documents' => $docs
            ];
            $totalDocs += count($docs);
        }

        // Obtener documentos sin póliza asociada
        $unassignedStmt = $pdo->prepare("
            SELECT d.*
            FROM client_documents d
            WHERE d.client_id = ? AND d.policy_id IS NULL
            ORDER BY d.created_at DESC
        ");
        $unassignedStmt->execute([$clientId]);
        $unassignedDocs = $unassignedStmt->fetchAll();
        $totalDocs += count($unassignedDocs);
    }
}

// Mensajes de éxito/error
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$pageTitle = "Gestión de Documentos";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .doc-card { transition: all 0.3s ease; }
        .doc-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .file-icon { font-size: 2rem; }
        .file-icon.pdf { color: #dc3545; }
        .file-icon.image { color: #198754; }
        .file-icon.doc { color: #0d6efd; }

        /* Drag & Drop Upload Styles */
        .file-upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            background: #f8fafc;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            margin-bottom: 20px;
        }
        .file-upload-area:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .file-upload-area.drag-over {
            border-color: #10b981;
            background: #d1fae5;
            transform: scale(1.02);
        }
        .upload-icon {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.6;
            color: #64748b;
        }
        .upload-text h5 {
            margin: 0 0 8px 0;
            font-size: 1.1rem;
            color: #1e293b;
            font-weight: 600;
        }
        .upload-text p {
            margin: 0 0 12px 0;
            color: #64748b;
            font-size: 0.95rem;
        }
        .file-formats {
            display: inline-block;
            padding: 6px 16px;
            background: white;
            border-radius: 20px;
            font-size: 0.8rem;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        .file-preview {
            display: none;
            align-items: center;
            gap: 16px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #10b981;
            margin-bottom: 20px;
        }
        .file-preview.show {
            display: flex;
        }
        .file-preview-icon {
            font-size: 40px;
            line-height: 1;
        }
        .file-preview-info {
            flex: 1;
            text-align: left;
        }
        .file-preview-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.95rem;
            margin-bottom: 4px;
            word-break: break-word;
        }
        .file-preview-size {
            font-size: 0.85rem;
            color: #64748b;
        }
        .file-remove-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: #fee2e2;
            color: #dc2626;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .file-remove-btn:hover {
            background: #dc2626;
            color: white;
            transform: scale(1.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid px-4 py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0"><i class="fas fa-file-alt me-2"></i>Gestión de Documentos</h1>
                <p class="text-muted mb-0">Subir y administrar documentos para clientes</p>
            </div>
            <a href="/admin/clients.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver a Clientes
            </a>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Selector de Cliente -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Seleccionar Cliente</label>
                        <select name="client_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Seleccione un cliente --</option>
                            <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $clientId == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nombre_completo']) ?> (<?= htmlspecialchars($c['email']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($client): ?>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-upload me-2"></i>Subir Documento
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ($client): ?>
        <!-- Info del Cliente -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-1"><?= htmlspecialchars($client['nombre_completo']) ?></h5>
                        <p class="text-muted mb-0">
                            <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($client['email']) ?>
                            <?php if ($client['telefono']): ?>
                            <span class="ms-3"><i class="fas fa-phone me-1"></i><?= htmlspecialchars($client['telefono']) ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge bg-primary fs-6"><?= $totalDocs ?> documentos</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pólizas y Documentos -->
        <?php if ($totalDocs === 0): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="fas fa-folder-open fa-4x mb-3"></i>
                <h5>No hay documentos</h5>
                <p>Haz clic en "Subir Documento" para agregar archivos</p>
            </div>
        </div>
        <?php else: ?>

        <!-- Pólizas con sus documentos -->
        <?php foreach ($policiesWithDocs as $pwdIndex => $pwd): ?>
        <?php
        $policy = $pwd['policy'];
        $policyDocs = $pwd['documents'];
        $tipoNombres = [
            'hogar' => '🏠 Hogar',
            'auto' => '🚗 Auto',
            'rt' => '👷 Riesgos del Trabajo',
            'vida' => '❤️ Vida',
            'salud' => '🏥 Salud',
            'otros' => '📦 Otros'
        ];
        $tipoDisplay = $tipoNombres[$policy['tipo_seguro']] ?? ucfirst($policy['tipo_seguro']);
        ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-contract me-2 text-primary"></i>
                        Póliza: <?= htmlspecialchars($policy['numero_poliza']) ?>
                        <span class="badge bg-secondary ms-2"><?= $tipoDisplay ?></span>
                    </h5>
                    <span class="badge bg-primary"><?= count($policyDocs) ?> documento(s)</span>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($policyDocs)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-3x mb-2"></i>
                    <p class="mb-0">No hay documentos asociados a esta póliza</p>
                </div>
                <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($policyDocs as $doc): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="card doc-card h-100">
                            <div class="card-body text-center">
                                <?php
                                $ext = strtolower(pathinfo($doc['nombre_archivo'], PATHINFO_EXTENSION));
                                $iconClass = 'fas fa-file';
                                $iconColor = '';
                                if ($ext === 'pdf') { $iconClass = 'fas fa-file-pdf'; $iconColor = 'pdf'; }
                                elseif (in_array($ext, ['jpg','jpeg','png','gif'])) { $iconClass = 'fas fa-file-image'; $iconColor = 'image'; }
                                elseif (in_array($ext, ['doc','docx'])) { $iconClass = 'fas fa-file-word'; $iconColor = 'doc'; }
                                ?>
                                <i class="<?= $iconClass ?> file-icon <?= $iconColor ?> mb-2"></i>
                                <h6 class="card-title mb-1" title="<?= htmlspecialchars($doc['nombre']) ?>">
                                    <?= htmlspecialchars(mb_substr($doc['nombre'], 0, 25)) ?><?= mb_strlen($doc['nombre']) > 25 ? '...' : '' ?>
                                </h6>
                                <p class="card-text small text-muted mb-2">
                                    <span class="badge bg-secondary"><?= ucfirst($doc['tipo']) ?></span>
                                </p>
                                <p class="card-text small text-muted">
                                    <?= date('d/m/Y', strtotime($doc['created_at'])) ?>
                                    <br><?= number_format($doc['tamano_bytes'] / 1024, 1) ?> KB
                                </p>
                                <div class="btn-group btn-group-sm">
                                    <a href="/admin/actions/download-document.php?id=<?= $doc['id'] ?>"
                                       class="btn btn-outline-primary" title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <button class="btn btn-outline-danger" title="Eliminar"
                                            onclick="deleteDocument(<?= $doc['id'] ?>, '<?= htmlspecialchars($doc['nombre'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <?php if ($doc['visible_cliente']): ?>
                                    <small class="text-success"><i class="fas fa-eye"></i> Visible para cliente</small>
                                    <?php else: ?>
                                    <small class="text-muted"><i class="fas fa-eye-slash"></i> Oculto</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Documentos sin póliza asociada -->
        <?php if (!empty($unassignedDocs)): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-folder me-2 text-secondary"></i>
                        Documentos Adicionales
                        <small class="text-muted">(sin póliza asociada)</small>
                    </h5>
                    <span class="badge bg-secondary"><?= count($unassignedDocs) ?> documento(s)</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($unassignedDocs as $doc): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="card doc-card h-100">
                            <div class="card-body text-center">
                                <?php
                                $ext = strtolower(pathinfo($doc['nombre_archivo'], PATHINFO_EXTENSION));
                                $iconClass = 'fas fa-file';
                                $iconColor = '';
                                if ($ext === 'pdf') { $iconClass = 'fas fa-file-pdf'; $iconColor = 'pdf'; }
                                elseif (in_array($ext, ['jpg','jpeg','png','gif'])) { $iconClass = 'fas fa-file-image'; $iconColor = 'image'; }
                                elseif (in_array($ext, ['doc','docx'])) { $iconClass = 'fas fa-file-word'; $iconColor = 'doc'; }
                                ?>
                                <i class="<?= $iconClass ?> file-icon <?= $iconColor ?> mb-2"></i>
                                <h6 class="card-title mb-1" title="<?= htmlspecialchars($doc['nombre']) ?>">
                                    <?= htmlspecialchars(mb_substr($doc['nombre'], 0, 25)) ?><?= mb_strlen($doc['nombre']) > 25 ? '...' : '' ?>
                                </h6>
                                <p class="card-text small text-muted mb-2">
                                    <span class="badge bg-secondary"><?= ucfirst($doc['tipo']) ?></span>
                                </p>
                                <p class="card-text small text-muted">
                                    <?= date('d/m/Y', strtotime($doc['created_at'])) ?>
                                    <br><?= number_format($doc['tamano_bytes'] / 1024, 1) ?> KB
                                </p>
                                <div class="btn-group btn-group-sm">
                                    <a href="/admin/actions/download-document.php?id=<?= $doc['id'] ?>"
                                       class="btn btn-outline-primary" title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <button class="btn btn-outline-danger" title="Eliminar"
                                            onclick="deleteDocument(<?= $doc['id'] ?>, '<?= htmlspecialchars($doc['nombre'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <?php if ($doc['visible_cliente']): ?>
                                    <small class="text-success"><i class="fas fa-eye"></i> Visible para cliente</small>
                                    <?php else: ?>
                                    <small class="text-muted"><i class="fas fa-eye-slash"></i> Oculto</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>

        <!-- Modal Subir Documento -->
        <div class="modal fade" id="uploadModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Subir Documento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="/admin/actions/upload-document.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="client_id" value="<?= $clientId ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Archivo del Documento *</label>
                                <div class="file-upload-area" id="fileUploadArea">
                                    <input type="file" name="documento" id="documentoInput" hidden required
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                                    <div class="upload-placeholder" id="uploadPlaceholder">
                                        <div class="upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <div class="upload-text">
                                            <h5>Arrastra el archivo aquí</h5>
                                            <p>o haz clic para seleccionar</p>
                                            <span class="file-formats">PDF, Word, Imágenes (Máx. 10MB)</span>
                                        </div>
                                    </div>
                                    <div class="file-preview" id="filePreview">
                                        <div class="file-preview-icon" id="fileIcon">
                                            <i class="fas fa-file"></i>
                                        </div>
                                        <div class="file-preview-info">
                                            <div class="file-preview-name" id="fileName"></div>
                                            <div class="file-preview-size" id="fileSize"></div>
                                        </div>
                                        <button type="button" class="file-remove-btn" id="removeFileBtn">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nombre del Documento *</label>
                                <input type="text" name="nombre" class="form-control" required
                                       placeholder="Ej: Póliza de Hogar 2024">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tipo de Documento</label>
                                <select name="tipo" class="form-select">
                                    <option value="poliza">Póliza</option>
                                    <option value="cotizacion">Cotización</option>
                                    <option value="factura">Factura</option>
                                    <option value="comprobante">Comprobante</option>
                                    <option value="contrato">Contrato</option>
                                    <option value="anexo">Anexo</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Vincular a Póliza (opcional)</label>
                                <select name="policy_id" class="form-select">
                                    <option value="">-- Sin vincular --</option>
                                    <?php if (!empty($policies)): ?>
                                    <?php foreach ($policies as $pol): ?>
                                    <option value="<?= $pol['id'] ?>">
                                        <?= htmlspecialchars($pol['numero_poliza']) ?> (<?= ucfirst($pol['tipo_seguro']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="visible_cliente" value="1" class="form-check-input" id="visibleCliente" checked>
                                <label class="form-check-label" for="visibleCliente">
                                    Visible para el cliente
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Subir
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Sin cliente seleccionado -->
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                <h4>Selecciona un Cliente</h4>
                <p class="text-muted">Elige un cliente del selector para ver y gestionar sus documentos</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    function deleteDocument(id, nombre) {
        if (confirm('¿Eliminar el documento "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
            window.location.href = '/admin/actions/delete-document.php?id=' + id + '&client_id=<?= $clientId ?>';
        }
    }

    // File Upload with Drag & Drop
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('documentoInput');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const fileIcon = document.getElementById('fileIcon');
    const removeBtn = document.getElementById('removeFileBtn');

    // Click to upload
    fileUploadArea?.addEventListener('click', (e) => {
        if (!e.target.closest('.file-remove-btn')) {
            fileInput?.click();
        }
    });

    // Prevent defaults for drag events
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileUploadArea?.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Highlight on drag
    ['dragenter', 'dragover'].forEach(eventName => {
        fileUploadArea?.addEventListener(eventName, () => {
            fileUploadArea.classList.add('drag-over');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        fileUploadArea?.addEventListener(eventName, () => {
            fileUploadArea.classList.remove('drag-over');
        }, false);
    });

    // Handle drop
    fileUploadArea?.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            fileInput.files = dt.files;
            handleFiles(files);
        }
    });

    // Handle file selection
    fileInput?.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        if (files.length === 0) return;

        const file = files[0];
        const maxSize = 10 * 1024 * 1024; // 10MB

        // Validate size
        if (file.size > maxSize) {
            alert('⚠️ El archivo es demasiado grande. Máximo 10MB');
            fileInput.value = '';
            return;
        }

        // Get file extension
        const ext = file.name.split('.').pop().toLowerCase();

        // Set icon based on file type
        let iconClass = 'fas fa-file';
        let iconColor = '#64748b';

        if (ext === 'pdf') {
            iconClass = 'fas fa-file-pdf';
            iconColor = '#dc3545';
        } else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
            iconClass = 'fas fa-file-image';
            iconColor = '#198754';
        } else if (['doc', 'docx'].includes(ext)) {
            iconClass = 'fas fa-file-word';
            iconColor = '#0d6efd';
        }

        // Update preview
        fileIcon.innerHTML = `<i class="${iconClass}" style="color: ${iconColor}"></i>`;
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);

        // Show preview, hide placeholder
        uploadPlaceholder.style.display = 'none';
        filePreview.classList.add('show');
    }

    // Remove file
    removeBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        fileInput.value = '';
        uploadPlaceholder.style.display = 'block';
        filePreview.classList.remove('show');
    });

    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    // Reset on modal close
    document.getElementById('uploadModal')?.addEventListener('hidden.bs.modal', function () {
        fileInput.value = '';
        uploadPlaceholder.style.display = 'block';
        filePreview.classList.remove('show');
    });
    </script>
</body>
</html>
