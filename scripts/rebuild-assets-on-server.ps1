# Rebuild assets on server
$commands = @"
cd /home/u990109832/domains/coprra.com/public_html
which npm 2>&1
echo '---'
node --version 2>&1
echo '---'
if command -v npm &> /dev/null; then
    echo 'NPM_FOUND'
    npm install 2>&1 | tail -20
    npm run build 2>&1 | tail -30
else
    echo 'NPM_NOT_FOUND'
fi
echo 'ASSETS_BUILD_COMPLETE'
"@

$tempFile = [System.IO.Path]::GetTempFileName()
$commands | Out-File -FilePath $tempFile -Encoding ASCII -NoNewline

$plinkArgs = "-ssh -batch -P 65002 -pw Hamo1510@Rayan146 u990109832@45.87.81.218 -m `"$tempFile`""

try {
    Write-Host "Checking for npm and rebuilding assets..." -ForegroundColor Yellow
    $result = & "C:\Program Files\PuTTY\plink.exe" $plinkArgs.Split(' ')
    Write-Host $result -ForegroundColor Cyan
    
    if ($result -match "NPM_NOT_FOUND") {
        Write-Host "`n⚠️ npm not found on server. Assets may need to be built locally and uploaded." -ForegroundColor Yellow
    } elseif ($result -match "ASSETS_BUILD_COMPLETE") {
        Write-Host "`n✅ Assets build process completed!" -ForegroundColor Green
    }
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
} finally {
    Remove-Item $tempFile -ErrorAction SilentlyContinue
}

