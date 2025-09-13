<?php
require_once '../config/config.php';
require_once '../includes/advertisement.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ad_id = $input['ad_id'] ?? null;

if (!$ad_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Ad ID required']);
    exit;
}

$adManager = new AdvertisementManager();
$adManager->incrementClickCount($ad_id);

echo json_encode(['success' => true]);
?>

