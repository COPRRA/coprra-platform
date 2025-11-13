# Check server logs for errors

Write-Host "Checking server logs..." -ForegroundColor Yellow

$commands = @"
cd /home/u990109832/domains/coprra.com/public_html
tail -50 storage/logs/laravel.log
echo 'LOGS_END'
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

