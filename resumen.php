<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/layout.php';
require_login();
$db = fich_pdo();

$sem=$sem=semana_actual();
$weekStart=$sem['start']; $weekEnd=$sem['end']; $weekStartTs=$sem['ts'];
$empleados=get_empleados($db);
$festivos=get_festivos($db,$weekStart,$weekEnd);
$dayNames=['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
$weekDates=[];
for($i=0;$i<7;$i++) $weekDates[]=date('Y-m-d',strtotime("+{$i} days",$weekStartTs));

$allIds=array_column($empleados,'id');
$attendance=[];
if($allIds){
    $in=implode(',',array_map('intval',$allIds));
    $st=$db->query("SELECT id,empleado_id,fecha,hora_entrada,hora_salida FROM Asistencia WHERE fecha BETWEEN '$weekStart' AND '$weekEnd' AND empleado_id IN ($in) ORDER BY id DESC");
    foreach($st->fetchAll() as $r) $attendance[(int)$r['empleado_id']][$r['fecha']]??=$r;
}
$scheduleByEmpDate=[];
if($allIds){
    $in=implode(',',array_map('intval',$allIds));
    $st=$db->query("SELECT empleado,dia_de_la_semana,hora_de_entrada,hora_de_salida,fecha_inicio,fecha_final FROM horarios WHERE empleado IN ($in) AND (fecha_inicio IS NULL OR fecha_inicio<='$weekEnd') AND (fecha_final IS NULL OR fecha_final>='$weekStart')");
    foreach($st->fetchAll() as $h){
        $dow=(int)$h['dia_de_la_semana']-1;
        $dayDate=date('Y-m-d',strtotime("+{$dow} days",$weekStartTs));
        if($dow<0||$dow>6) continue;
        if(!empty($h['fecha_inicio'])&&$h['fecha_inicio']>$dayDate) continue;
        if(!empty($h['fecha_final'])&&$h['fecha_final']<$dayDate) continue;
        if(empty($h['hora_de_entrada'])||empty($h['hora_de_salida'])) continue;
        $scheduleByEmpDate[(int)$h['empleado']][$dayDate]=true;
    }
}
$today=date('Y-m-d');
layout_open('Resumen semanal','resumen');
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
  <h2 style="margin:0;font-size:1.1rem;">Resumen semanal</h2>
  <div class="week-nav">
    <a href="?week=<?=$sem['prev']?>" class="btn btn-secondary btn-sm">← Anterior</a>
    <span class="week-label"><?=$sem['label']?></span>
    <a href="?week=<?=$sem['next']?>" class="btn btn-secondary btn-sm">Siguiente →</a>
  </div>
</div>
<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
  <span class="cap cap-ok">Asistió</span>
  <span class="cap cap-warn">Jornada abierta</span>
  <span class="cap cap-err">Error registro</span>
  <span class="cap cap-absent">Ausencia</span>
  <span class="cap cap-holiday">Festivo</span>
  <span class="cap cap-none">Sin horario</span>
</div>
<div class="table-wrap" style="overflow-x:auto;">
<table class="resumen-table">
  <thead><tr>
    <th>Empleado</th>
    <?php foreach($weekDates as $i=>$date): ?>
    <th><?=$dayNames[$i]?><br><span style="font-weight:400;color:var(--muted);font-size:.7rem;"><?=$date?></span></th>
    <?php endforeach; ?>
  </tr></thead>
  <tbody>
    <?php foreach($empleados as $emp):
      $empId=(int)$emp['id'];
    ?>
    <tr>
      <td><?=htmlspecialchars($emp['nombre'].' '.$emp['apellido'])?></td>
      <?php foreach($weekDates as $date):
        $isFestivo=isset($festivos[$date]);
        $att=$attendance[$empId][$date]??null;
        $hasSchedule=!empty($scheduleByEmpDate[$empId][$date]);
        if($isFestivo){$cls='cap-holiday';$lbl=htmlspecialchars($festivos[$date]['nombre']);}
        elseif($att&&$att['hora_entrada']&&$att['hora_salida']){$cls='cap-ok';$lbl=substr($att['hora_entrada'],0,5).' – '.substr($att['hora_salida'],0,5);}
        elseif($att&&$att['hora_entrada']&&!$att['hora_salida']){$cls=($date===$today)?'cap-warn':'cap-err';$lbl=($date===$today)?'Abierta '.substr($att['hora_entrada'],0,5):'Sin salida';}
        elseif($hasSchedule&&$date<=$today){$cls='cap-absent';$lbl='Ausente';}
        else{$cls='cap-none';$lbl='—';}
        $editUrl='editar_fichaje.php?'.($att?"id={$att['id']}":"emp={$empId}&fecha={$date}");
      ?>
      <td><a class="cap-link" href="<?=$editUrl?>"><span class="cap <?=$cls?>"><?=$lbl?></span></a></td>
      <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php layout_close(); ?>
