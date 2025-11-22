#!/bin/bash
#
# AseguraloCR - Production Deployment Script
# This script pulls from the main branch and deploys to aseguralocr.com
#
# Cron: */10 * * * * /bin/bash /home/asegural/deploy-production.sh

REPO_DIR="/home/asegural/aseguralocr_repo"
PRODUCTION_DIR="/home/asegural/public_html/aseguralocr"
LOG_FILE="/home/asegural/deploy_production.log"
GITHUB_REPO="https://github.com/marugaul/aseguralocr.com.git"
BRANCH="main"

# Add timestamp to log
echo "=== Production Deploy Started: $(date) ===" >> "$LOG_FILE" 2>&1

# Clone repo if it doesn't exist
if [ ! -d "$REPO_DIR/.git" ]; then
    echo "Cloning production repository..." >> "$LOG_FILE" 2>&1
    git clone -b "$BRANCH" "$GITHUB_REPO" "$REPO_DIR" >> "$LOG_FILE" 2>&1

    if [ $? -ne 0 ]; then
        echo "ERROR: Failed to clone repository" >> "$LOG_FILE" 2>&1
        exit 1
    fi
fi

# Navigate to repo directory
cd "$REPO_DIR" || exit 1

# Pull latest changes
echo "Pulling latest changes from $BRANCH..." >> "$LOG_FILE" 2>&1
git fetch --all >> "$LOG_FILE" 2>&1
git reset --hard "origin/$BRANCH" >> "$LOG_FILE" 2>&1
git clean -fd >> "$LOG_FILE" 2>&1

if [ $? -ne 0 ]; then
    echo "ERROR: Failed to pull changes" >> "$LOG_FILE" 2>&1
    exit 1
fi

# Sync to production directory
echo "Syncing files to production directory..." >> "$LOG_FILE" 2>&1
rsync -av --delete \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='vendor/' \
    --exclude='composer/vendor/' \
    --exclude='logs/' \
    --exclude='storage/pdfs/' \
    --exclude='storage/uploads/' \
    --exclude='storage/temp/' \
    --exclude='app/config/config.php' \
    --exclude='includes/db.php' \
    --exclude='.env' \
    --exclude='*.log' \
    --exclude='php_error.log' \
    --exclude='sessions/' \
    --exclude='deploy-*.sh' \
    --exclude='DEPLOYMENT.md' \
    "$REPO_DIR/" "$PRODUCTION_DIR/" >> "$LOG_FILE" 2>&1

if [ $? -eq 0 ]; then
    echo "✓ Production deployment completed successfully: $(date)" >> "$LOG_FILE" 2>&1
else
    echo "ERROR: Rsync failed" >> "$LOG_FILE" 2>&1
    exit 1
fi

# Set proper permissions (optional, adjust as needed)
# chmod -R 755 "$PRODUCTION_DIR"
# find "$PRODUCTION_DIR" -type f -exec chmod 644 {} \;

echo "=== Production Deploy Finished: $(date) ===" >> "$LOG_FILE" 2>&1
echo "" >> "$LOG_FILE" 2>&1
