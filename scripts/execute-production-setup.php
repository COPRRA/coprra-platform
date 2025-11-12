<?php

/**
 * Execute Production Setup Script on Remote Server via SSH
 * 
 * This script connects to the production server via SSH and executes
 * the production-setup-complete.sh script remotely.
 */

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use phpseclib3\Net\SSH2;

// SSH Configuration
$sshConfig = [
    'host' => '45.87.81.218',
    'port' => 65002,
    'username' => 'u990109832',
    'password' => 'Hamo1510@Rayan146',
    'project_path' => '/home/u990109832/domains/coprra.com/public_html',
];

echo "============================================================\n";
echo "Production Setup Script Executor\n";
echo "============================================================\n";
echo "Connecting to: {$sshConfig['host']}:{$sshConfig['port']}\n";
echo "User: {$sshConfig['username']}\n";
echo "Project Path: {$sshConfig['project_path']}\n";
echo "============================================================\n\n";

try {
    // Connect to SSH
    echo "🔌 Establishing SSH connection...\n";
    $ssh = new SSH2($sshConfig['host'], $sshConfig['port']);
    
    if (!$ssh->login($sshConfig['username'], $sshConfig['password'])) {
        throw new Exception('SSH login failed. Please check credentials.');
    }
    
    echo "✅ SSH connection established successfully!\n\n";
    
    // First, upload the fixed script to the server
    echo "📤 Uploading fixed production-setup-complete.sh script...\n";
    
    $localScriptPath = __DIR__ . '/production-setup-complete.sh';
    $remoteScriptPath = $sshConfig['project_path'] . '/scripts/production-setup-complete.sh';
    
    if (!file_exists($localScriptPath)) {
        throw new Exception("Local script not found: {$localScriptPath}");
    }
    
    $scriptContent = file_get_contents($localScriptPath);
    
    // Create scripts directory if it doesn't exist
    $ssh->exec("mkdir -p {$sshConfig['project_path']}/scripts");
    
    // Upload script content
    $ssh->exec("cat > {$remoteScriptPath} << 'SCRIPTEOF'\n{$scriptContent}\nSCRIPTEOF");
    
    // Make script executable
    $ssh->exec("chmod +x {$remoteScriptPath}");
    
    echo "✅ Script uploaded and made executable\n\n";
    
    // Now execute the script
    echo "============================================================\n";
    echo "🚀 Executing Production Setup Script\n";
    echo "============================================================\n\n";
    
    // Change to project directory and execute
    $command = "cd {$sshConfig['project_path']} && bash {$remoteScriptPath} 2>&1";
    
    echo "Executing: {$command}\n";
    echo "----------------------------------------\n\n";
    
    // Execute and capture output in real-time
    $ssh->setTimeout(600); // 10 minutes timeout
    
    $output = $ssh->exec($command);
    $exitStatus = $ssh->getExitStatus();
    
    // Display output
    echo $output;
    
    echo "\n----------------------------------------\n";
    echo "Exit Status: {$exitStatus}\n";
    
    if ($exitStatus === 0) {
        echo "\n✅ SUCCESS: Production setup completed successfully!\n";
    } else {
        echo "\n⚠️  WARNING: Script completed with exit status {$exitStatus}\n";
        echo "Please review the output above for any errors.\n";
    }
    
    // Disconnect
    $ssh->disconnect();
    
    echo "\n============================================================\n";
    echo "✅ Execution Complete\n";
    echo "============================================================\n";
    
    exit($exitStatus === 0 ? 0 : 1);
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}


