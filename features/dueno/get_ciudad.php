<?php
// features/api/get_ciudad.php

header('Content-Type: application/json; charset=utf-8');

// Validación básica
if (!isset($_GET['lat']) || !isset($_GET['lon'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan parámetros lat/lon']);
    exit;
}

$lat = $_GET['lat'];
$lon = $_GET['lon'];

// Validar que sean numéricos y rangos razonables
if (!is_numeric($lat) || !is_numeric($lon)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}
if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros fuera de rango']);
    exit;
}

$url = "https://nominatim.openstreetmap.org/reverse?lat={$lat}&lon={$lon}&format=json";

// Nominatim requiere un User-Agent identificable
$userAgent = 'JaguataApp/1.0 (+https://tu-dominio-o-email)';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => [
        "User-Agent: {$userAgent}",
        "Accept: application/json"
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode >= 400) {
    http_response_code(502);
    echo json_encode([
        'error' => 'No se pudo obtener la dirección',
        'status' => $httpCode,
        'detail' => $error
    ]);
    exit;
}

// Entregar tal cual la respuesta de Nominatim
echo $response;
