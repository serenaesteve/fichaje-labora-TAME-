<?php
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';

// Si ya tiene sesión de empleado, redirigir
if (!empty($_SESSION['empleado_loggedin'])) {
    header('Location: empleado.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empId = (int)($_POST['emp_id'] ?? 0);
    $pin   = trim($_POST['pin'] ?? '');
    $db    = fich_pdo();
    $st    = $db->prepare("SELECT * FROM empleados WHERE id=? AND activo=1 LIMIT 1");
    $st->execute([$empId]);
    $emp   = $st->fetch();
    if ($emp && $emp['pin'] === $pin) {
        $_SESSION['empleado_loggedin'] = true;
        $_SESSION['empleado'] = [
            'id'      => $emp['id'],
            'nombre'  => $emp['nombre'] . ' ' . $emp['apellido'],
        ];
        header('Location: empleado.php'); exit;
    }
    $error = 'PIN incorrecto o empleado no válido.';
}

$db = fich_pdo();
$empleados = $db->query("SELECT id, nombre, apellido FROM empleados WHERE activo=1 ORDER BY nombre, apellido")->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>TAME Fichajes — Acceso empleado</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="terminal-wrap">
  <div class="terminal-card">
    <h2>TAME Formación</h2>
    <p class="sub">Portal del empleado</p>

    <?php if ($error): ?>
    <div class="terminal-status error" style="margin-bottom:14px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group" style="margin-bottom:12px;text-align:left;">
        <label>Tu nombre</label>
        <select name="emp_id" required style="font-size:1rem;padding:12px;">
          <option value="">-- Selecciona --</option>
          <?php foreach ($empleados as $e): ?>
          <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['nombre'].' '.$e['apellido']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group" style="margin-bottom:6px;text-align:left;">
        <label>PIN</label>
      </div>

      <!-- Display PIN -->
      <div class="pin-display" id="pinDisplay">____</div>

      <!-- Teclado numérico -->
      <div class="numpad">
        <?php foreach([1,2,3,4,5,6,7,8,9] as $n): ?>
        <button type="button" onclick="addPin('<?= $n ?>')"><?= $n ?></button>
        <?php endforeach; ?>
        <button type="button" class="btn-del" onclick="delPin()">⌫</button>
        <button type="button" onclick="addPin('0')">0</button>
        <button type="button" class="btn-ok" onclick="submitPin()">✓</button>
      </div>

      <input type="hidden" name="pin" id="pinInput">
      <button type="submit" id="submitBtn" style="display:none;"></button>
    </form>

    <p style="text-align:center;margin-top:16px;font-size:.78rem;">
      <a href="login.php" style="color:var(--muted);">Acceso administración →</a>
    </p>
  </div>
</div>

<script>
let pin = '';
function updateDisplay(){
  const d = document.getElementById('pinDisplay');
  d.textContent = pin.padEnd(4,'_').split('').join(' ');
}
function addPin(n){
  if(pin.length >= 4) return;
  pin += n;
  updateDisplay();
}
function delPin(){
  pin = pin.slice(0,-1);
  updateDisplay();
}
function submitPin(){
  if(pin.length !== 4){ return; }
  document.getElementById('pinInput').value = pin;
  document.getElementById('submitBtn').click();
}
updateDisplay();
</script>
</body>
</html>
