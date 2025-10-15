<?php

declare(strict_types=1);
require_once __DIR__ . '/../../src/Config/AppConfig.php';
require_once __DIR__ . '/../../src/Controllers/AuthController.php';

use Jaguata\Config\AppConfig;
use Jaguata\Controllers\AuthController;

AppConfig::init();
$auth = new AuthController();
$auth->checkRole('dueno');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: BuscarPaseadores.php');
    exit;
}

$pdo = AppConfig::db();
$st = $pdo->prepare("SELECT * FROM paseadores WHERE paseador_id = :id");
$st->bindValue(':id', $id, PDO::PARAM_INT);
$st->execute();
$u = $st->fetch(PDO::FETCH_ASSOC);

include __DIR__ . '/../../src/Templates/Header.php';
include __DIR__ . '/../../src/Templates/Navbar.php';
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<div class="container my-4">
    <?php if (!$u): ?>
        <div class="alert alert-warning">Paseador no encontrado.</div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <?php if (!empty($u['foto_url'])): ?>
                        <img src="<?= h($u['foto_url']) ?>" class="rounded-circle me-3" style="width:88px;height:88px;object-fit:cover;">
                    <?php endif; ?>
                    <div>
                        <h3 class="mb-0"><?= h($u['nombre']) ?></h3>
                        <div class="text-muted"><?= h($u['zona'] ?? 'Sin zona') ?></div>
                    </div>
                </div>
                <div class="mb-3">
                    <span class="badge bg-success me-2">⭐ <?= number_format((float)$u['calificacion'], 1) ?></span>
                    <span class="badge bg-secondary me-2">Gs <?= number_format((float)$u['precio_hora'], 0, ',', '.') ?>/h</span>
                    <span class="badge bg-light text-dark">Paseos: <?= (int)$u['total_paseos'] ?></span>
                </div>
                <?php if (!empty($u['descripcion'])): ?>
                    <p class="text-muted"><?= nl2br(h($u['descripcion'])) ?></p>
                <?php endif; ?>
                <a class="btn btn-primary" href="SolicitarPaseo.php?paseador_id=<?= $id ?>">Solicitar paseo</a>
                <a class="btn btn-outline-secondary" href="BuscarPaseadores.php">Volver</a>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../../src/Templates/Footer.php'; ?>