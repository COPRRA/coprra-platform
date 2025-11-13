# Check .env configuration

Write-Host "Checking .env configuration..." -ForegroundColor Yellow

$commands = @"
cd /home/u990109832/domains/coprra.com/public_html
echo '=== APP Configuration ==='
grep -E '^APP_|^DB_|^CACHE_|^SESSION_' .env | head -20
echo ''
echo '=== Checking APP_KEY ==='
grep 'APP_KEY=' .env
echo ''
echo '=== Checking file permissions ==='
ls -la storage/logs/ | head -5
ls -la bootstrap/cache/ | head -5
echo 'ENV_CHECK_DONE'
"@

$tempFile = [System.IO.Path]::GetTempFileName()
$commands | Out-File -FilePath $tempFile -Encoding ASCII -NoNewline

$plinkArgs = "-ssh -batch -P 65002 -pw Hamo1510@Rayan146 u990109832@45.87.81.218 -m `"$tempFile`""

try {
    $result = & "C:\Program Files\PuTTY\plink.exe" $plinkArgs.Split(' ')
    Write-Host $result -ForegroundColor Cyan
} catch {
    Write-Host "❌ Failed: $_" -ForegroundColor Red
} finally {
    Remove-Item $tempFile -ErrorAction SilentlyContinue
}

