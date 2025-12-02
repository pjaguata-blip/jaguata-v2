<?php
require_once __DIR__ . '/src/Config/AppConfig.php';
require_once __DIR__ . '/src/Services/DatabaseService.php';

use Jaguata\Config\AppConfig;
use Jaguata\Services\DatabaseService;

AppConfig::init();

echo "<pre>";

echo "Probando conexión...\n";

$db = DatabaseService::getInstance()->getConnection();

$stmt = $db->query("SELECT COUNT(*) AS total FROM usuarios");
$row  = $stmt->fetch();

echo "Total de usuarios en la tabla 'usuarios': " . ($row['total'] ?? 0) . "\n";

$pass = '123456';
echo "Password de prueba: {$pass}\n";
echo "Hash BCRYPT: " . password_hash($pass, PASSWORD_BCRYPT) . "\n";

echo "</pre>";
