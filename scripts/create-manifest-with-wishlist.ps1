# Create manifest.json with wishlist.js included
$manifestContent = @{
    "resources/css/app.css" = @{
        "file" = "assets/app.css"
        "src" = "resources/css/app.css"
        "isEntry" = $true
    }
    "resources/js/app.js" = @{
        "file" = "assets/app.js"
        "src" = "resources/js/app.js"
        "isEntry" = $true
        "imports" = @(
            "resources/js/bootstrap.js",
            "resources/js/compare.js",
            "resources/js/wishlist.js",
            "resources/js/live-search.js"
        )
    }
    "resources/js/bootstrap.js" = @{
        "file" = "assets/bootstrap.js"
        "src" = "resources/js/bootstrap.js"
    }
    "resources/js/compare.js" = @{
        "file" = "assets/compare.js"
        "src" = "resources/js/compare.js"
    }
    "resources/js/wishlist.js" = @{
        "file" = "assets/wishlist.js"
        "src" = "resources/js/wishlist.js"
    }
    "resources/js/live-search.js" = @{
        "file" = "assets/live-search.js"
        "src" = "resources/js/live-search.js"
    }
} | ConvertTo-Json -Depth 10

$localManifest = "public/build/manifest.json"
$manifestContent | Out-File -FilePath $localManifest -Encoding UTF8 -NoNewline

Write-Host "Created manifest.json locally" -ForegroundColor Green

# Upload to server
$remoteManifest = "/home/u990109832/domains/coprra.com/public_html/public/build/manifest.json"

Write-Host "Uploading manifest.json to server..." -ForegroundColor Yellow

$pscpArgs = "-P 65002 -pw Hamo1510@Rayan146 `"$localManifest`" u990109832@45.87.81.218:`"$remoteManifest`""

try {
    & "C:\Program Files\PuTTY\pscp.exe" $pscpArgs.Split(' ')
    Write-Host "✅ Manifest uploaded successfully!" -ForegroundColor Green
} catch {
    Write-Host "Error uploading manifest: $_" -ForegroundColor Red
}

