# Delete Placeholder Tests Script
# Purpose: Remove all placeholder test files with proper logging and verification

$ErrorActionPreference = "Continue"
$LogFile = "delete_placeholder_tests_log.txt"
$StartTime = Get-Date

Write-Output "=== Starting Placeholder Test Deletion ===" | Tee-Object -FilePath $LogFile
Write-Output "Start Time: $StartTime" | Tee-Object -FilePath $LogFile -Append

# Read list of files to delete
$FilesToDelete = Get-Content "placeholder-tests-to-delete.txt"
$TotalFiles = $FilesToDelete.Count
Write-Output "Total files to delete: $TotalFiles" | Tee-Object -FilePath $LogFile -Append

$DeletedCount = 0
$FailedCount = 0
$FailedFiles = @()

foreach ($File in $FilesToDelete) {
    try {
        if (Test-Path $File) {
            Remove-Item -Path $File -Force -ErrorAction Stop
            $DeletedCount++
            if ($DeletedCount % 50 -eq 0) {
                Write-Output "Progress: $DeletedCount / $TotalFiles deleted..." | Tee-Object -FilePath $LogFile -Append
            }
        } else {
            Write-Output "File not found: $File" | Tee-Object -FilePath $LogFile -Append
            $FailedCount++
        }
    }
    catch {
        Write-Output "Failed to delete: $File - Error: $_" | Tee-Object -FilePath $LogFile -Append
        $FailedCount++
        $FailedFiles += $File
    }
}

$EndTime = Get-Date
$Duration = $EndTime - $StartTime

Write-Output "`n=== Deletion Complete ===" | Tee-Object -FilePath $LogFile -Append
Write-Output "Successfully deleted: $DeletedCount files" | Tee-Object -FilePath $LogFile -Append
Write-Output "Failed to delete: $FailedCount files" | Tee-Object -FilePath $LogFile -Append
Write-Output "End Time: $EndTime" | Tee-Object -FilePath $LogFile -Append
Write-Output "Duration: $($Duration.TotalSeconds) seconds" | Tee-Object -FilePath $LogFile -Append

if ($FailedCount -gt 0) {
    Write-Output "`nFailed files:" | Tee-Object -FilePath $LogFile -Append
    $FailedFiles | ForEach-Object { Write-Output "  - $_" | Tee-Object -FilePath $LogFile -Append }
}

# Verify deletion
$RemainingFiles = Get-ChildItem -Path "tests/Unit" -Filter "*Test.php" -File | Where-Object { $_.Length -lt 300 }
Write-Output "`nVerification: $($RemainingFiles.Count) small files remain" | Tee-Object -FilePath $LogFile -Append

Write-Output "`n✅ Script completed. Check delete_placeholder_tests_log.txt for details." | Tee-Object -FilePath $LogFile -Append

# Return success/failure
if ($FailedCount -eq 0) {
    exit 0
} else {
    exit 1
}

