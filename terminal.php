<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/layout.php';
require_login();
$db = fich_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action  = $_POST['action'] ?? '';
    $empId   = (int)($_POST['emp_id'] ?? 0);
    $today   = date('Y-m-d');
    $nowTime = date('H:i:s');

    if ($action === 'entrada') {
        if ($empId <= 0) { echo json_encode(['ok'=>false,'msg'=>'Selecciona un empleado']); exit; }
        $st = $db->prepare("SELECT id,hora_entrada,hora_salida FROM Asistencia WHERE empleado_id=? AND fecha=? LIMIT 1");
        $st->execute([$empId,$today]);
        $reg = $st->fetch();
        if ($reg && $reg['hora_entrada']) {
            echo json_encode(['ok'=>false,'msg'=>'Ya tienes entrada registrada a las '.substr($reg['hora_entrada'],0,5)]);
        } else {
            $db->prepare("INSERT INTO Asistencia(empleado_id,fecha,hora_entrada) VALUES(?,?,?) ON DUPLICATE KEY UPDATE hora_entrada=?")->execute([$empId,$today,$nowTime,$nowTime]);
            echo json_encode(['ok'=>true,'tipo'=>'entrada','hora'=>substr($nowTime,0,5),'msg'=>'✓ Entrada registrada a las '.substr($nowTime,0,5)]);
        }
        exit;
    }

    if ($action === 'salida') {
        if ($empId <= 0) { echo json_encode(['ok'=>false,'msg'=>'Selecciona un empleado']); exit; }
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
            echo json_encode(['ok'=>true,'tipo'=>'salida','hora'=>substr($nowTime,0,5),'msg'=>'✓ Salida registrada — '.fmtH(max(0,$mins)).' trabajadas']);
        }
        exit;
    }

    if ($action === 'estado') {
        if ($empId<=0) { echo json_encode(['ok'=>false]); exit; }
        $st=$db->prepare("SELECT hora_entrada,hora_salida FROM Asistencia WHERE empleado_id=? AND fecha=? LIMIT 1");
        $st->execute([$empId,date('Y-m-d')]);
        echo json_encode(['ok'=>true,'reg'=>$st->fetch()]);
        exit;
    }

    echo json_encode(['ok'=>false,'msg'=>'Acción no reconocida']); exit;
}

$empleados = get_empleados($db);
layout_open('Terminal', 'terminal');
?>
<div class="terminal-wrap">
  <div class="terminal-card">
    <h2>TAME Formación</h2>
    <p class="sub">Control de fichajes · <span id="reloj">--:--:--</span></p>

    <div class="form-group" style="margin-bottom:14px;text-align:left;">
      <label>Selecciona tu nombre</label>
      <select id="empSelect" style="font-size:1rem;padding:12px;">
        <option value="">-- Selecciona --</option>
        <?php foreach ($empleados as $e): ?>
        <option value="<?= (int)$e['id'] ?>">
          <?= htmlspecialchars($e['nombre'].' '.$e['apellido']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div id="estadoEmp" style="margin-bottom:16px;min-height:28px;"></div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:6px;">
      <button class="btn" id="btnEntrada" onclick="fichar('entrada')"
        style="justify-content:center;padding:16px;font-size:1rem;background:rgba(52,211,153,.15);color:#34d399;border:1px solid rgba(52,211,153,.3);">
        ↓ Entrada
      </button>
      <button class="btn" id="btnSalida" onclick="fichar('salida')"
        style="justify-content:center;padding:16px;font-size:1rem;background:rgba(248,113,113,.15);color:#f87171;border:1px solid rgba(248,113,113,.3);">
        ↑ Salida
      </button>
    </div>

    <div id="statusArea"></div>
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

document.getElementById('empSelect').addEventListener('change', async function(){
  const id=this.value, el=document.getElementById('estadoEmp');
  if(!id){el.innerHTML='';return;}
  const fd=new FormData(); fd.append('action','estado'); fd.append('emp_id',id);
  const d=await(await fetch('terminal.php',{method:'POST',body:fd})).json();
  if(d.ok&&d.reg){
    if(d.reg.hora_salida)
      el.innerHTML=`<span class="cap cap-ok">Jornada completa · ${d.reg.hora_entrada.slice(0,5)} – ${d.reg.hora_salida.slice(0,5)}</span>`;
    else if(d.reg.hora_entrada)
      el.innerHTML=`<span class="cap cap-warn">Entrada ${d.reg.hora_entrada.slice(0,5)} · pendiente salida</span>`;
    else
      el.innerHTML=`<span class="cap cap-none">Sin fichaje hoy</span>`;
  } else {
    el.innerHTML=`<span class="cap cap-none">Sin fichaje hoy</span>`;
  }
});

async function fichar(tipo){
  const id=document.getElementById('empSelect').value;
  if(!id){showStatus('Selecciona un empleado primero','warning');return;}
  const fd=new FormData(); fd.append('action',tipo); fd.append('emp_id',id);
  const d=await(await fetch('terminal.php',{method:'POST',body:fd})).json();
  showStatus(d.msg, d.ok?'ok':'error');
  if(d.ok) document.getElementById('empSelect').dispatchEvent(new Event('change'));
}

function showStatus(msg,type){
  const el=document.getElementById('statusArea');
  el.innerHTML=`<div class="terminal-status ${type}" style="margin-top:14px;">${msg}</div>`;
  if(type==='ok') setTimeout(()=>{el.innerHTML=''},4000);
}
</script>
<?php layout_close(); ?>
