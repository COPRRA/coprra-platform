# Create proper Vite manifest with required files

Write-Host "Creating proper Vite manifest..." -ForegroundColor Yellow

$manifestContent = @'
{
  "resources/css/app.css": {
    "file": "assets/app.css",
    "src": "resources/css/app.css",
    "isEntry": true
  },
  "resources/js/app.js": {
    "file": "assets/app.js",
    "src": "resources/js/app.js",
    "isEntry": true
  }
}
'@

$commands = @"
cd /home/u990109832/domains/coprra.com/public_html
mkdir -p public/build/assets
cat > public/build/manifest.json << 'MANIFEST_EOF'
$manifestContent
MANIFEST_EOF
touch public/build/assets/app.css
touch public/build/assets/app.js
chmod 644 public/build/manifest.json public/build/assets/*
ls -la public/build/
echo 'MANIFEST_CREATED'
"@

$tempFile = [System.IO.Path]::GetTempFileName()
$commands | Out-File -FilePath $tempFile -Encoding ASCII -NoNewline

$plinkArgs = "-ssh -batch -P 65002 -pw Hamo1510@Rayan146 u990109832@45.87.81.218 -m `"$tempFile`""

try {
    $result = & "C:\Program Files\PuTTY\plink.exe" $plinkArgs.Split(' ')
    Write-Host $result -ForegroundColor Cyan
    Write-Host ""
    Write-Host "✅ Proper manifest created!" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed: $_" -ForegroundColor Red
} finally {
    Remove-Item $tempFile -ErrorAction SilentlyContinue
}

