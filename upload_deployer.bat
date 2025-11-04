@echo off
REM Upload deployment script via FTP
echo Uploading DEPLOY_COMPLETE_CODE.php to production...
echo open ftp.coprra.com> ftpcmd.dat
echo u990109832>> ftpcmd.dat
echo Gasser@010>> ftpcmd.dat
echo binary>> ftpcmd.dat
echo cd /domains/coprra.com/public_html>> ftpcmd.dat
echo put DEPLOY_COMPLETE_CODE.php>> ftpcmd.dat
echo quit>> ftpcmd.dat
ftp -n -s:ftpcmd.dat
del ftpcmd.dat
echo.
echo ========================================
echo Upload complete!
echo Access it at: https://coprra.com/DEPLOY_COMPLETE_CODE.php
echo ========================================
pause

