# Hassence push helper
# Usage: .\scripts\push.ps1 "Commit message"
#
# Stages all changes (respecting .gitignore), commits and pushes to origin.
# On first run, Git Credential Manager will ask for your GitHub token;
# it is cached afterwards so later runs need no credentials.
#
# For scheduled DB + content backups, see scripts/backup.bat.

param(
    [string]$Message = "Update Hassence source"
)

$ErrorActionPreference = "Stop"
$repoRoot = Split-Path -Parent $PSScriptRoot

Set-Location -LiteralPath $repoRoot

# Make sure the remote points at the right repo
if (-not (git remote get-url origin 2>$null)) {
    git remote add origin "https://github.com/hassanmemon1985-sketch/Hassence.git"
}

# Stage everything the .gitignore allows
git add -A

# Commit only if there is something to commit
$staged = git diff --cached --name-only
if (-not $staged) {
    Write-Host "Nothing to commit - working tree clean."
    exit 0
}

Write-Host "Committing: $Message"
git commit -m $Message

Write-Host "Pushing to origin..."
git push

Write-Host "Done. Files changed:"
$staged | ForEach-Object { "  $_" }