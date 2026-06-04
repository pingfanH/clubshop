<?php
try {
    $dsn = "mysql:host=127.0.0.1;dbname=yoshop_pro2;charset=utf8";
    $pdo = new \PDO($dsn, 'yoshop_pro2', 'd4JMFkRsmMJRnJt4');
    
    // Get all menus
    $stmt = $pdo->query("SELECT * FROM yoshop_store_menu ORDER BY sort ASC, create_time ASC");
    $menus = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    echo "Total Menus: " . count($menus) . "\n";
    foreach ($menus as $menu) {
        echo "ID: " . $menu['menu_id'] . " | Name: " . $menu['name'] . " | Parent: " . $menu['parent_id'] . "\n";
    }
    
} catch (\PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
