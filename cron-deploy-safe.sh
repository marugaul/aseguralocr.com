#!/bin/bash

# Safe deployment script for cron - prevents race conditions
# Usage: Add to crontab: */3 * * * * /home/asegural/public_html/aseguralocr/cron-deploy-safe.sh >> /home/asegural/deployment.log 2>&1

REPO_PATH="/home/asegural/aseguralocr_repo"
PROD_PATH="/home/asegural/public_html/aseguralocr"
LOCKFILE="/tmp/aseguralocr_deploy.lock"
STOP_FILE="$PROD_PATH/STOP_CRON.txt"

echo "====================================="
echo "Deploy started: $(date)"

# Check if STOP file exists
if [ -f "$STOP_FILE" ]; then
    echo "STOP_CRON.txt exists - Deployment disabled"
    echo "To re-enable, delete: $STOP_FILE"
    exit 0
fi

# Check if another deployment is running
if [ -f "$LOCKFILE" ]; then
    # Check if process is actually running
    PID=$(cat "$LOCKFILE")
    if ps -p $PID > /dev/null 2>&1; then
        echo "Another deployment is running (PID: $PID)"
        exit 0
    else
        echo "Stale lockfile found, removing..."
        rm -f "$LOCKFILE"
    fi
fi

# Create lockfile with current PID
echo $$ > "$LOCKFILE"

# Cleanup function
cleanup() {
    rm -f "$LOCKFILE"
    echo "Deploy completed: $(date)"
}
trap cleanup EXIT

# Remove git lock if exists
GIT_LOCK="$REPO_PATH/.git/index.lock"
if [ -f "$GIT_LOCK" ]; then
    echo "Removing git lock file..."
    rm -f "$GIT_LOCK"
fi

# Change to repo directory
cd "$REPO_PATH" || exit 1

# Git pull
echo "Fetching from origin..."
git fetch origin 2>&1

echo "Resetting to origin/main..."
git reset --hard origin/main 2>&1

# Wait a moment for git to finish completely
sleep 1

# Rsync to production
echo "Syncing to production..."
rsync -a \
    --delete \
    --exclude='.git' \
    --exclude='vendor/' \
    --exclude='logs/' \
    --exclude='storage/' \
    --exclude='app/config/config.php' \
    --exclude='includes/db.php' \
    "$REPO_PATH/" "$PROD_PATH/" 2>&1

echo "Rsync completed"
