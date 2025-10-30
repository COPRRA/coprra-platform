# PowerShell script to fix all remaining corruption patterns in app directory

Write-Host "Starting comprehensive app directory corruption fix..." -ForegroundColor Green

$fixedFiles = 0
$patterns = @(
    # Namespace corruptions
    @{ Pattern = "namespace Apfinal final p\\"; Replacement = "namespace App\" },
    @{ Pattern = "namfinal final espace "; Replacement = "namespace " },
    @{ Pattern = "namesfinal final pace "; Replacement = "namespace " },
    @{ Pattern = "namespafinal final ce "; Replacement = "namespace " },
    
    # Use statement corruptions
    @{ Pattern = "use final final "; Replacement = "use " },
    @{ Pattern = "use Illumifinal final nate\\"; Replacement = "use Illuminate\" },
    @{ Pattern = "use Illuminatefinal final \\"; Replacement = "use Illuminate\" },
    @{ Pattern = "use App\\Serfinal final vices\\"; Replacement = "use App\Services\" },
    @{ Pattern = "use App\\Http\\Requesfinal final ts\\"; Replacement = "use App\Http\Requests\" },
    @{ Pattern = "use Apfinal final p\\"; Replacement = "use App\" },
    @{ Pattern = "use Illuminate\\Database\\Eloquent\\Ffinal final actories\\"; Replacement = "use Illuminate\Database\Eloquent\Factories\" },
    
    # Property and comment corruptions
    @{ Pattern = "\\* @property.*?creatfinal final ed_at"; Replacement = "* @property \Carbon\Carbon|null `$created_at" },
    @{ Pattern = "\\* @property.*?metadfinal final ata"; Replacement = "* @property array<string, string|int|null>|null `$metadata" },
    @{ Pattern = "\\* @property.*?final final "; Replacement = "* @property " },
    @{ Pattern = "\\* @property.*?Carbon\\|nulfinal final l"; Replacement = "* @property Carbon|null" },
    @{ Pattern = "\\* @method.*?boolfinal final \\|null>"; Replacement = "* @method static \App\Models\Brand create(array<string, string|bool|null>" },
    @{ Pattern = "\\* @profinal perfinal final ty"; Replacement = "* @property" },
    @{ Pattern = "\\* @propertfinal final y"; Replacement = "* @property" },
    @{ Pattern = "\\* @profinal final perty"; Replacement = "* @property" },
    @{ Pattern = "\\* @prfinal final operty"; Replacement = "* @property" },
    @{ Pattern = "stringfinal final "; Replacement = "string " },
    @{ Pattern = "\\*final final "; Replacement = "* " },
    @{ Pattern = "int \\$helpful_coufinal final nt"; Replacement = "int `$helpful_count" },
    @{ Pattern = "Collection<int, Pricfinal final eAlert>"; Replacement = "Collection<int, PriceAlert>" },
    @{ Pattern = "float \\$ratefinal final"; Replacement = "float `$rate" },
    @{ Pattern = "array<string, string\\|int\\|bool\\|nufinal final ll>"; Replacement = "array<string, string|int|bool|null>" },
    @{ Pattern = "bool \\$in_stock"; Replacement = "bool `$in_stock" },
    @{ Pattern = "\\\\final final Illuminate"; Replacement = "\Illuminate" },
    
    # Generic final final patterns
    @{ Pattern = "final final "; Replacement = "" },
    @{ Pattern = "final final\t"; Replacement = "" },
    @{ Pattern = "final final\r"; Replacement = "" },
    @{ Pattern = "final final\n"; Replacement = "" }
)

# Get all PHP files in the app directory
$phpFiles = Get-ChildItem -Path "app" -Recurse -Filter "*.php" -File

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

Write-Host "App corruption fix completed. Fixed $fixedFiles files." -ForegroundColor Green