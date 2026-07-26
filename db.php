<?php
$conn = mysqli_connect("localhost", "root", "");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `perfume_hub` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_select_db($conn, "perfume_hub");

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