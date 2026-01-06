<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/Config/AppConfig.php';


use Jaguata\Config\AppConfig;

AppConfig::init();
header('Content-Type: application/json; charset=utf-8');

/*
  Este endpoint normalmente hace reverse geocoding (lat/lng → dirección)
  Acá te dejo una estructura base segura.
  Si vos ya tenías lógica con cURL, pegala dentro del try.
*/

$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 0.0;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : 0.0;

if ($lat === 0.0 || $lng === 0.0) {
    echo json_encode(['error' => 'Parámetros lat/lng inválidos']);
    exit;
}

try {
    // ✅ Nominatim Reverse (sin API key)
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: Jaguata/1.0 (localhost)'
        ],
    ]);

    $raw = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        echo json_encode(['error' => 'cURL error: ' . $err]);
        exit;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        echo json_encode(['error' => 'Respuesta inválida del servicio']);
        exit;
    }

    // devolvemos lo mismo que tu JS espera: address + display_name
    echo json_encode([
        'address' => $data['address'] ?? [],
        'display_name' => $data['display_name'] ?? '',
    ]);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
