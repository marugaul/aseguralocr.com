#!/bin/bash
#
# AseguraloCR - Staging Deployment Script
# This script pulls from the staging branch and deploys to staging.aseguralocr.com
#
# Cron: */5 * * * * /bin/bash /home/asegural/deploy-staging.sh

REPO_DIR="/home/asegural/staging_repo"
STAGING_DIR="/home/asegural/public_html/aseguralocrstaging"
LOG_FILE="/home/asegural/deploy_staging.log"
GITHUB_REPO="https://github.com/marugaul/aseguralocr.com.git"
BRANCH="staging"

# Add timestamp to log
echo "=== Staging Deploy Started: $(date) ===" >> "$LOG_FILE" 2>&1

# Clone repo if it doesn't exist
if [ ! -d "$REPO_DIR/.git" ]; then
    echo "Cloning staging repository..." >> "$LOG_FILE" 2>&1
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

# Sync to staging directory
echo "Syncing files to staging directory..." >> "$LOG_FILE" 2>&1
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
    "$REPO_DIR/" "$STAGING_DIR/" >> "$LOG_FILE" 2>&1

if [ $? -eq 0 ]; then
    echo "✓ Staging deployment completed successfully: $(date)" >> "$LOG_FILE" 2>&1
else
    echo "ERROR: Rsync failed" >> "$LOG_FILE" 2>&1
    exit 1
fi

# Set proper permissions (optional, adjust as needed)
# chmod -R 755 "$STAGING_DIR"
# find "$STAGING_DIR" -type f -exec chmod 644 {} \;

echo "=== Staging Deploy Finished: $(date) ===" >> "$LOG_FILE" 2>&1
echo "" >> "$LOG_FILE" 2>&1
