@echo off
cd /d C:\Users\Gaser\Desktop\COPRRA
(
echo open 45.87.81.218 21
echo u990109832
echo Hamo1510@Rayan146
echo cd /domains/coprra.com/public_html
echo binary
echo put restore_env.php
echo bye
) > ftp_cmd_restore.txt

ftp -v -n -s:ftp_cmd_restore.txt
del ftp_cmd_restore.txt
echo.
echo Upload completed - Now access: https://coprra.com/restore_env.php

