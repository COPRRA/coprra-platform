# PowerShell script to execute production setup on remote server via SSH
# This script uploads the fixed script and executes it on the production server

$ErrorActionPreference = "Stop"

# SSH Configuration
$sshHost = "45.87.81.218"
$sshPort = "65002"
$sshUser = "u990109832"
$sshPassword = "Hamo1510@Rayan146"
$projectPath = "/home/u990109832/domains/coprra.com/public_html"
$localScriptPath = Join-Path $PSScriptRoot "production-setup-complete.sh"
$remoteScriptPath = "$projectPath/scripts/production-setup-complete.sh"

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "Production Setup Script Executor" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "Connecting to: ${sshHost}:${sshPort}" -ForegroundColor Yellow
Write-Host "User: $sshUser" -ForegroundColor Yellow
Write-Host "Project Path: $projectPath" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Check if local script exists
if (-not (Test-Path $localScriptPath)) {
    Write-Host "❌ ERROR: Local script not found: $localScriptPath" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Local script found: $localScriptPath" -ForegroundColor Green
Write-Host ""

# Read script content
$scriptContent = Get-Content $localScriptPath -Raw -Encoding UTF8

# Escape script content for SSH
$escapedContent = $scriptContent -replace '"', '\"' -replace '\$', '\$' -replace '`', '\`'

Write-Host "🔌 Establishing SSH connection..." -ForegroundColor Yellow

# Create SSH command to upload and execute script
$sshCommands = @"
mkdir -p $projectPath/scripts
cat > $remoteScriptPath << 'SCRIPTEOF'
$scriptContent
SCRIPTEOF
chmod +x $remoteScriptPath
cd $projectPath && bash $remoteScriptPath
"@

# Use ssh with password via here-string
# Note: This requires sshpass or expect-like functionality
# For Windows, we'll use plink if available, otherwise manual SSH

# Check if plink is available
$plinkPath = Get-Command plink -ErrorAction SilentlyContinue

if ($plinkPath) {
    Write-Host "✅ Using Plink (PuTTY) for SSH connection" -ForegroundColor Green
    Write-Host ""
    
    # Create temporary command file
    $tempCmdFile = [System.IO.Path]::GetTempFileName()
    $sshCommands | Out-File -FilePath $tempCmdFile -Encoding ASCII -NoNewline
    
    # Execute via plink
    $plinkArgs = @(
        "-ssh",
        "-P", $sshPort,
        "-pw", $sshPassword,
        "${sshUser}@${sshHost}",
        "-m", $tempCmdFile
    )
    
    Write-Host "🚀 Executing production setup script..." -ForegroundColor Cyan
    Write-Host "----------------------------------------" -ForegroundColor Gray
    Write-Host ""
    
    & plink $plinkArgs
    
    $exitCode = $LASTEXITCODE
    
    # Cleanup
    Remove-Item $tempCmdFile -Force
    
    Write-Host ""
    Write-Host "----------------------------------------" -ForegroundColor Gray
    
    if ($exitCode -eq 0) {
        Write-Host "✅ SUCCESS: Production setup completed successfully!" -ForegroundColor Green
    } else {
        Write-Host "⚠️  WARNING: Script completed with exit code $exitCode" -ForegroundColor Yellow
    }
    
} else {
    # Try using native SSH with expect-like functionality
    Write-Host "⚠️  Plink not found. Attempting native SSH..." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Note: You may need to enter password manually" -ForegroundColor Yellow
    Write-Host ""
    
    # Create a PowerShell script that uses SSH interactively
    $sshScript = @"
`$securePassword = ConvertTo-SecureString "$sshPassword" -AsPlainText -Force
`$credential = New-Object System.Management.Automation.PSCredential("$sshUser", `$securePassword)

`$session = New-SSHSession -ComputerName $sshHost -Port $sshPort -Credential `$credential -AcceptKey

if (`$session) {
    `$result = Invoke-SSHCommand -SessionId `$session.SessionId -Command "$sshCommands"
    Write-Host `$result.Output
    Remove-SSHSession -SessionId `$session.SessionId
} else {
    Write-Host "Failed to establish SSH session"
    exit 1
}
"@
    
    # Check if Posh-SSH module is available
    $poshSsh = Get-Module -ListAvailable -Name Posh-SSH
    
    if ($poshSsh) {
        Import-Module Posh-SSH
        Invoke-Expression $sshScript
    } else {
        Write-Host "❌ ERROR: Neither Plink nor Posh-SSH module found." -ForegroundColor Red
        Write-Host ""
        Write-Host "Please install one of the following:" -ForegroundColor Yellow
        Write-Host "  1. PuTTY (includes plink.exe)" -ForegroundColor Yellow
        Write-Host "  2. Posh-SSH PowerShell module: Install-Module -Name Posh-SSH" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "OR manually execute:" -ForegroundColor Yellow
        Write-Host "  ssh -p $sshPort $sshUser@$sshHost" -ForegroundColor Cyan
        Write-Host "  Then run the commands manually on the server" -ForegroundColor Yellow
        exit 1
    }
}

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "✅ Execution Complete" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Cyan


