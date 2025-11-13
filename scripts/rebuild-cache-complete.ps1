# Complete cache rebuild on server
$commands = @"
cd /home/u990109832/domains/coprra.com/public_html
php artisan optimize:clear
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes-v7.php
rm -f bootstrap/cache/services.php
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo 'CACHE_REBUILT'
"@

$tempFile = [System.IO.Path]::GetTempFileName()
$commands | Out-File -FilePath $tempFile -Encoding ASCII -NoNewline

$plinkArgs = "-ssh -batch -P 65002 -pw Hamo1510@Rayan146 u990109832@45.87.81.218 -m `"$tempFile`""

try {
    Write-Host "Performing complete cache rebuild..." -ForegroundColor Yellow
    $result = & "C:\Program Files\PuTTY\plink.exe" $plinkArgs.Split(' ')
    Write-Host $result -ForegroundColor Cyan
    
    if ($result -match "CACHE_REBUILT") {
        Write-Host "`n✅ Cache rebuilt successfully!" -ForegroundColor Green
    }
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
} finally {
    Remove-Item $tempFile -ErrorAction SilentlyContinue
}

