# Verify RouteServiceProvider on server - check if rate limiter 'api' exists
$commands = @"
cd /home/u990109832/domains/coprra.com/public_html
grep -A 3 "RateLimiter::for('api'" app/Providers/RouteServiceProvider.php 2>&1
echo '---'
php artisan tinker --execute="echo RateLimiter::limiter('api') ? 'EXISTS' : 'NOT_EXISTS';" 2>&1 | head -5
echo 'VERIFICATION_COMPLETE'
"@

$tempFile = [System.IO.Path]::GetTempFileName()
$commands | Out-File -FilePath $tempFile -Encoding ASCII -NoNewline

$plinkArgs = "-ssh -batch -P 65002 -pw Hamo1510@Rayan146 u990109832@45.87.81.218 -m `"$tempFile`""

try {
    $result = & "C:\Program Files\PuTTY\plink.exe" $plinkArgs.Split(' ')
    Write-Host $result -ForegroundColor Cyan
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
} finally {
    Remove-Item $tempFile -ErrorAction SilentlyContinue
}

