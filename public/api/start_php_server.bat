@echo off
echo Starting PHP development server...
echo Server will run at: http://localhost:8080
echo.
echo To test the form:
echo 1. Open http://localhost:8080 in your browser
echo 2. Navigate to the registration page
echo 3. Try submitting the form
echo.
echo Press Ctrl+C to stop the server
echo.

cd /d "%~dp0"
php -S localhost:8080