<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/layout.php';
require_login();
$db=fich_pdo();
$msg='';$msgType='ok';
$empleados=get_empleados($db);
$selectedEmp=(int)($_REQUEST['emp']??0);
$periodStart=$_REQUEST['fecha_inicio']??date('Y-m-d');
$periodEndRaw=trim($_REQUEST['fecha_final']??'');
$periodEnd=$periodEndRaw===''?null:$periodEndRaw;

if($_SERVER['REQUEST_METHOD']==='POST'&&$selectedEmp>0){
    if($periodEnd!==null&&$periodEnd<$periodStart){$msg='La fecha final no puede ser anterior a la de inicio.';$msgType='error';}
    else{
        $dias=$db->query("SELECT id FROM dias_de_la_semana ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        foreach($dias as $d){
            $times=array_values(array_filter($_POST['slots'][$d]??[],fn($t)=>preg_match('/^\d{2}:\d{2}$/',$t)));
            if($periodEnd===null){$del=$db->prepare("DELETE FROM horarios WHERE empleado=? AND dia_de_la_semana=? AND fecha_inicio=? AND fecha_final IS NULL");$del->execute([$selectedEmp,$d,$periodStart]);}
            else{$del=$db->prepare("DELETE FROM horarios WHERE empleado=? AND dia_de_la_semana=? AND fecha_inicio=? AND fecha_final=?");$del->execute([$selectedEmp,$d,$periodStart,$periodEnd]);}
            if(empty($times)) continue;
            sort($times);
            $blocks=[];$block=[$times[0]];
            for($i=1;$i<count($times);$i++){
                $prev=DateTime::createFromFormat('H:i',$times[$i-1]);
                $curr=DateTime::createFromFormat('H:i',$times[$i]);
                if(($curr->getTimestamp()-$prev->getTimestamp())/60<=30) $block[]=$times[$i];
                else{$blocks[]=$block;$block=[$times[$i]];}
            }
            $blocks[]=$block;
            foreach($blocks as $blk){
                $in_dt=DateTime::createFromFormat('H:i',reset($blk));
                $out_dt=DateTime::createFromFormat('H:i',end($blk));
                $out_dt->add(new DateInterval('PT30M'));
                if($periodEnd===null){$ins=$db->prepare("INSERT INTO horarios(empleado,dia_de_la_semana,fecha_inicio,fecha_final,hora_de_entrada,hora_de_salida) VALUES(?,?,?,NULL,?,?)");$ins->execute([$selectedEmp,$d,$periodStart,$in_dt->format('H:i:s'),$out_dt->format('H:i:s')]);}
                else{$ins=$db->prepare("INSERT INTO horarios(empleado,dia_de_la_semana,fecha_inicio,fecha_final,hora_de_entrada,hora_de_salida) VALUES(?,?,?,?,?,?)");$ins->execute([$selectedEmp,$d,$periodStart,$periodEnd,$in_dt->format('H:i:s'),$out_dt->format('H:i:s')]);}
            }
        }
        $msg='Horario guardado.';
    }
}

