# Quick fix: Update composer.lock on server
$commands = @"
cd /home/u990109832/domains/coprra.com/public_html
composer update laravel/socialite --no-interaction 2>&1
composer install --no-dev --optimize-autoloader 2>&1
php artisan config:cache 2>&1
php artisan route:cache 2>&1
echo 'COMPOSER_FIXED'
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

