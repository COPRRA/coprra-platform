# Fix all namespace corruption patterns in factory files
$factoryDir = "database\factories"
$files = Get-ChildItem -Path $factoryDir -Filter "*.php" -Recurse

$fixedCount = 0

foreach ($file in $files) {
    try {
        $content = Get-Content $file.FullName -Raw
        $originalContent = $content
        
        # Fix all known corruption patterns
        $content = $content -replace "namesfinal final pace", "namespace"
        $content = $content -replace "namefinal final space", "namespace"
        $content = $content -replace "namfinal final espace", "namespace"
        $content = $content -replace "nafinal final mespace", "namespace"
        $content = $content -replace "nfinal final amespace", "namespace"
        $content = $content -replace "final final namespace", "namespace"
        $content = $content -replace "final namespace", "namespace"
        $content = $content -replace "final mespace", "namespace"
        $content = $content -replace "final space", "namespace"
        $content = $content -replace "final pace", "namespace"
        $content = $content -replace "final espace", "namespace"
        
        # Additional patterns that might exist
        $content = $content -replace "nam.*final.*space", "namespace"
        $content = $content -replace "nam.*final.*pace", "namespace"
        $content = $content -replace "nam.*final.*espace", "namespace"
        $content = $content -replace "nam.*final.*mespace", "namespace"
        
        if ($content -ne $originalContent) {
            Set-Content $file.FullName -Value $content -NoNewline
            Write-Host "Fixed: $($file.Name)" -ForegroundColor Green
            $fixedCount++
        } else {
            Write-Host "No changes needed: $($file.Name)" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "Error processing $($file.FullName): $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host "`nFixed $fixedCount factory files" -ForegroundColor Cyan

# Also check scripts directory
$scriptsDir = "scripts"
if (Test-Path $scriptsDir) {
    $scriptFiles = Get-ChildItem -Path $scriptsDir -Filter "*.php" -Recurse
    
    foreach ($file in $scriptFiles) {
        try {
            $content = Get-Content $file.FullName -Raw
            $originalContent = $content
            
            # Fix corruption patterns in scripts
            $content = $content -replace "final final ", ""
            $content = $content -replace "namesfinal final pace", "namespace"
            $content = $content -replace "namefinal final space", "namespace"
            $content = $content -replace "namfinal final espace", "namespace"
            $content = $content -replace "nafinal final mespace", "namespace"
            $content = $content -replace "nfinal final amespace", "namespace"
            
            if ($content -ne $originalContent) {
                Set-Content $file.FullName -Value $content -NoNewline
                Write-Host "Fixed script: $($file.Name)" -ForegroundColor Green
                $fixedCount++
            }
        } catch {
            Write-Host "Error processing script $($file.FullName): $($_.Exception.Message)" -ForegroundColor Red
        }
    }
}

Write-Host "`nTotal fixed: $fixedCount files" -ForegroundColor Cyan