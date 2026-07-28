<?php
// fetch_product_info.php - Hybrid Intelligent Web Fetcher & URL Fallback Engine
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$raw_url = $_REQUEST['url'] ?? '';
$url = trim($raw_url);

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'error' => 'Please provide a valid HTTP/HTTPS product URL.']);
    exit();
}

// 1. URL PATH PARSER (Always parsed first as reference/fallback)
$url_data = parse_amazon_url_fallback($url);

// 2. HTTP CRAWLER WITH BROWSER EMULATION
$html = '';
$http_code = 0;
$is_blocked = false;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
curl_setopt($ch, CURLOPT_ENCODING, ""); // Handle gzip
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
    'Accept-Language: en-US,en;q=0.9',
    'Cache-Control: no-cache',
    'Pragma: no-cache',
    'Upgrade-Insecure-Requests: 1'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$html = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

// Detect Amazon Bot Challenge / Captcha
if (empty($html) || $http_code >= 400 || strpos($html, 'Robot Check') !== false || strpos($html, 'captcha') !== false || strpos($html, 'api-services-support') !== false) {
    $is_blocked = true;
}

// 3. PARSING LOGIC
$name = '';
$brand = '';
$mrp = 0.00;
$price = 0.00;
$images = [];

if (!$is_blocked) {
    // A. Parse Product Name / Title
    if (preg_match('/id=["\']productTitle["\'][^>]*>\s*([^<]+)/i', $html, $m)) {
        $name = trim($m[1]);
    }
    if (empty($name) && preg_match('/<meta[^>]+property=["\']og:title["\'][^]+content=["\']([^"\']+)["\']/i', $html, $m)) {
        $name = trim($m[1]);
    }
    if (empty($name) && preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $m)) {
        $name = trim($m[1]);
    }

    // Clean name
    $name = preg_replace('/^Amazon\.in\s*:\s*/i', '', $name);
    $name = preg_replace('/\s*:\s*Amazon\.(in|com|co\.uk).*$/i', '', $name);
    $name = preg_replace('/\s*[\|-]\s*(Buy|Online|Low Price|Flipkart|Amazon).*$/i', '', $name);
    $name = html_entity_decode(trim($name), ENT_QUOTES, 'UTF-8');

    // B. Parse Brand Name
    if (preg_match('/id=["\']bylineInfo["\'][^>]*>\s*(?:Visit the\s+)?(?:Brand:\s*)?([^<]+)/i', $html, $m)) {
        $b_raw = trim($m[1]);
        $b_raw = preg_replace('/^(Visit the|Brand:|\s*Store)/i', '', $b_raw);
        $b_raw = preg_replace('/Store$/i', '', $b_raw);
        $brand = trim($b_raw);
    }

    // C. Parse Prices
    if (preg_match('/<span[^>]*class=["\'][^"\']*a-price-whole[^"\']*["\'][^>]*>([0-9,]+)/i', $html, $m)) {
        $price = floatval(str_replace(',', '', $m[1]));
    }
    if ($price <= 0 && preg_match('/<span[^>]*id=["\']priceblock_ourprice["\'][^>]*>₹?\s*([0-9,]+(?:\.[0-9]{2})?)/i', $html, $m)) {
        $price = floatval(str_replace(',', '', $m[1]));
    }
    if (preg_match('/<span[^>]*class=["\'][^"\']*a-text-price[^"\']*["\'][^>]*>.*?<span[^>]*class=["\']a-offscreen["\'][^>]*>₹?\s*([0-9,]+)/is', $html, $m)) {
        $mrp = floatval(str_replace(',', '', $m[1]));
    }
}

// 4. FALLBACK RESOLVER (If blocked or extraction failed)
if (empty($name) || strlen($name) < 4) {
    $name = $url_data['name'];
}
if (empty($brand) || $brand === 'Perfume Hub') {
    $brand = $url_data['brand'];
}

// Ensure first word of name matches Brand for cleaner visual consistency
if (!empty($brand) && stripos($name, $brand) === false) {
    $name = $brand . ' ' . $name;
}

// Price Fallback Engine based on Brand segment
if ($price <= 0) {
    $lower_brand = strtolower($brand);
    if (strpos($lower_brand, 'creed') !== false || strpos($lower_brand, 'kurkdjian') !== false || strpos($lower_brand, 'roja') !== false) {
        $price = 24500.00;
        $mrp = 28000.00;
    } elseif (strpos($lower_brand, 'tom ford') !== false || strpos($lower_brand, 'chanel') !== false || strpos($lower_brand, 'dior') !== false) {
        $price = 14800.00;
        $mrp = 17500.00;
    } elseif (strpos($lower_brand, 'davidoff') !== false || strpos($lower_brand, 'calvin') !== false || strpos($lower_brand, 'boss') !== false) {
        $price = 4800.00;
        $mrp = 6500.00;
    } else {
        $price = 7800.00;
        $mrp = 9500.00;
    }
}
if ($mrp <= 0) {
    $mrp = round($price * 1.18, 2);
}

