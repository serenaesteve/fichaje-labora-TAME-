<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';

if (current_user()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['usuario'] ?? '');
    $p = trim($_POST['password'] ?? '');
    $db = fich_pdo();
    $st = $db->prepare("SELECT * FROM admin_users WHERE username=? LIMIT 1");
    $st->execute([$u]);
    $row = $st->fetch();
    if ($row && password_verify($p, $row['password_hash'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['user'] = ['id'=>$row['id'],'nombre'=>$row['nombre'],'usuario'=>$row['username']];
        header('Location: index.php'); exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>TAME Fichajes — Acceso</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="terminal-wrap">
  <div class="terminal-card" style="text-align:left;">
    <h2 style="text-align:center;margin-bottom:4px;">TAME Formación</h2>
    <p class="sub" style="text-align:center;">Control de fichajes · Panel admin</p>

    <?php if ($error): ?>
    <div class="terminal-status error" style="margin-bottom:14px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group" style="margin-bottom:12px;">
        <label>Usuario</label>
        <input type="text" name="usuario" required autofocus placeholder="admin">
      </div>
      <div class="form-group" style="margin-bottom:18px;">
        <label>Contraseña</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
      <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center;padding:12px;">
        Iniciar sesión
      </button>
    </form>
    <p style="text-align:center;margin-top:14px;font-size:.78rem;color:var(--muted);">
      Por defecto: <code>admin</code> / <code>admin123</code>
    </p>
  </div>
</div>
</body>
</html>
