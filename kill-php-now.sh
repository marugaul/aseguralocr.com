#!/bin/bash
# Script de emergencia - Mata TODOS los procesos PHP ahora mismo
# Ejecutar: bash kill-php-now.sh

echo "Matando procesos PHP..."

# Mata todos los procesos PHP del usuario
pkill -9 -u asegural_marugaul php

# Limpiar archivos de progreso
rm -f /home/asegural/public_html/aseguralocr/data/padron/extract_progress.json
rm -f /home/asegural/public_html/aseguralocr/data/padron/progress.json
rm -f /home/asegural/public_html/aseguralocrstaging/data/padron/extract_progress.json
rm -f /home/asegural/public_html/aseguralocrstaging/data/padron/progress.json

echo "✓ Procesos PHP detenidos"
echo "✓ Archivos de progreso eliminados"
echo "Espera 30 segundos y prueba cargar tu sitio"
