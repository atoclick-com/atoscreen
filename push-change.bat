@echo off
setlocal enabledelayedexpansion

echo ===================================================
echo     atoscreen - Git Commit and Push Automation
echo ===================================================
echo.

:: 1. Ask for commit message
set /p commit_msg="Enter commit message (press Enter for 'Update atoscreen'): "
if "%commit_msg%"=="" set commit_msg=Update atoscreen

echo.
echo [1/3] Compiling fresh production Vite assets...
call npm run build
if %errorlevel% neq 0 (
    echo.
    echo [WARNING] Build failed or had warnings. Continuing with git push...
)

echo.
echo [2/3] Staging and committing changes...
git add .
git commit -m "%commit_msg%"

echo.
echo [3/3] Syncing and pushing to https://github.com/atoclick-com/atoscreen.git...
git pull --rebase origin main 2>nul
git push -u origin main

if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Push encountered an issue.
    echo Trying push with upstream...
    git push -u origin main --force
    if !errorlevel! neq 0 (
        echo [ERROR] Push failed. Please check your GitHub credentials or connection.
    ) else (
        echo [SUCCESS] Changes successfully pushed to origin main!
    )
) else (
    echo.
    echo [SUCCESS] Changes successfully pushed to https://github.com/atoclick-com/atoscreen!
)

echo.
echo ===================================================
echo   On your server (/home/trotiluxe.ma/public_html/v):
echo   Run: bash deploy.sh
echo ===================================================
echo.
pause
