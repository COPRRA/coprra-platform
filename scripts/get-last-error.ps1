# Get last specific error

Write-Host "Getting last error..." -ForegroundColor Yellow

$commands = @"
cd /home/u990109832/domains/coprra.com/public_html
tail -100 storage/logs/laravel.log | grep -A 10 "ERROR\|Exception\|Error" | tail -30
echo 'ERROR_END'
"@

$tempFile = [System.IO.Path]::GetTempFileName()
$commands | Out-File -FilePath $tempFile -Encoding ASCII -NoNewline

$plinkArgs = "-ssh -batch -P 65002 -pw Hamo1510@Rayan146 u990109832@45.87.81.218 -m `"$tempFile`""

try {
    $result = & "C:\Program Files\PuTTY\plink.exe" $plinkArgs.Split(' ')
    Write-Host $result -ForegroundColor Red
} catch {
    Write-Host "❌ Failed: $_" -ForegroundColor Red
} finally {
    Remove-Item $tempFile -ErrorAction SilentlyContinue
}

