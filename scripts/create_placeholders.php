<?php
// Creates 1x1 PNG placeholders for a list of product image filenames

$destDir = __DIR__ . '/../uploads/products';
if (!is_dir($destDir)) {
    mkdir($destDir, 0777, true);
}

$filenames = [
    'samsung_s23.jpg','samsung_a54.jpg','macbook_air_m2.jpg','macbook_pro14.jpg','apple_watch9.jpg',
    'airpods_pro2.jpg','canon_m50.jpg','gopro_hero12.jpg','hp_envy.jpg','logitech_mx3s.jpg',
    'oneplus11.jpg','pixel8.jpg','asus_zephyrus.jpg','thinkpad_x1.jpg','sony_xm5.jpg',
    'bose_ultra.jpg','dell_xps13.jpg','acer_predator.jpg','seagate_2tb.jpg','samsung_ssd980.jpg'
];

// 1x1 transparent PNG (base64)
$pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAl0B8k7E8VQAAAAASUVORK5CYII=';
$pngBytes = base64_decode($pngBase64);

$created = 0;
foreach ($filenames as $name) {
    $target = $destDir . '/' . $name;
    if (!file_exists($target)) {
        file_put_contents($target, $pngBytes);
        $created++;
    }
}

echo "Ensured " . count($filenames) . " files. Created: $created\n";
?>


