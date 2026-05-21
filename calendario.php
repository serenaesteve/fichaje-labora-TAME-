<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/layout.php';
require_login();
$db=fich_pdo();
$sem=semana_actual();
$weekStart=$sem['start'];$weekEnd=$sem['end'];$weekStartTs=$sem['ts'];
$empleados=get_empleados($db);
$selectedEmp=(int)($_GET['emp']??0);
$employeeName='';
foreach($empleados as $e) if((int)$e['id']===$selectedEmp) $employeeName=$e['nombre'].' '.$e['apellido'];
$festivos=get_festivos($db,$weekStart,$weekEnd);
$teorico=$selectedEmp?get_horario_teorico($db,$selectedEmp,$weekStart,$weekEnd,$festivos,$weekStartTs):['slots'=>[],'due'=>0];
$asistencia=$selectedEmp?get_asistencia_semana($db,$selectedEmp,$weekStart,$weekEnd):['events'=>[],'worked'=>0];
$percent=$teorico['due']>0?round($asistencia['worked']/$teorico['due']*100,1):0;
$days=['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
layout_open('Calendario','calendario');
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
  <h2 style="margin:0;font-size:1.1rem;">Calendario semanal</h2>
  <div class="week-nav">
    <a href="?emp=<?=$selectedEmp?>&week=<?=$sem['prev']?>" class="btn btn-secondary btn-sm">← Anterior</a>
    <span class="week-label"><?=$sem['label']?></span>
    <a href="?emp=<?=$selectedEmp?>&week=<?=$sem['next']?>" class="btn btn-secondary btn-sm">Siguiente →</a>
  </div>
</div>
<form method="get" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:16px;flex-wrap:wrap;">
  <input type="hidden" name="week" value="<?=htmlspecialchars($sem['start'])?>">
  <div class="form-group" style="flex:1;min-width:220px;">
    <label>Empleado</label>
    <select name="emp">
      <option value="0">-- Selecciona --</option>
      <?php foreach($empleados as $e): ?>
      <option value="<?=(int)$e['id']?>" <?=(int)$e['id']===$selectedEmp?'selected':''?>>
        <?=htmlspecialchars($e['nombre'].' '.$e['apellido'])?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn-primary" type="submit">Ver</button>
</form>
<?php if($selectedEmp): ?>
<div class="summary-bar">
  <div class="summary-stat"><span class="val"><?=fmtH($teorico['due'])?></span><span class="lbl">Requeridas</span></div>
  <div class="summary-stat"><span class="val"><?=fmtH($asistencia['worked'])?></span><span class="lbl">Trabajadas</span></div>
  <div class="summary-stat"><span class="val" style="color:<?=$percent>=100?'var(--ok)':($percent>=75?'var(--warn)':'var(--err)')?>"><?=$percent?>%</span><span class="lbl">Avance</span></div>
  <div class="progress-wrap"><div class="progress-fill" style="width:<?=min(100,$percent)?>%"></div></div>
</div>
<div style="overflow-x:auto;">
<div class="calendar" style="grid-template-rows:40px repeat(48,22px);">
  <div class="cal-header" style="grid-column:1;grid-row:1;"></div>
  <?php for($d=0;$d<7;$d++): $dayDate=date('Y-m-d',strtotime("+{$d} days",$weekStartTs)); ?>
  <div class="cal-header" style="grid-column:<?=$d+2?>;grid-row:1;"><?=$days[$d]?><br><span style="font-size:.6rem;color:var(--muted)"><?=date('d/m',strtotime($dayDate))?></span></div>
  <?php endfor; ?>
  <?php for($i=0;$i<48;$i++): $h=floor($i/2);$m=$i%2===0?'00':'30';$row=$i+2; ?>
  <div class="cal-time" style="grid-column:1;grid-row:<?=$row?>;"><?=$m==='00'?sprintf('%02d',$h).':00':''?></div>
  <?php for($d=0;$d<7;$d++): ?><div class="cal-slot" style="grid-column:<?=$d+2?>;grid-row:<?=$row?>;"></div><?php endfor; ?>
  <?php endfor; ?>
  <?php foreach($festivos as $fDate=>$fInfo):
    $di=(int)((strtotime($fDate)-$weekStartTs)/86400);
    if($di<0||$di>6) continue;
  ?><div class="cal-holiday" style="grid-column:<?=$di+2?>;grid-row:2/span 48;"><?=htmlspecialchars($fInfo['nombre'])?></div><?php endforeach; ?>
  <?php foreach($teorico['slots'] as $tev): $c=$tev['day']+2;$sr=floor($tev['start']/30)+2;$sp=max(1,floor($tev['duration']/30)); ?>
  <div class="cal-event-teorico" style="grid-column:<?=$c?>;grid-row:<?=$sr?>/span <?=$sp?>;"></div>
  <?php endforeach; ?>
  <?php foreach($asistencia['events'] as $ev): $c=$ev['day']+2;$sr=floor($ev['start']/30)+2;$sp=max(1,floor($ev['duration']/30)); ?>
  <div class="cal-event-real" style="grid-column:<?=$c?>;grid-row:<?=$sr?>/span <?=$sp?>;"><?=$ev['entrada'].' – '.$ev['salida']?></div>
  <?php endforeach; ?>
</div></div>
<?php else: ?><p style="color:var(--muted)">Selecciona un empleado para ver su calendario.</p><?php endif; ?>
<?php layout_close(); ?>
