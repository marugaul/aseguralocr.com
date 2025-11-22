#!/bin/bash
# Post-deployment script
LOG="/home/asegural/deploy_production.log"

echo "=== Post-Deploy: $(date) ===" >> $LOG

# Copy htaccess
if [ -f "/home/asegural/aseguralocr_repo/.htaccess.parent" ]; then
    cp /home/asegural/aseguralocr_repo/.htaccess.parent /home/asegural/public_html/.htaccess 2>> $LOG
    [ $? -eq 0 ] && echo "✓ htaccess copied" >> $LOG || echo "✗ htaccess FAILED" >> $LOG
else
    echo "✗ htaccess.parent NOT FOUND" >> $LOG
fi

# Copy index redirect
if [ -f "/home/asegural/aseguralocr_repo/index_public_html.php" ]; then
    cp /home/asegural/aseguralocr_repo/index_public_html.php /home/asegural/public_html/index.php 2>> $LOG
    [ $? -eq 0 ] && echo "✓ index.php copied" >> $LOG || echo "✗ index.php FAILED" >> $LOG
else
    echo "✗ index_public_html.php NOT FOUND" >> $LOG
fi

echo "=== Post-Deploy Complete ===" >> $LOG
