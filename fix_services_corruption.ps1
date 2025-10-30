# PowerShell script to fix all remaining corruption patterns in Services directory

Write-Host "Starting comprehensive Services directory corruption fix..." -ForegroundColor Green

$fixedFiles = 0
$patterns = @(
    # Namespace corruptions
    @{ Pattern = "namespace App\\Servifinal final ces\\"; Replacement = "namespace App\Services\" },
    @{ Pattern = "namespace App\\Servfinal final ices\\"; Replacement = "namespace App\Services\" },
    @{ Pattern = "namespace App\\Services\\Apfinal final i;"; Replacement = "namespace App\Services\Api;" },
    @{ Pattern = "namespace App\\Sfinal final ervices\\"; Replacement = "namespace App\Services\" },
    @{ Pattern = "namespace App\\Services\\Acfinal final tivity\\"; Replacement = "namespace App\Services\Activity\" },
    @{ Pattern = "namespace Appfinal final \\Services;"; Replacement = "namespace App\Services;" },
    @{ Pattern = "namespace App\\Servicesfinal final \\"; Replacement = "namespace App\Services\" },
    
    # Use statement corruptions
    @{ Pattern = "use Illuminate\\Support\\Facfinal final ades\\"; Replacement = "use Illuminate\Support\Facades\" },
    @{ Pattern = "usefinal final  Illuminate\\"; Replacement = "use Illuminate\" },
    @{ Pattern = "use Illuminatfinal final e\\"; Replacement = "use Illuminate\" },
    @{ Pattern = "use Illumifinal final nate\\"; Replacement = "use Illuminate\" },
    @{ Pattern = "use App\\Services\\Backup\\Strategiesfinal final \\"; Replacement = "use App\Services\Backup\Strategies\" },
    
    # Generic final final patterns
    @{ Pattern = "final final "; Replacement = "" },
    @{ Pattern = "final final\t"; Replacement = "" },
    @{ Pattern = "final final\r"; Replacement = "" },
    @{ Pattern = "final final\n"; Replacement = "" }
)

# Get all PHP files in the Services directory
$phpFiles = Get-ChildItem -Path "app\Services" -Recurse -Filter "*.php" -File

foreach ($file in $phpFiles) {
    $content = Get-Content -Path $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    $fileChanged = $false
    
    foreach ($patternInfo in $patterns) {
        $pattern = $patternInfo.Pattern
        $replacement = $patternInfo.Replacement
        
        if ($content -match $pattern) {
            $content = $content -replace $pattern, $replacement
            $fileChanged = $true
            Write-Host "Fixed pattern '$pattern' in $($file.Name)" -ForegroundColor Yellow
        }
    }
    
    if ($fileChanged) {
        Set-Content -Path $file.FullName -Value $content -Encoding UTF8 -NoNewline
        $fixedFiles++
        Write-Host "Fixed file: $($file.FullName)" -ForegroundColor Green
    }
}

Write-Host "Services corruption fix completed. Fixed $fixedFiles files." -ForegroundColor Green