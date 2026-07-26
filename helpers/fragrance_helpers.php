<?php
// helpers/fragrance_helpers.php - E-Commerce Order Reference Helper
function generate_purchase_reference($pdo) {
    do {
        $ref = 'PH-ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM purchase_orders WHERE purchase_reference = :ref");
        $stmt->execute(['ref' => $ref]);
        $exists = $stmt->fetchColumn() > 0;
    } while ($exists);
    return $ref;
}
?>
