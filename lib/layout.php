<?php
declare(strict_types=1);

function layout_open(string $title, string $activeTab = ''): void {
    $u = current_user();
    $base = base_url();
    ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>TAME Fichajes – <?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="<?= $base ?>assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="brand">
    <span style="color:var(--muted);font-weight:600;">TAME Formación</span>
    <span style="color:var(--muted);margin:0 6px">/</span>
    <a href="<?= $base ?>index.php" style="color:var(--accent);text-decoration:none;font-weight:800;">Fichajes</a>
  </div>
  <div class="spacer"></div>
  <span class="user-info"><?= htmlspecialchars($u['nombre'] ?? '') ?></span>
  <a href="<?= $base ?>logout.php" class="btn btn-secondary btn-sm">Salir</a>
</header>
<div class="layout">
  <aside class="sidebar">
    <div class="section-label">Control horario</div>
    <?php
    $links = [
        'terminal'   => ['Terminal de fichaje', 'terminal.php'],
        'resumen'    => ['Resumen semanal',      'resumen.php'],
        'calendario' => ['Calendario',           'calendario.php'],
        'horarios'   => ['Editor de horarios',   'horarios.php'],
        'alertas'    => ['Alertas admin',        'alertas.php'],
        'festivos'   => ['Festivos',             'festivos.php'],
        'empleados'  => ['Empleados',            'empleados.php'],
    ];
    foreach ($links as $key => [$label, $href]):
        $active = ($activeTab === $key) ? 'active' : '';
    ?>
    <a class="nav-link <?= $active ?>" href="<?= $base . $href ?>">
      <span class="dot"></span> <?= htmlspecialchars($label) ?>
    </a>
    <?php endforeach; ?>
  </aside>
  <main class="content">
<?php
}

function layout_close(): void {
    echo '</main></div></body></html>';
}