// Image Fallback Engine
if (!$is_blocked) {
    // Parse dynamic Amazon images
    if (preg_match('/data-a-dynamic-image=["\']({[^"\']+\})["\']/i', $html, $m)) {
        $img_json = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        $decoded = json_decode($img_json, true);
        if (is_array($decoded)) {
            foreach (array_keys($decoded) as $img_url) {
                if (filter_var($img_url, FILTER_VALIDATE_URL)) {
                    $images[] = $img_url;
                }
            }
        }
    }
    // Direct regex images
    if (preg_match_all('/https:\/\/m\.media-amazon\.com\/images\/I\/[A-Za-z0-9%_\-\.\+]+\.jpg/i', $html, $m)) {
        foreach ($m[0] as $img_url) {
            $high_res_url = preg_replace('/\._SX[0-9]+_\./i', '._AC_SL1500_.', $img_url);
            $high_res_url = preg_replace('/\._SY[0-9]+_\./i', '._AC_SL1500_.', $high_res_url);
            $high_res_url = preg_replace('/\._AC_SR[0-9,]+_\./i', '._AC_SL1500_.', $high_res_url);
            if (!in_array($high_res_url, $images)) {
                $images[] = $high_res_url;
            }
        }
    }
}

// Fallback images based on Fragrance/Category Keywords
if (empty($images)) {
    $lower_name = strtolower($name);
    if (strpos($lower_name, 'blue') !== false || strpos($lower_name, 'water') !== false || strpos($lower_name, 'aqua') !== false || strpos($lower_name, 'fresh') !== false) {
        $images = [
            'https://images.unsplash.com/photo-1547887537-6158d64c35b3?w=800',
            'https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=800'
        ];
    } elseif (strpos($lower_name, 'gold') !== false || strpos($lower_name, 'oud') !== false || strpos($lower_name, 'amber') !== false || strpos($lower_name, 'royal') !== false || strpos($lower_name, 'creed') !== false) {
        $images = [
            'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=800',
            'https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=800'
        ];
    } elseif (strpos($lower_name, 'rose') !== false || strpos($lower_name, 'floral') !== false || strpos($lower_name, 'woman') !== false || strpos($lower_name, 'women') !== false) {
        $images = [
            'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?w=800',
            'https://images.unsplash.com/photo-1547887537-6158d64c35b3?w=800'
        ];
    } else {
        $images = [
            'https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=800',
            'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=800'
        ];
    }
}

$images = array_unique($images);
$images = array_values(array_slice($images, 0, 5));

echo json_encode([
    'success' => true,
    'name' => htmlspecialchars_decode($name, ENT_QUOTES),
    'brand' => htmlspecialchars_decode($brand, ENT_QUOTES),
    'mrp' => $mrp,
    'price' => $price,
    'image_urls' => $images,
    'fallback_used' => $is_blocked
]);
exit();

// Fallback path segment parser function
function parse_amazon_url_fallback($url) {
    $parsed = parse_url($url);
    $path = $parsed['path'] ?? '';
    
    $name = '';
    $brand = 'Perfume Hub';

    $segments = explode('/', trim($path, '/'));
    foreach ($segments as $seg) {
        if ($seg === 'dp' || $seg === 'gp' || (strlen($seg) === 10 && preg_match('/^[A-Z0-9]{10}$/i', $seg))) {
            continue;
        }
        if (strlen($seg) > 5 && strpos($seg, '-') !== false) {
            $name = $seg;
            break;
        }
    }

    if (empty($name) && !empty($segments)) {
        foreach ($segments as $seg) {
            if (strlen($seg) > 3 && $seg !== 'dp' && $seg !== 'gp') {
                $name = $seg;
                break;
            }
        }
    }

    if (!empty($name)) {
        $name = str_replace(['-', '_', '+'], ' ', $name);
        $name = urldecode($name);
        $name = ucwords(trim($name));
    }

    $name = preg_replace('/Ref=.*$/i', '', $name);
    $name = trim($name);

    $brands_list = ['Creed', 'Tom Ford', 'Dior', 'Chanel', 'Gucci', 'Versace', 'Armani', 'Yves Saint Laurent', 'YSL', 'Lattafa', 'Rasasi', 'Afnan', 'Jaguar', 'Calvin Klein', 'CK', 'Davidoff', 'Paco Rabanne', 'Hugo Boss', 'Burberry', 'Bvlgari', 'Jo Malone', 'Maison Francis Kurkdjian', 'MFK'];
    foreach ($brands_list as $b) {
        if (stripos($name, $b) !== false) {
            $brand = $b;
            break;
        }
    }

    if ($brand === 'Perfume Hub' && !empty($name)) {
        $first_word = strtok($name, " ");
        if (strlen($first_word) > 2) {
            $brand = $first_word;
        }
    }

    return [
        'name' => $name,
        'brand' => $brand
    ];
}
?>
