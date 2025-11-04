@echo off
echo ======================================
echo FTP Upload Script for diagnose_and_fix.php
echo ======================================
echo.

cd /d C:\Users\Gaser\Desktop\COPRRA

echo Creating FTP command file...
(
echo open 45.87.81.218 21
echo u990109832
echo Hamo1510@Rayan146
echo cd /domains/coprra.com/public_html
echo binary
echo put diagnose_and_fix.php
echo ls -la diagnose_and_fix.php
echo bye
) > ftp_commands.txt

echo.
echo Executing FTP upload...
echo.

ftp -v -n -s:ftp_commands.txt

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ======================================
    echo SUCCESS! File uploaded via FTP
    echo ======================================
    echo.
    echo Now access: https://coprra.com/diagnose_and_fix.php
    del ftp_commands.txt
    exit /b 0
) else (
    echo.
    echo ======================================
    echo FTP FAILED - Error code: %ERRORLEVEL%
    echo ======================================
    del ftp_commands.txt
    exit /b 1
)

