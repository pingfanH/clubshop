<?php
namespace app;
require __DIR__ . '/vendor/autoload.php';

use think\App;
use app\store\model\store\Menu as MenuModel;

$http = (new App())->http;
$response = $http->run();

// Initialize DB connection manually if needed or rely on framework boot
// Since we are outside the framework lifecycle, we might need to bootstrap it.
// Simpler approach: Use the framework's console or just direct DB query if possible.
// But direct DB requires credentials.

// Let's try to just use the model if the app is bootstrapped enough.
// Actually, ThinkPHP 6 needs full bootstrap.

// Let's try to read the database configuration first to connect directly via PDO.
$config = require __DIR__ . '/config/database.php';
$dbConfig = $config['connections']['mysql'];

try {
    $dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new \PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    
    $stmt = $pdo->query("SELECT * FROM yoshop_store_menu ORDER BY sort ASC, create_time ASC");
    $menus = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    foreach ($menus as $menu) {
        echo "ID: " . $menu['menu_id'] . " | Name: " . $menu['name'] . " | Parent: " . $menu['parent_id'] . "\n";
    }
    
} catch (\PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
