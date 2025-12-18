#!/bin/bash
# Script para matar procesos PHP que llevan más de 5 minutos corriendo
# Ejecutar cada 5 minutos en cron

# Configuración
MAX_TIEMPO=300  # 5 minutos en segundos
USUARIO="asegural_marugaul"

# Encontrar y matar procesos PHP que lleven más de MAX_TIEMPO segundos
ps -u $USUARIO -o pid,etime,cmd | grep php | while read PID ETIME CMD; do
    # Convertir tiempo a segundos
    if [[ $ETIME == *-* ]]; then
        # Días-HH:MM:SS
        SECONDS=999999
    elif [[ $ETIME == *:*:* ]]; then
        # HH:MM:SS
        IFS=':' read -r H M S <<< "$ETIME"
        SECONDS=$((H*3600 + M*60 + S))
    elif [[ $ETIME == *:* ]]; then
        # MM:SS
        IFS=':' read -r M S <<< "$ETIME"
        SECONDS=$((M*60 + S))
    else
        # SS
        SECONDS=$ETIME
    fi

    # Si lleva más de MAX_TIEMPO, matar
    if [ "$SECONDS" -gt "$MAX_TIEMPO" ]; then
        kill -9 $PID 2>/dev/null
        echo "$(date): Proceso PHP $PID matado (tiempo: $ETIME)" >> /home/asegural/kill_processes.log
    fi
done

# Limpiar archivos de progreso del padrón si existen
rm -f /home/asegural/public_html/aseguralocr/data/padron/extract_progress.json 2>/dev/null
rm -f /home/asegural/public_html/aseguralocr/data/padron/progress.json 2>/dev/null
