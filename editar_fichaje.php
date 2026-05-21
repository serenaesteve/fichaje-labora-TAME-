<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/layout.php';
require_login();
$db=fich_pdo();
$msg='';$msgType='ok';
$empleados=$db->query("SELECT id,nombre,apellido FROM empleados WHERE activo=1 ORDER BY nombre,apellido")->fetchAll();
$id=(int)($_GET['id']??0);$empId=(int)($_GET['emp']??0);$fecha=$_GET['fecha']??date('Y-m-d');$reg=null;
if($id>0){$st=$db->prepare("SELECT * FROM Asistencia WHERE id=? LIMIT 1");$st->execute([$id]);$reg=$st->fetch();if($reg){$empId=(int)$reg['empleado_id'];$fecha=$reg['fecha'];}}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'save';
    $pEmpId=(int)($_POST['empleado_id']??$empId);$pFecha=$_POST['fecha']??$fecha;
    $pEntrada=$_POST['hora_entrada']??'';$pSalida=$_POST['hora_salida']??'';$pNota=trim($_POST['nota']??'');
    if($action==='delete'&&$id>0){$db->prepare("DELETE FROM Asistencia WHERE id=?")->execute([$id]);header('Location: resumen.php');exit;}
    if(!$pEmpId||!$pFecha){$msg='Empleado y fecha son obligatorios.';$msgType='error';}
    else{
        $u=current_user();
        if($id>0){$db->prepare("UPDATE Asistencia SET empleado_id=?,fecha=?,hora_entrada=?,hora_salida=?,nota=?,editado_por=? WHERE id=?")->execute([$pEmpId,$pFecha,$pEntrada?:null,$pSalida?:null,$pNota,$u['id']??null,$id]);$msg='Fichaje actualizado.';}
        else{
            $check=$db->prepare("SELECT id FROM Asistencia WHERE empleado_id=? AND fecha=? LIMIT 1");$check->execute([$pEmpId,$pFecha]);$existing=$check->fetch();
            if($existing){$db->prepare("UPDATE Asistencia SET hora_entrada=?,hora_salida=?,nota=?,editado_por=? WHERE id=?")->execute([$pEntrada?:null,$pSalida?:null,$pNota,$u['id']??null,$existing['id']]);$id=(int)$existing['id'];$msg='Fichaje actualizado.';}
            else{$db->prepare("INSERT INTO Asistencia(empleado_id,fecha,hora_entrada,hora_salida,nota,editado_por) VALUES(?,?,?,?,?,?)")->execute([$pEmpId,$pFecha,$pEntrada?:null,$pSalida?:null,$pNota,$u['id']??null]);$id=(int)$db->lastInsertId();$msg='Fichaje creado.';}
        }
        $st=$db->prepare("SELECT * FROM Asistencia WHERE id=? LIMIT 1");$st->execute([$id]);$reg=$st->fetch();
        if($reg){$empId=(int)$reg['empleado_id'];$fecha=$reg['fecha'];}
    }
}
layout_open('Editar fichaje','');
?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
  <a href="resumen.php" class="btn btn-secondary btn-sm">← Volver</a>
  <h2 style="margin:0;font-size:1.1rem;"><?=$id>0?'Editar':'Crear'?> fichaje</h2>
</div>
<?php if($msg): ?><div class="flash <?=$msgType?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<div class="card" style="max-width:480px;">
<form method="post">
  <input type="hidden" name="action" value="save">
  <div class="form-group" style="margin-bottom:10px;"><label>Empleado</label>
    <select name="empleado_id" required>
      <?php foreach($empleados as $e): ?><option value="<?=(int)$e['id']?>" <?=(int)$e['id']===$empId?'selected':''?>><?=htmlspecialchars($e['nombre'].' '.$e['apellido'])?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="form-group" style="margin-bottom:10px;"><label>Fecha</label><input type="date" name="fecha" required value="<?=htmlspecialchars($reg['fecha']??$fecha)?>"></div>
  <div class="form-group" style="margin-bottom:10px;"><label>Hora entrada</label><input type="time" name="hora_entrada" value="<?=htmlspecialchars(substr($reg['hora_entrada']??'',0,5))?>"></div>
  <div class="form-group" style="margin-bottom:10px;"><label>Hora salida</label><input type="time" name="hora_salida" value="<?=htmlspecialchars(substr($reg['hora_salida']??'',0,5))?>"></div>
  <div class="form-group" style="margin-bottom:16px;"><label>Nota interna</label><input type="text" name="nota" value="<?=htmlspecialchars($reg['nota']??'')?>"></div>
  <div style="display:flex;gap:8px;">
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a href="resumen.php" class="btn btn-secondary">Cancelar</a>
    <?php if($id>0): ?><button class="btn btn-danger" type="submit" name="action" value="delete" onclick="return confirm('¿Eliminar este fichaje?')" style="margin-left:auto">Eliminar</button><?php endif; ?>
  </div>
</form>
</div>
<?php layout_close(); ?>
