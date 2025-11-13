# Final cache clear after Redis fix

Write-Host "Clearing all caches..." -ForegroundColor Yellow

$commands = @"
cd /home/u990109832/domains/coprra.com/public_html
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo 'CACHE_CLEARED'
"@

$tempFile = [System.IO.Path]::GetTempFileName()
$commands | Out-File -FilePath $tempFile -Encoding ASCII -NoNewline

$plinkArgs = "-ssh -batch -P 65002 -pw Hamo1510@Rayan146 u990109832@45.87.81.218 -m `"$tempFile`""

try {
    $result = & "C:\Program Files\PuTTY\plink.exe" $plinkArgs.Split(' ')
    Write-Host $result -ForegroundColor Cyan
    Write-Host ""
    Write-Host "✅ Cache cleared!" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed: $_" -ForegroundColor Red
} finally {
    Remove-Item $tempFile -ErrorAction SilentlyContinue
}

