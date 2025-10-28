<?php
require_once __DIR__ . '/src/Config/AppConfig.php';

use Jaguata\Config\AppConfig;

AppConfig::init();

$pdo = AppConfig::db();

// Verificar si la tabla usuarios existe
echo "<h3>1️⃣ Verificando tabla usuarios...</h3>";
$stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
if ($stmt->rowCount() === 0) {
    die("<p style='color:red'>❌ No existe la tabla usuarios</p>");
}

// Verificar usuario admin
echo "<h3>2️⃣ Buscando usuario admin...</h3>";
$user = $pdo->query("SELECT usu_id, nombre, email, rol, estado, pass FROM usuarios WHERE email='admin@jaguata.com' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<p style='color:red'>❌ No existe usuario admin@jaguata.com</p>";
} else {
    echo "<pre>";
    print_r($user);
    echo "</pre>";

    echo "<h3>3️⃣ Verificando hash de contraseña...</h3>";
    if (password_verify('admin123', $user['pass'])) {
        echo "<p style='color:green'>✅ Hash válido, password_verify('admin123') = TRUE</p>";
    } else {
        echo "<p style='color:red'>❌ Hash inválido, password_verify('admin123') = FALSE</p>";
    }
}
