<?php
// setup_database.php - Self-executing setup script for Perfume Hub
require_once __DIR__ . '/config/db.php';

try {
    $pdo = getPDO();

    // 1. Run schema DDL statement by statement
    $sql_schema = file_get_contents(__DIR__ . '/database/database_schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql_schema)));
    foreach ($statements as $stmt_sql) {
        if (!empty($stmt_sql)) {
            $pdo->exec($stmt_sql);
        }
    }

    // Ensure columns exist on products table if upgraded
    $cols = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('brand', $cols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN `brand` VARCHAR(255) DEFAULT 'Perfume Hub' AFTER `name` ");
    }
    if (!in_array('mrp', $cols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN `mrp` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `category` ");
    }
    if (!in_array('image_urls', $cols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN `image_urls` TEXT AFTER `image_url` ");
    }

    // 2. Populate Sample Fragrances if table is empty
    $check_stmt = $pdo->query("SELECT COUNT(*) FROM fragrances");
    if ($check_stmt->fetchColumn() == 0) {
        
        // Sample Fragrance 1: Creed Aventus
        $pdo->exec("INSERT INTO `fragrances` 
            (`fragrance_code`, `catalogue_number`, `fragrance_name`, `url_key`, `house_id`, `collection_id`, `segment_id`, `audience`, `family_id`, `strength_id`, `perfumer_id`, `primary_volume_ml`, `summary`, `full_story`, `origin_country`, `release_year`, `list_price`, `offer_price`, `discount_rate`, `available_units`, `availability_state`, `record_state`, `data_quality_score`, `featured_flag`) 
            VALUES 
            ('PH-MEN-WDY-0001', 'CAT-CRD-001', 'Creed Aventus', 'creed-aventus-parfum', 1, 1, 1, 'Men', 2, 2, 1, 100, 
            'The legendary luxury fragrance for men celebrating strength, power and success.', 
            'Sensual, audacious and contemporary, Aventus combines head notes of lemon, pink pepper and Italian bergamot with a rich heart of pineapple, sweet jasmine and Indonesian patchouli. The dry-down reveals oakmoss, cedarwood and Creed signature ambergris.', 
            'France', 2010, 32000.00, 28500.00, 10.94, 25, 'Ready', 'Published', 100, 1)");
        $f1_id = $pdo->lastInsertId();

        $pdo->exec("INSERT INTO `fragrance_notes` (`fragrance_id`, `note_stage`, `ingredient_name`, `display_position`) VALUES
            ($f1_id, 'opening', 'Italian Bergamot', 1),
            ($f1_id, 'opening', 'Pink Pepper', 2),
            ($f1_id, 'opening', 'Crisp Pineapple', 3),
            ($f1_id, 'heart', 'Moroccan Jasmine', 1),
            ($f1_id, 'heart', 'Birch & Patchouli', 2),
            ($f1_id, 'dry_down', 'Oakmoss', 1),
            ($f1_id, 'dry_down', 'Ambergris', 2),
            ($f1_id, 'dry_down', 'Cedarwood', 3)");

        $pdo->exec("INSERT INTO `fragrance_volumes` (`fragrance_id`, `volume_label`, `volume_ml`, `volume_price`, `volume_list_price`, `is_default_volume`) VALUES
            ($f1_id, '50 ml', 50, 18500.00, 21000.00, 0),
            ($f1_id, '100 ml', 100, 28500.00, 32000.00, 1),
            ($f1_id, '200 ml', 200, 45000.00, 50000.00, 0)");

        $pdo->exec("INSERT INTO `fragrance_editions` (`fragrance_id`, `edition_title`, `edition_details`) VALUES
            ($f1_id, 'Retail Pack', 'Standard luxury box with authenticity certificate'),
            ($f1_id, 'Tester Pack', 'Original tester in plain unsealed white box'),
            ($f1_id, 'Gift Box', 'Includes 100ml Parfum + 10ml Travel Spray')");

        $pdo->exec("INSERT INTO `fragrance_media` (`fragrance_id`, `remote_image_address`, `media_origin`, `image_type`, `featured_image`) VALUES
            ($f1_id, 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=600&auto=format&fit=crop&q=80', 'external_link', 'Main', 1),
            ($f1_id, 'https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=600&auto=format&fit=crop&q=80', 'external_link', 'Gallery', 0),
            ($f1_id, 'https://images.unsplash.com/photo-1541643600914-78b084683601?w=600&auto=format&fit=crop&q=80', 'external_link', 'Gallery', 0)");

        // Sample Fragrance 2: Tom Ford Black Orchid
        $pdo->exec("INSERT INTO `fragrances` 
            (`fragrance_code`, `catalogue_number`, `fragrance_name`, `url_key`, `house_id`, `collection_id`, `segment_id`, `audience`, `family_id`, `strength_id`, `perfumer_id`, `primary_volume_ml`, `summary`, `full_story`, `origin_country`, `release_year`, `list_price`, `offer_price`, `discount_rate`, `available_units`, `availability_state`, `record_state`, `data_quality_score`, `featured_flag`) 
            VALUES 
            ('PH-UNI-OUD-0002', 'CAT-TF-002', 'Tom Ford Black Orchid', 'tom-ford-black-orchid', 2, 1, 3, 'Unisex', 4, 2, 3, 100, 
            'A luxurious and sensual fragrance of rich, dark accords and an alluring potion of black orchids.', 
            'Black Orchid is a opulent, dark, and sensual fragrance with a rich trace of black orchid, spices, and dark accords. It is both modern and timeless.', 
            'United States', 2006, 18500.00, 15900.00, 14.05, 18, 'Ready', 'Published', 95, 1)");
        $f2_id = $pdo->lastInsertId();

        $pdo->exec("INSERT INTO `fragrance_notes` (`fragrance_id`, `note_stage`, `ingredient_name`, `display_position`) VALUES
            ($f2_id, 'opening', 'Black Truffle', 1),
            ($f2_id, 'opening', 'Ylang-Ylang', 2),
            ($f2_id, 'opening', 'Bergamot', 3),
            ($f2_id, 'heart', 'Black Orchid', 1),
            ($f2_id, 'heart', 'Lotus Wood', 2),
            ($f2_id, 'dry_down', 'Patchouli', 1),
            ($f2_id, 'dry_down', 'Incense & Vanilla', 2),
            ($f2_id, 'dry_down', 'Sandalwood', 3)");

        $pdo->exec("INSERT INTO `fragrance_volumes` (`fragrance_id`, `volume_label`, `volume_ml`, `volume_price`, `volume_list_price`, `is_default_volume`) VALUES
            ($f2_id, '50 ml', 50, 11500.00, 13000.00, 0),
            ($f2_id, '100 ml', 100, 15900.00, 18500.00, 1)");

        $pdo->exec("INSERT INTO `fragrance_editions` (`fragrance_id`, `edition_title`, `edition_details`) VALUES
            ($f2_id, 'Retail Pack', 'Standard gold ribbed luxury bottle'),
            ($f2_id, 'Tester Pack', 'Tester unit direct from manufacturer')");

        $pdo->exec("INSERT INTO `fragrance_media` (`fragrance_id`, `remote_image_address`, `media_origin`, `image_type`, `featured_image`) VALUES
            ($f2_id, 'https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=600&auto=format&fit=crop&q=80', 'external_link', 'Main', 1),
            ($f2_id, 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?w=600&auto=format&fit=crop&q=80', 'external_link', 'Gallery', 0)");

        // Sample Fragrance 3: Dior Sauvage Elixir
        $pdo->exec("INSERT INTO `fragrances` 
            (`fragrance_code`, `catalogue_number`, `fragrance_name`, `url_key`, `house_id`, `collection_id`, `segment_id`, `audience`, `family_id`, `strength_id`, `perfumer_id`, `primary_volume_ml`, `summary`, `full_story`, `origin_country`, `release_year`, `list_price`, `offer_price`, `discount_rate`, `available_units`, `availability_state`, `record_state`, `data_quality_score`, `featured_flag`) 
            VALUES 
            ('PH-MEN-SPC-0003', 'CAT-DIOR-003', 'Dior Sauvage Elixir', 'dior-sauvage-elixir', 3, 1, 1, 'Men', 9, 1, 2, 60, 
            'An extraordinarily concentrated fragrance steeped in the iconic freshness of Sauvage with an intoxicating heart of spices.', 
            'Sauvage Elixir alters the rules of men perfumery by exploring the boundaries of extreme concentration. It is a fragrance as unique and rare as a red moon in the night sky.', 
            'France', 2021, 16000.00, 14200.00, 11.25, 30, 'Ready', 'Published', 100, 1)");
        $f3_id = $pdo->lastInsertId();

        $pdo->exec("INSERT INTO `fragrance_notes` (`fragrance_id`, `note_stage`, `ingredient_name`, `display_position`) VALUES
            ($f3_id, 'opening', 'Cinnamon', 1),
            ($f3_id, 'opening', 'Nutmeg', 2),
            ($f3_id, 'opening', 'Cardamom', 3),
            ($f3_id, 'heart', 'Lavender Essence', 1),
            ($f3_id, 'dry_down', 'Licorice', 1),
            ($f3_id, 'dry_down', 'Rich Amber & Vetiver', 2)");

        $pdo->exec("INSERT INTO `fragrance_volumes` (`fragrance_id`, `volume_label`, `volume_ml`, `volume_price`, `volume_list_price`, `is_default_volume`) VALUES
            ($f3_id, '60 ml', 60, 14200.00, 16000.00, 1),
            ($f3_id, '100 ml', 100, 21000.00, 23500.00, 0)");

        $pdo->exec("INSERT INTO `fragrance_editions` (`fragrance_id`, `edition_title`, `edition_details`) VALUES
            ($f3_id, 'Retail Pack', 'Midnight blue glass bottle with silver typography'),
            ($f3_id, 'Travel Edition', '60ml bottle with magnetic leather atomizer case')");

        $pdo->exec("INSERT INTO `fragrance_media` (`fragrance_id`, `remote_image_address`, `media_origin`, `image_type`, `featured_image`) VALUES
            ($f3_id, 'https://images.unsplash.com/photo-1541643600914-78b084683601?w=600&auto=format&fit=crop&q=80', 'external_link', 'Main', 1)");

        // Sample Fragrance 4: Lattafa Khamrah
        $pdo->exec("INSERT INTO `fragrances` 
            (`fragrance_code`, `catalogue_number`, `fragrance_name`, `url_key`, `house_id`, `collection_id`, `segment_id`, `audience`, `family_id`, `strength_id`, `perfumer_id`, `primary_volume_ml`, `summary`, `full_story`, `origin_country`, `release_year`, `list_price`, `offer_price`, `discount_rate`, `available_units`, `availability_state`, `record_state`, `data_quality_score`, `featured_flag`) 
            VALUES 
            ('PH-UNI-AMB-0004', 'CAT-LAT-004', 'Lattafa Khamrah', 'lattafa-khamrah', 4, 1, 4, 'Unisex', 6, 2, NULL, 100, 
            'A luxurious unisex oriental spicy perfume blending sweet dates, cinnamon, praline and vanilla.', 
            'Lattafa Khamrah is a warm gourmand masterwork inspired by royal Arabian nights. Its sweet, cozy, resinous scent profile creates a magnetic aura that lasts for over 14 hours.', 
            'United Arab Emirates', 2022, 4500.00, 2999.00, 33.36, 40, 'Ready', 'Published', 95, 1)");
        $f4_id = $pdo->lastInsertId();

        $pdo->exec("INSERT INTO `fragrance_notes` (`fragrance_id`, `note_stage`, `ingredient_name`, `display_position`) VALUES
            ($f4_id, 'opening', 'Cinnamon', 1),
            ($f4_id, 'opening', 'Nutmeg & Bergamot', 2),
            ($f4_id, 'heart', 'Dates Accord', 1),
            ($f4_id, 'heart', 'Praline & Tuberose', 2),
            ($f4_id, 'dry_down', 'Vanilla Bourbon', 1),
            ($f4_id, 'dry_down', 'Tonka Bean & Amberwood', 2)");

        $pdo->exec("INSERT INTO `fragrance_volumes` (`fragrance_id`, `volume_label`, `volume_ml`, `volume_price`, `volume_list_price`, `is_default_volume`) VALUES
            ($f4_id, '100 ml', 100, 2999.00, 4500.00, 1)");

        $pdo->exec("INSERT INTO `fragrance_editions` (`fragrance_id`, `edition_title`, `edition_details`) VALUES
            ($f4_id, 'Retail Pack', 'Heavy crystal glass bottle with embossed gold stopper'),
            ($f4_id, 'Gift Box', '100ml Eau de Parfum + 50ml Body Spray')");

        $pdo->exec("INSERT INTO `fragrance_media` (`fragrance_id`, `remote_image_address`, `media_origin`, `image_type`, `featured_image`) VALUES
            ($f4_id, 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?w=600&auto=format&fit=crop&q=80', 'external_link', 'Main', 1)");
    }

    // Populate Sample Products if table empty
    $prod_chk = $pdo->query("SELECT COUNT(*) FROM products");
    if ($prod_chk->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO products (`name`, `brand`, `category`, `mrp`, `price`, `description`, `image_url`, `image_urls`) VALUES
            ('Creed Aventus', 'Creed', 'Men', 32000.00, 28500.00, 'Luxury royal perfume for men', 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=600', 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=600\nhttps://images.unsplash.com/photo-1523293182086-7651a899d37f?w=600'),
            ('Tom Ford Black Orchid', 'Tom Ford', 'Unisex', 18500.00, 15900.00, 'Sensual dark orchid fragrance', 'https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=600', 'https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=600\nhttps://images.unsplash.com/photo-1592945403244-b3fbafd7f539?w=600'),
            ('Dior Sauvage Elixir', 'Dior', 'Men', 16000.00, 14200.00, 'Concentrated spicy fragrance', 'https://images.unsplash.com/photo-1541643600914-78b084683601?w=600', 'https://images.unsplash.com/photo-1541643600914-78b084683601?w=600'),
            ('Lattafa Khamrah', 'Lattafa', 'Unisex', 4500.00, 2999.00, 'Warm oriental spicy gourmet scent', 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?w=600', 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?w=600')");
    }

    echo "✅ Database initialized successfully! All tables and columns ready.<br><a href='index.php'>Go to Perfume Hub Storefront</a>";
} catch (PDOException $e) {
    die("❌ Setup Failed: " . $e->getMessage());
}
?>
