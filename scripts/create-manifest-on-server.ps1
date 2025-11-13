# Create manifest.json directly on server
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
echo 'MANIFEST_CREATED'
"@

$tempFile = [System.IO.Path]::GetTempFileName()
$commands | Out-File -FilePath $tempFile -Encoding ASCII -NoNewline

$plinkArgs = "-ssh -batch -P 65002 -pw Hamo1510@Rayan146 u990109832@45.87.81.218 -m `"$tempFile`""

try {
    Write-Host "Creating manifest.json on server..." -ForegroundColor Yellow
    $result = & "C:\Program Files\PuTTY\plink.exe" $plinkArgs.Split(' ')
    Write-Host $result -ForegroundColor Cyan
    
    if ($result -match "MANIFEST_CREATED") {
        Write-Host "`n✅ Manifest created successfully!" -ForegroundColor Green
    }
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
} finally {
    Remove-Item $tempFile -ErrorAction SilentlyContinue
}

