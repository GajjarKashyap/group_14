<?php
// config/db.php - Database Connection Configuration (PDO)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getPDO() {
    static $pdo = null;
    if ($pdo === null) {
        $host = 'localhost';
        $user = 'root';
        $pass = '';
        $dbname = 'perfume_hub';

        try {
            // First connect without dbname to ensure database exists
            $initPdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $initPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Now connect to perfume_hub
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            // Auto-setup database tables if they do not exist
            $tableExists = false;
            try {
                $q = $pdo->query("SELECT 1 FROM `products` LIMIT 1");
                $tableExists = true;
            } catch (Exception $e) {
                $tableExists = false;
            }

            if (!$tableExists) {
                $schemaFile = __DIR__ . '/../database/database_schema.sql';
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $pdo->exec($sql);
                }
            } else {
                // Verify added_by column exists
                $chkCol = $pdo->query("SHOW COLUMNS FROM `products` LIKE 'added_by'");
                if ($chkCol->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE `products` ADD COLUMN `added_by` VARCHAR(100) DEFAULT 'System Admin'");
                }
            }
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
    return $pdo;
}

function sanitize_input($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

if (!function_exists('optimize_image_url')) {
    function optimize_image_url($url, $type = 'thumbnail') {
        if (empty($url)) return 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=300';
        
        // Check if Amazon CDN
        if (stripos($url, 'media-amazon.com') !== false || stripos($url, 'images-amazon.com') !== false) {
            $replacement = '._AC_SL1200_.'; 
            if ($type === 'thumbnail') {
                $replacement = '._AC_SX200_.';
            } elseif ($type === 'card') {
                $replacement = '._AC_SX400_.';
            }
            
            // Replace Amazon size descriptors
            $modified_url = preg_replace('/\._[A-Za-z0-9,_\-]+_\./i', $replacement, $url);
            if ($modified_url) {
                return $modified_url;
            }
            return $url;
        }
        
        // Check if Unsplash
        if (stripos($url, 'images.unsplash.com') !== false) {
            $url = preg_replace('/[?&](w|q|fm|fit|h|crop)=[^&]+/i', '', $url);
            $connector = (strpos($url, '?') === false) ? '?' : '&';
            
            if ($type === 'thumbnail') {
                $url .= $connector . 'w=250&q=70&fm=webp';
            } elseif ($type === 'card') {
                $url .= $connector . 'w=500&q=75&fm=webp';
            } else {
                $url .= $connector . 'w=1200&q=85&fm=webp';
            }
            return $url;
        }
        
        return $url;
    }
}

?>
