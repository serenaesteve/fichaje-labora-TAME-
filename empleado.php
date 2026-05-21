<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['empleado_loggedin'])) {
    header('Location: empleado_login.php'); exit;
}

$emp   = $_SESSION['empleado'];
$empId = (int)$emp['id'];
$db    = fich_pdo();
$today = date('Y-m-d');

// AJAX: fichaje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action  = $_POST['action'];
    $nowTime = date('H:i:s');

    if ($action === 'entrada') {
        $st = $db->prepare("SELECT id,hora_entrada,hora_salida FROM Asistencia WHERE empleado_id=? AND fecha=? LIMIT 1");
        $st->execute([$empId,$today]);
        $reg = $st->fetch();
        if ($reg && $reg['hora_entrada']) {
            echo json_encode(['ok'=>false,'msg'=>'Ya tienes entrada registrada a las '.substr($reg['hora_entrada'],0,5)]);
        } else {
            $db->prepare("INSERT INTO Asistencia(empleado_id,fecha,hora_entrada) VALUES(?,?,?) ON DUPLICATE KEY UPDATE hora_entrada=?")->execute([$empId,$today,$nowTime,$nowTime]);
            echo json_encode(['ok'=>true,'msg'=>'✓ Entrada registrada a las '.substr($nowTime,0,5)]);
        }
        exit;
    }
    if ($action === 'salida') {
        $st = $db->prepare("SELECT id,hora_entrada,hora_salida FROM Asistencia WHERE empleado_id=? AND fecha=? LIMIT 1");
        $st->execute([$empId,$today]);
        $reg = $st->fetch();
        if (!$reg || !$reg['hora_entrada']) {
            echo json_encode(['ok'=>false,'msg'=>'Primero debes registrar la entrada']);
        } elseif ($reg['hora_salida']) {
            echo json_encode(['ok'=>false,'msg'=>'Ya tienes salida registrada a las '.substr($reg['hora_salida'],0,5)]);
        } else {
            $db->prepare("UPDATE Asistencia SET hora_salida=? WHERE id=?")->execute([$nowTime,$reg['id']]);
            [$h1,$m1]=explode(':',$reg['hora_entrada']);
            [$h2,$m2]=explode(':',substr($nowTime,0,5));
            $mins=((int)$h2*60+(int)$m2)-((int)$h1*60+(int)$m1);
            echo json_encode(['ok'=>true,'msg'=>'✓ Salida registrada — '.fmtH(max(0,$mins)).' trabajadas']);
        }
        exit;
    }
    if ($action === 'logout') {
        $_SESSION['empleado_loggedin'] = false;
        unset($_SESSION['empleado']);
        echo json_encode(['ok'=>true]); exit;
    }
    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']); exit;
}

// Cargar fichaje de hoy
$stHoy = $db->prepare("SELECT hora_entrada, hora_salida FROM Asistencia WHERE empleado_id=? AND fecha=? LIMIT 1");
$stHoy->execute([$empId, $today]);
$hoy = $stHoy->fetch();

// Historial últimas 2 semanas
$desde = date('Y-m-d', strtotime('-14 days'));
$stHist = $db->prepare("SELECT fecha, hora_entrada, hora_salida FROM Asistencia WHERE empleado_id=? AND fecha BETWEEN ? AND ? ORDER BY fecha DESC");
$stHist->execute([$empId, $desde, $today]);
$historial = $stHist->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>TAME Fichajes — <?= htmlspecialchars($emp['nombre']) ?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="topbar">
  <div class="brand">TAME Formación · Fichajes</div>
  <div class="spacer"></div>
  <span class="user-info"><?= htmlspecialchars($emp['nombre']) ?></span>
  <button class="btn btn-secondary btn-sm" onclick="cerrarSesion()">Salir</button>
</header>

