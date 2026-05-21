<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/layout.php';
require_login();
$db=fich_pdo();
$today=date('Y-m-d');
$weekStartTs=strtotime('monday this week');
$weekStart=date('Y-m-d',$weekStartTs);
$weekEnd=date('Y-m-d',strtotime('sunday this week'));
$empleados=get_empleados($db);
$empById=[];
foreach($empleados as $e) $empById[(int)$e['id']]=$e['nombre'].' '.$e['apellido'];
$allIds=array_keys($empById);

$abiertas=[];
if($allIds){$in=implode(',',$allIds);$st=$db->query("SELECT id,empleado_id,hora_entrada FROM Asistencia WHERE fecha='$today' AND hora_entrada IS NOT NULL AND hora_salida IS NULL AND empleado_id IN ($in)");$abiertas=$st->fetchAll();}

$errores=[];
if($allIds){$in=implode(',',$allIds);$st=$db->query("SELECT id,empleado_id,fecha,hora_entrada FROM Asistencia WHERE fecha BETWEEN '$weekStart' AND '$today' AND fecha<>'$today' AND hora_entrada IS NOT NULL AND hora_salida IS NULL AND empleado_id IN ($in)");$errores=$st->fetchAll();}

$ausencias=[];
$weekDates=[];
for($i=0;$i<7;$i++){$d=date('Y-m-d',strtotime("+{$i} days",$weekStartTs));if($d<$today) $weekDates[]=$d;}
$festivos=[];
if($weekDates){$inD="'".implode("','",$weekDates)."'";$st=$db->query("SELECT fecha FROM Festivos WHERE fecha IN ($inD)");foreach($st->fetchAll() as $r) $festivos[$r['fecha']]=true;}
if($allIds&&$weekDates){
    $in=implode(',',$allIds);$inD="'".implode("','",$weekDates)."'";
    $st=$db->query("SELECT empleado,dia_de_la_semana,fecha_inicio,fecha_final,hora_de_entrada,hora_de_salida FROM horarios WHERE empleado IN ($in) AND (fecha_inicio IS NULL OR fecha_inicio<='$weekEnd') AND (fecha_final IS NULL OR fecha_final>='$weekStart')");
    $horarios=$st->fetchAll();
    $st2=$db->query("SELECT empleado_id,fecha FROM Asistencia WHERE fecha IN ($inD) AND empleado_id IN ($in)");
    $asistidas=[];foreach($st2->fetchAll() as $r) $asistidas[(int)$r['empleado_id']][$r['fecha']]=true;
    foreach($horarios as $h){
        $empId=(int)$h['empleado'];$dow=(int)$h['dia_de_la_semana']-1;
        foreach($weekDates as $date){
            if((int)date('N',strtotime($date))-1!==$dow) continue;
            if(!empty($h['fecha_inicio'])&&$h['fecha_inicio']>$date) continue;
            if(!empty($h['fecha_final'])&&$h['fecha_final']<$date) continue;
            if(isset($festivos[$date])) continue;
            if(!isset($asistidas[$empId][$date])) $ausencias[]=['empleado_id'=>$empId,'fecha'=>$date];
        }
    }
    $seen=[];$ausencias=array_filter($ausencias,function($a)use(&$seen){$k=$a['empleado_id'].'-'.$a['fecha'];if(isset($seen[$k]))return false;$seen[$k]=true;return true;});
}
layout_open('Alertas','alertas');
?>
<h2 style="margin:0 0 20px;font-size:1.1rem;">Panel de alertas — semana <?=$weekStart?> / <?=$weekEnd?></h2>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;">
  <div class="card">
    <div class="card-title" style="color:var(--warn);">⚠ Jornadas abiertas hoy (<?=count($abiertas)?>)</div>
    <?php if($abiertas): foreach($abiertas as $a): ?>
    <div class="alert-chip warn"><span>🕐</span><div><div><?=htmlspecialchars($empById[$a['empleado_id']]??'?')?></div><div style="font-size:.78rem;opacity:.75">Entrada: <?=substr($a['hora_entrada'],0,5)?></div></div><a href="editar_fichaje.php?id=<?=$a['id']?>" class="btn btn-secondary btn-sm" style="margin-left:auto">Editar</a></div>
    <?php endforeach; else: ?><p style="color:var(--muted);font-size:.88rem;">Sin jornadas abiertas ✓</p><?php endif; ?>
  </div>
  <div class="card">
    <div class="card-title" style="color:var(--err);">✗ Errores esta semana (<?=count($errores)?>)</div>
    <?php if($errores): foreach($errores as $e): ?>
    <div class="alert-chip err"><span>⚡</span><div><div><?=htmlspecialchars($empById[$e['empleado_id']]??'?')?></div><div style="font-size:.78rem;opacity:.75"><?=$e['fecha']?> — entrada <?=substr($e['hora_entrada'],0,5)?> sin salida</div></div><a href="editar_fichaje.php?id=<?=$e['id']?>" class="btn btn-danger btn-sm" style="margin-left:auto">Corregir</a></div>
    <?php endforeach; else: ?><p style="color:var(--muted);font-size:.88rem;">Sin errores ✓</p><?php endif; ?>
  </div>
  <div class="card">
    <div class="card-title" style="color:var(--muted);">● Ausencias esta semana (<?=count($ausencias)?>)</div>
    <?php if($ausencias): foreach($ausencias as $a): ?>
    <div class="alert-chip absent"><span>👤</span><div><div><?=htmlspecialchars($empById[$a['empleado_id']]??'?')?></div><div style="font-size:.78rem;opacity:.75"><?=$a['fecha']?></div></div><a href="editar_fichaje.php?emp=<?=$a['empleado_id']?>&fecha=<?=$a['fecha']?>" class="btn btn-secondary btn-sm" style="margin-left:auto">Justificar</a></div>
    <?php endforeach; else: ?><p style="color:var(--muted);font-size:.88rem;">Sin ausencias ✓</p><?php endif; ?>
  </div>
</div>
<?php layout_close(); ?>
