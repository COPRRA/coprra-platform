# Fix register route issue

Write-Host "Fixing register route..." -ForegroundColor Yellow

$commands = @"
cd /home/u990109832/domains/coprra.com/public_html
git pull origin feature/build-affiliate-store-foundation
php artisan view:clear
php artisan view:cache
echo 'REGISTER_ROUTE_FIXED'
"@

$tempFile = [System.IO.Path]::GetTempFileName()
$commands | Out-File -FilePath $tempFile -Encoding ASCII -NoNewline

$plinkArgs = "-ssh -batch -P 65002 -pw Hamo1510@Rayan146 u990109832@45.87.81.218 -m `"$tempFile`""

try {
    $result = & "C:\Program Files\PuTTY\plink.exe" $plinkArgs.Split(' ')
    Write-Host $result -ForegroundColor Cyan
    Write-Host ""
    Write-Host "✅ Register route fixed!" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed: $_" -ForegroundColor Red
} finally {
    Remove-Item $tempFile -ErrorAction SilentlyContinue
}

