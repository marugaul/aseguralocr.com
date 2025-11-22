<?php
/**
 * loader-fpdi-fpdf.php
 * Carga FPDF y FPDI sin Composer buscando en rutas absolutas del proyecto.
 * Ajusta PROJECT_ROOT si tu ruta home/public_html es distinta.
 */

function load_fpdi_instance() {
    // AJUSTA si tu ruta real es otra
    $PROJECT_ROOT = '/home/asegural/public_html';

    // Rutas comprobadas para FPDF (tи▓ dijiste que estив en /vendor/fpdf/fpdf.php)
    $fpdfCandidates = [
        $PROJECT_ROOT . '/vendor/fpdf/fpdf.php',
        $PROJECT_ROOT . '/vendor/fpdf.php',
        $PROJECT_ROOT . '/fpdf/fpdf.php',
        $PROJECT_ROOT . '/fpdf.php',
    ];

    // Rutas comprobadas para FPDI (namespaced v2 o legacy)
    $fpdiCandidates = [
        $PROJECT_ROOT . '/vendor/setasign/fpdi/src/autoload.php',
        $PROJECT_ROOT . '/vendor/setasign/fpdi/src/Fpdi.php',
        $PROJECT_ROOT . '/vendor/fpdi/src/autoload.php',
        $PROJECT_ROOT . '/vendor/fpdi/src/Fpdi.php',
        $PROJECT_ROOT . '/vendor/fpdi/fpdi.php',
        $PROJECT_ROOT . '/vendor/fpdi.php',
        $PROJECT_ROOT . '/fpdi/src/autoload.php',
        $PROJECT_ROOT . '/fpdi.php',
    ];

    // 1) Intentar autoload de composer (si existe)
    $autoload = $PROJECT_ROOT . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        // si composer cargио ambas librerикas ya, instanciamos direct
        if (class_exists('setasign\\Fpdi\\Fpdi')) {
            return new \setasign\Fpdi\Fpdi();
        } elseif (class_exists('FPDI')) {
            return new \FPDI();
        }
    }

    // 2) Incluir FPDF (obligatorio)
    $fpdfIncluded = false;
    foreach ($fpdfCandidates as $p) {
        if (file_exists($p)) {
            require_once $p;
            $fpdfIncluded = true;
            break;
        }
    }

    // 3) Incluir FPDI
    $fpdiIncluded = false;
    foreach ($fpdiCandidates as $p) {
        if (file_exists($p)) {
            require_once $p;
            $fpdiIncluded = true;
            break;
        }
    }

    // 4) Detectar clase FPDI disponible
    $fpdiClass = null;
    if (class_exists('setasign\\Fpdi\\Fpdi')) {
        $fpdiClass = 'setasign\\Fpdi\\Fpdi';
    } elseif (class_exists('FPDI')) {
        $fpdiClass = 'FPDI';
    } else {
        // intentar detectar cualquier clase que contenga "fpdi"
        foreach (get_declared_classes() as $c) {
            if (stripos($c, 'fpdi') !== false) {
                $fpdiClass = $c;
                break;
            }
        }
    }

    // Errores claros si falta algo
    if (!$fpdfIncluded) {
        throw new RuntimeException("FPDF no encontrado. Rutas comprobadas:\n" . implode("\n", $fpdfCandidates));
    }
    if ($fpdiClass === null) {
        throw new RuntimeException("FPDI no encontrado o no se detectио clase FPDI. Rutas comprobadas:\n" . implode("\n", $fpdiCandidates));
    }

    // 5) Instanciar PDF
    if ($fpdiClass === 'FPDI') {
        return new FPDI();
    } else {
        return new $fpdiClass();
    }
}