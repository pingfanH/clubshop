<?php
echo "<pre>";
$runtime = dirname(__DIR__) . '/runtime';
echo "Runtime Path: $runtime\n";
echo "Runtime Perms: " . substr(sprintf('%o', fileperms($runtime)), -4) . "\n";
echo "Runtime Owner: " . posix_getpwuid(fileowner($runtime))['name'] . "\n";
echo "Current User: " . shell_exec('whoami') . "\n";

// Try to chmod
if (!is_writable($runtime)) {
    echo "Runtime is not writable. Attempting chmod...\n";
    @chmod($runtime, 0777);
    if (is_writable($runtime)) {
        echo "Chmod success!\n";
    } else {
        echo "Chmod failed.\n";
    }
} else {
    echo "Runtime is writable.\n";
}

// Try to run the update command
$rootPath = dirname(__DIR__);
// Add 2>&1 to capture stderr
$cmd = "cd {$rootPath} && php think update_schema 2>&1";
echo "Executing: {$cmd}\n";
echo "Output:\n";
echo shell_exec($cmd);
echo "</pre>";
