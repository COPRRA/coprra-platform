# Verify Laravel Pulse on production server
$commands = @"
cd /home/u990109832/domains/coprra.com/public_html
php artisan pulse:check 2>&1
echo '---'
php artisan migrate --pretend 2>&1 | grep -i pulse | head -10
echo '---'
php artisan route:list | grep pulse 2>&1
echo 'PULSE_VERIFICATION_COMPLETE'
"@

$tempFile = [System.IO.Path]::GetTempFileName()
$commands | Out-File -FilePath $tempFile -Encoding ASCII -NoNewline

$plinkArgs = "-ssh -batch -P 65002 -pw Hamo1510@Rayan146 u990109832@45.87.81.218 -m `"$tempFile`""

try {
    Write-Host "Verifying Laravel Pulse installation..." -ForegroundColor Yellow
    $result = & "C:\Program Files\PuTTY\plink.exe" $plinkArgs.Split(' ')
    Write-Host $result -ForegroundColor Cyan
    
    if ($result -match "PULSE_VERIFICATION_COMPLETE") {
        Write-Host "`n✅ Pulse verification completed!" -ForegroundColor Green
    }
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
} finally {
    Remove-Item $tempFile -ErrorAction SilentlyContinue
}

