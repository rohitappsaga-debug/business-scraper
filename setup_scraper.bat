@echo off
setlocal enabledelayedexpansion

echo ====================================================
echo    Business Scraper: COMPLETE PROJECT SETUP
echo ====================================================
echo This script will prepare your fresh PC for scraping.
echo Ensure you have PHP, Composer, and Node.js installed.
echo.

:: 1. Environment File
if not exist .env (
    echo [1/9] Creating .env file from example...
    copy .env.example .env
) else (
    echo [1/9] .env file already exists.
)

:: 1.5 Node.js Auto-Discovery (New)
echo.
echo [1.5/9] Locating Node.js binary...
set "DETECTED_NODE="
for /f "delims=" %%i in ('where node 2^>nul') do (
    set "DETECTED_NODE=%%i"
    goto :node_found
)
if exist "C:\Program Files\nodejs\node.exe" set "DETECTED_NODE=C:\Program Files\nodejs\node.exe" & goto :node_found
if exist "%ProgramFiles%\nodejs\node.exe" set "DETECTED_NODE=%ProgramFiles%\nodejs\node.exe" & goto :node_found

:node_found
if defined DETECTED_NODE (
    set "SAFE_NODE=!DETECTED_NODE:\=/!"
    echo Found Node at: !DETECTED_NODE!
    echo Updating .env with discovered path...
    :: Robust PowerShell update using variable passing to avoid quote hell
    powershell -Command "$p = '!SAFE_NODE!'; (Get-Content .env) -replace '^NODE_BINARY_PATH=.*', \"NODE_BINARY_PATH=\\\"$p\\\"\" | Set-Content .env"
    if !ERRORLEVEL! neq 0 (
        echo [ERROR] Could not update .env file. Please check file permissions.
        pause
    )
) else (
    echo [WARNING] Could not locate node.exe automatically. 
    echo Please set NODE_BINARY_PATH manually in your .env file.
)

:: 2. Composer Dependencies
echo.
echo [2/9] Installing PHP dependencies (Composer)...
call composer install --no-interaction

:: 3. Application Key
echo.
echo [3/9] Generating application key...
call php artisan key:generate --no-interaction

:: 4. Node.js Dependencies
echo.
echo [4/9] Installing Node.js dependencies (NPM)...
call npm install

:: 5. Playwright Browsers
echo.
echo [5/9] Installing Playwright Chromium browser...
echo (This may take a few minutes...)
call npx playwright install chromium

:: 6. Database Migrations
echo.
echo [6/9] Running database migrations...
echo (Ensure your WAMP/MySQL server is running!)
call php artisan migrate --force

:: 7. Build Assets & Storage
echo.
echo [7/9] Finalizing application assets...
call php artisan storage:link --no-interaction
call npm run build

:: 8. Health Check
echo.
echo [8/9] Running final health check...
call php artisan scraper:check
set "HEALTH_RESULT=%ERRORLEVEL%"

:: 9. Auto-Repair Logic
if %HEALTH_RESULT% neq 0 (
    echo.
    echo ====================================================
    echo    !!! PROBLEMS DETECTED !!!
    echo ====================================================
    echo [9/9] Starting Auto-Repair Mode...
    
    echo [REPAIR] Forcing browser installation with dependencies...
    call npx playwright install chromium --with-deps
    
    echo [REPAIR] Refreshing Node dependencies (Forced)...
    call npm install --force
    
    echo [REPAIR] Retrying Health Check...
    call php artisan scraper:check
    if !ERRORLEVEL! neq 0 (
        echo.
        echo [CRITICAL ERROR] Auto-Repair could not fix the environment.
        echo ----------------------------------------------------
        echo TROUBLESHOOTING CHECKLIST:
        echo 1. DATABASE: Is WAMP/XAMPP (MySQL) running?
        echo 2. CONFIG: Did you set DB_PASSWORD in your .env file?
        echo 3. NETWORK: Do you have a stable internet connection?
        echo 4. PERMISSIONS: Try running this .bat as Administrator.
        echo ----------------------------------------------------
    ) else (
        echo.
        echo [PASS] Auto-Repair was successful! System is now ready.
    )
) else (
    echo.
    echo [9/9] Status: Environment check passed. No repair needed.
)

echo.
echo ====================================================
echo SETUP PROCESS FINISHED
echo ====================================================
echo 1. Ensure WAMP/MySQL is running.
echo 2. Run 'php artisan serve' to start the web server.
echo 3. Run 'php artisan queue:work' to process scraping jobs.
echo ====================================================
echo.
echo [DONE] Setup finished. You can now scroll up and copy any output.
pause
exit /b 0
