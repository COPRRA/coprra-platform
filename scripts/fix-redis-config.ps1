# Fix Redis connection issue

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Fixing Redis Configuration" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$commands = @"
cd /home/u990109832/domains/coprra.com/public_html
sed -i 's/CACHE_DRIVER=redis/CACHE_DRIVER=file/g' .env
sed -i 's/SESSION_DRIVER=redis/SESSION_DRIVER=file/g' .env
grep -E 'CACHE_DRIVER|SESSION_DRIVER' .env
php artisan config:clear
php artisan config:cache
echo 'REDIS_FIXED'
"@

$tempFile = [System.IO.Path]::GetTempFileName()
$commands | Out-File -FilePath $tempFile -Encoding ASCII -NoNewline

$plinkArgs = "-ssh -batch -P 65002 -pw Hamo1510@Rayan146 u990109832@45.87.81.218 -m `"$tempFile`""

try {
    $result = & "C:\Program Files\PuTTY\plink.exe" $plinkArgs.Split(' ')
    Write-Host $result -ForegroundColor Cyan
    Write-Host ""
    Write-Host "✅ Redis configuration fixed!" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed: $_" -ForegroundColor Red
} finally {
    Remove-Item $tempFile -ErrorAction SilentlyContinue
}