$existingSlots=[];$totalSel=0;
if($selectedEmp){
    if($periodEnd===null){$st=$db->prepare("SELECT dia_de_la_semana,hora_de_entrada,hora_de_salida FROM horarios WHERE empleado=? AND fecha_inicio=? AND fecha_final IS NULL ORDER BY dia_de_la_semana,hora_de_entrada");$st->execute([$selectedEmp,$periodStart]);}
    else{$st=$db->prepare("SELECT dia_de_la_semana,hora_de_entrada,hora_de_salida FROM horarios WHERE empleado=? AND fecha_inicio=? AND fecha_final=? ORDER BY dia_de_la_semana,hora_de_entrada");$st->execute([$selectedEmp,$periodStart,$periodEnd]);}
    foreach($st->fetchAll() as $h){
        $dia=(int)$h['dia_de_la_semana'];
        $start=DateTime::createFromFormat('H:i:s',$h['hora_de_entrada']);
        $end=DateTime::createFromFormat('H:i:s',$h['hora_de_salida']);
        while($start<$end){$existingSlots[$dia][]=$start->format('H:i');$start->add(new DateInterval('PT30M'));$totalSel++;}
    }
}
$diaNames=['','Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
layout_open('Horarios','horarios');
?>
<h2 style="margin:0 0 16px;font-size:1.1rem;">Editor de horarios teóricos</h2>
<?php if($msg): ?><div class="flash <?=$msgType?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<form method="get" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;">
  <div class="form-group" style="min-width:200px;"><label>Empleado</label>
    <select name="emp"><option value="0">-- Selecciona --</option>
    <?php foreach($empleados as $e): ?><option value="<?=(int)$e['id']?>" <?=(int)$e['id']===$selectedEmp?'selected':''?>><?=htmlspecialchars($e['nombre'].' '.$e['apellido'])?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label>Fecha inicio</label><input type="date" name="fecha_inicio" value="<?=htmlspecialchars($periodStart)?>"></div>
  <div class="form-group"><label>Fecha final <span style="color:var(--muted);font-weight:400">(vacío=indefinido)</span></label><input type="date" name="fecha_final" value="<?=htmlspecialchars($periodEnd??'')?>"></div>
  <button class="btn btn-secondary" type="submit">Cargar</button>
</form>
<?php if($selectedEmp): ?>
<form id="slotsForm" method="post">
  <input type="hidden" name="emp" value="<?=$selectedEmp?>">
  <input type="hidden" name="fecha_inicio" value="<?=htmlspecialchars($periodStart)?>">
  <input type="hidden" name="fecha_final" value="<?=htmlspecialchars($periodEnd??'')?>">
  <div style="overflow-x:auto;margin-bottom:12px;">
  <div class="slot-grid" style="grid-template-rows:36px repeat(48,12px);">
    <div class="sh" style="grid-column:1;grid-row:1;"></div>
    <?php for($d=1;$d<=7;$d++): ?><div class="sh" style="grid-column:<?=$d+1?>;grid-row:1;"><?=$diaNames[$d]?></div><?php endfor; ?>
    <?php for($h=0;$h<24;$h++): foreach(['00','30'] as $m):
      $row=($h*2+($m==='30'?1:0))+2; $time=sprintf('%02d:%s',$h,$m);
    ?>
    <div class="st" style="grid-column:1;grid-row:<?=$row?>;"><?=$m==='00'?sprintf('%02d',$h).':00':''?></div>
    <?php for($d=1;$d<=7;$d++): $sel=(!empty($existingSlots[$d])&&in_array($time,$existingSlots[$d],true))?'selected':''; ?>
    <div class="slot-cell <?=$sel?>" style="grid-column:<?=$d+1?>;grid-row:<?=$row?>;" data-dia="<?=$d?>" data-time="<?=$time?>"></div>
    <?php endfor; ?>
    <?php endforeach; endfor; ?>
  </div></div>
  <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px;flex-wrap:wrap;">
    <span id="totalHoras" style="color:var(--accent);font-weight:800;"><?=$totalSel*0.5?> horas seleccionadas</span>
    <button class="btn btn-primary" type="submit">Guardar horario</button>
    <button class="btn btn-secondary" type="button" onclick="document.querySelectorAll('.slot-cell.selected').forEach(c=>c.classList.remove('selected'));updateTotal()">Limpiar</button>
  </div>
</form>
<script>
document.querySelectorAll('.slot-cell').forEach(c=>c.addEventListener('click',()=>{c.classList.toggle('selected');updateTotal();}));
function updateTotal(){document.getElementById('totalHoras').textContent=(document.querySelectorAll('.slot-cell.selected').length*0.5).toFixed(1)+' horas seleccionadas';}
document.getElementById('slotsForm').addEventListener('submit',function(){
  document.querySelectorAll('.hiddenSlot').forEach(e=>e.remove());
  document.querySelectorAll('.slot-cell.selected').forEach(c=>{const i=document.createElement('input');i.type='hidden';i.name=`slots[${c.dataset.dia}][]`;i.value=c.dataset.time;i.className='hiddenSlot';this.appendChild(i);});
});
</script>
<?php else: ?><p style="color:var(--muted)">Selecciona un empleado para editar su horario.</p><?php endif; ?>
<?php layout_close(); ?>
