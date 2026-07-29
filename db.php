<?php
$conn = mysqli_connect("localhost", "root", "");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `perfume_hub` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_select_db($conn, "perfume_hub");

// Auto-setup database tables if they do not exist
$chk_table = mysqli_query($conn, "SHOW TABLES LIKE 'products'");
if ($chk_table && mysqli_num_rows($chk_table) == 0) {
    $schemaFile = __DIR__ . '/database/database_schema.sql';
    if (file_exists($schemaFile)) {
        $sql = file_get_contents($schemaFile);
        if (mysqli_multi_query($conn, $sql)) {
            do {
                if ($res = mysqli_store_result($conn)) {
                    mysqli_free_result($res);
                }
            } while (mysqli_next_result($conn));
        }
    }
} else {
    // Verify added_by column exists
    $chk_col = mysqli_query($conn, "SHOW COLUMNS FROM `products` LIKE 'added_by'");
    if ($chk_col && mysqli_num_rows($chk_col) == 0) {
        mysqli_query($conn, "ALTER TABLE `products` ADD COLUMN `added_by` VARCHAR(100) DEFAULT 'System Admin'");
    }
}

if (!function_exists('optimize_image_url')) {
    function optimize_image_url($url, $type = 'thumbnail') {
        if (empty($url)) return 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=300';
        
        if (stripos($url, 'media-amazon.com') !== false || stripos($url, 'images-amazon.com') !== false) {
            $replacement = '._AC_SL1200_.'; 
            if ($type === 'thumbnail') {
                $replacement = '._AC_SX200_.';
            } elseif ($type === 'card') {
                $replacement = '._AC_SX400_.';
            }
            $modified_url = preg_replace('/\._[A-Za-z0-9,_\-]+_\./i', $replacement, $url);
            if ($modified_url) return $modified_url;
            return $url;
        }
        
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