<div style="max-width:600px;margin:30px auto;padding:0 16px;">

  <!-- Reloj y estado hoy -->
  <div class="card" style="text-align:center;margin-bottom:20px;">
    <div style="font-size:2.5rem;font-weight:800;color:var(--accent);letter-spacing:.05em;" id="reloj">--:--:--</div>
    <div style="color:var(--muted);font-size:.85rem;margin-bottom:16px;"><?= date('l, d \d\e F \d\e Y') ?></div>

    <!-- Estado hoy -->
    <div style="margin-bottom:18px;" id="estadoHoy">
      <?php if ($hoy && $hoy['hora_entrada'] && $hoy['hora_salida']): ?>
        <span class="cap cap-ok">Jornada completa · <?= substr($hoy['hora_entrada'],0,5) ?> – <?= substr($hoy['hora_salida'],0,5) ?></span>
      <?php elseif ($hoy && $hoy['hora_entrada']): ?>
        <span class="cap cap-warn">Entrada <?= substr($hoy['hora_entrada'],0,5) ?> · pendiente salida</span>
      <?php else: ?>
        <span class="cap cap-none">Sin fichaje hoy</span>
      <?php endif; ?>
    </div>

    <!-- Botones -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <button class="btn" onclick="fichar('entrada')"
        style="justify-content:center;padding:16px;font-size:1rem;background:rgba(52,211,153,.15);color:#34d399;border:1px solid rgba(52,211,153,.3);">
        ↓ Entrada
      </button>
      <button class="btn" onclick="fichar('salida')"
        style="justify-content:center;padding:16px;font-size:1rem;background:rgba(248,113,113,.15);color:#f87171;border:1px solid rgba(248,113,113,.3);">
        ↑ Salida
      </button>
    </div>

    <div id="statusArea"></div>
  </div>

  <!-- Historial -->
  <div class="card">
    <div class="card-title">Mis últimos fichajes</div>
    <div class="table-wrap">
    <table>
      <thead><tr><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($historial as $r):
          $entrada = $r['hora_entrada'] ? substr($r['hora_entrada'],0,5) : '—';
          $salida  = $r['hora_salida']  ? substr($r['hora_salida'],0,5)  : '—';
          $total   = '';
          if ($r['hora_entrada'] && $r['hora_salida']) {
            [$h1,$m1]=explode(':',$r['hora_entrada']);
            [$h2,$m2]=explode(':',$r['hora_salida']);
            $mins=(int)$h2*60+(int)$m2-(int)$h1*60-(int)$m1;
            $total = fmtH(max(0,$mins));
          }
        ?>
        <tr>
          <td><?= $r['fecha'] ?></td>
          <td><?= $entrada ?></td>
          <td><?= $salida ?></td>
          <td style="color:var(--accent);font-weight:700;"><?= $total ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$historial): ?>
        <tr><td colspan="4" style="color:var(--muted);text-align:center;">Sin registros</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>

</div>

<script>
function tick(){
  const n=new Date();
  document.getElementById('reloj').textContent=
    String(n.getHours()).padStart(2,'0')+':'+
    String(n.getMinutes()).padStart(2,'0')+':'+
    String(n.getSeconds()).padStart(2,'0');
}
tick(); setInterval(tick,1000);

async function fichar(tipo){
  const fd=new FormData(); fd.append('action',tipo);
  const d=await(await fetch('empleado.php',{method:'POST',body:fd})).json();
  const el=document.getElementById('statusArea');
  el.innerHTML=`<div class="terminal-status ${d.ok?'ok':'error'}" style="margin-top:14px;">${d.msg}</div>`;
  if(d.ok){ setTimeout(()=>location.reload(),2000); }
}

async function cerrarSesion(){
  await fetch('empleado.php',{method:'POST',body:new FormData()});
  const fd=new FormData(); fd.append('action','logout');
  await fetch('empleado.php',{method:'POST',body:fd});
  location.href='empleado_login.php';
}
</script>
</body>
</html>
