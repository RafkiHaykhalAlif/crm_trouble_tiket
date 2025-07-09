<?php

header('Content-Type: application/json');

$type = isset($_GET['type']) ? $_GET['type'] : '';
$prov = isset($_GET['prov']) ? intval($_GET['prov']) : 0;

if ($type === 'provinces') {
    $url = 'https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json';
} elseif ($type === 'regencies' && $prov > 0) {
    $url = 'https://emsifa.github.io/api-wilayah-indonesia/api/regencies/' . $prov . '.json';
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

echo file_get_contents($url);