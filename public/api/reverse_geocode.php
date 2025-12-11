<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Config/AppConfig.php';

use Jaguata\Config\AppConfig;

AppConfig::init();

header('Content-Type: application/json; charset=utf-8');

// Coordenadas desde GET
$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;

if ($lat === null || $lng === null) {
    echo json_encode(['error' => 'Coordenadas inválidas']);
    exit;
}

// URL de Nominatim (OpenStreetMap)
$url = "https://nominatim.openstreetmap.org/reverse?lat={$lat}&lon={$lng}&format=json&addressdetails=1";

// Nominatim exige un User-Agent identificable
$opts = [
    'http' => [
        'header' => "User-Agent: Jaguata/1.0 (admin@jaguata.com)\r\n"
    ]
];

$context  = stream_context_create($opts);
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo json_encode(['error' => 'No se pudo contactar con Nominatim']);
    exit;
}

// Devolvemos la respuesta tal cual para que el frontend la use
echo $response;
