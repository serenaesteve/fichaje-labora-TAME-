<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/layout.php';
require_login();
$db=fich_pdo();
$msg='';$msgType='ok';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';
    if($action==='save'){
        $id=(int)($_POST['id']??0);
        $nombre=trim($_POST['nombre']??'');
        $apellido=trim($_POST['apellido']??'');
        $pin=trim($_POST['pin']??'0000');
        $email=trim($_POST['email']??'');
        $activo=(int)($_POST['activo']??1);
        if(!$nombre||!$apellido){$msg='Nombre y apellido son obligatorios.';$msgType='error';}
        elseif(!preg_match('/^\d{4}$/',$pin)){$msg='El PIN debe ser 4 dígitos.';$msgType='error';}
        else{
            if($id>0){
                $db->prepare("UPDATE empleados SET nombre=?,apellido=?,pin=?,email=?,activo=? WHERE id=?")->execute([$nombre,$apellido,$pin,$email,$activo,$id]);
                $msg='Empleado actualizado.';
            } else {
                $db->prepare("INSERT INTO empleados(nombre,apellido,pin,email,activo) VALUES(?,?,?,?,?)")->execute([$nombre,$apellido,$pin,$email,$activo]);
                $msg='Empleado creado.';
            }
        }
    } elseif($action==='delete'){
        $id=(int)($_POST['id']??0);
        if($id>0){$db->prepare("DELETE FROM empleados WHERE id=?")->execute([$id]);$msg='Empleado eliminado.';}
    }
}

$editing=null;
if(isset($_GET['edit'])){
    $st=$db->prepare("SELECT * FROM empleados WHERE id=?");
    $st->execute([(int)$_GET['edit']]);
    $editing=$st->fetch();
}

$empleados=$db->query("SELECT * FROM empleados ORDER BY nombre,apellido")->fetchAll();
layout_open('Empleados','empleados');
?>
<h2 style="margin:0 0 16px;font-size:1.1rem;">Gestión de empleados</h2>
<?php if($msg): ?><div class="flash <?=$msgType?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<div style="display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start;flex-wrap:wrap;">
  <div class="card">
    <div class="card-title"><?=$editing?'Editar empleado':'Nuevo empleado'?></div>
    <form method="post">
      <input type="hidden" name="action" value="save">
      <?php if($editing): ?><input type="hidden" name="id" value="<?=(int)$editing['id']?>"><?php endif; ?>
      <div class="form-group" style="margin-bottom:10px;"><label>Nombre</label><input type="text" name="nombre" required value="<?=htmlspecialchars($editing['nombre']??'')?>"></div>
      <div class="form-group" style="margin-bottom:10px;"><label>Apellido</label><input type="text" name="apellido" required value="<?=htmlspecialchars($editing['apellido']??'')?>"></div>
      <div class="form-group" style="margin-bottom:10px;"><label>PIN (4 dígitos)</label><input type="text" name="pin" maxlength="4" pattern="\d{4}" value="<?=htmlspecialchars($editing['pin']??'0000')?>" placeholder="0000"></div>
      <div class="form-group" style="margin-bottom:10px;"><label>Email</label><input type="email" name="email" value="<?=htmlspecialchars($editing['email']??'')?>"></div>
      <div class="form-group" style="margin-bottom:14px;"><label>Estado</label>
        <select name="activo">
          <option value="1" <?=empty($editing)||$editing['activo']?'selected':''?>>Activo</option>
          <option value="0" <?=isset($editing)&&!$editing['activo']?'selected':''?>>Inactivo</option>
        </select>
      </div>
      <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" type="submit"><?=$editing?'Guardar':'Crear'?></button>
        <?php if($editing): ?><a href="empleados.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
      </div>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Nombre</th><th>PIN</th><th>Email</th><th>Estado</th><th></th></tr></thead>
      <tbody>
        <?php foreach($empleados as $e): ?>
        <tr>
          <td><?=htmlspecialchars($e['nombre'].' '.$e['apellido'])?></td>
          <td><code><?=htmlspecialchars($e['pin'])?></code></td>
          <td style="color:var(--muted)"><?=htmlspecialchars($e['email']??'')?></td>
          <td><span class="cap <?=$e['activo']?'cap-ok':'cap-absent'?>"><?=$e['activo']?'Activo':'Inactivo'?></span></td>
          <td style="white-space:nowrap;">
            <a href="?edit=<?=$e['id']?>" class="btn btn-secondary btn-sm">Editar</a>
            <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?=$e['id']?>">
              <button class="btn btn-danger btn-sm">Eliminar</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$empleados): ?><tr><td colspan="5" style="color:var(--muted);text-align:center;">Sin empleados. Crea el primero.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layout_close(); ?>
