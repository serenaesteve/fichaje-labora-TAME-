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
        $fecha=$_POST['fecha']??'';$nombre=trim($_POST['nombre']??'');$desc=trim($_POST['descripcion']??'');$id=(int)($_POST['id']??0);
        if(!$fecha||!$nombre){$msg='Fecha y nombre son obligatorios.';$msgType='error';}
        else{
            if($id>0){$db->prepare("UPDATE Festivos SET fecha=?,nombre=?,descripcion=? WHERE id=?")->execute([$fecha,$nombre,$desc,$id]);$msg='Festivo actualizado.';}
            else{$db->prepare("INSERT INTO Festivos(fecha,nombre,descripcion) VALUES(?,?,?)")->execute([$fecha,$nombre,$desc]);$msg='Festivo añadido.';}
        }
    } elseif($action==='delete'){$id=(int)($_POST['id']??0);if($id>0){$db->prepare("DELETE FROM Festivos WHERE id=?")->execute([$id]);$msg='Festivo eliminado.';}}
}
$editing=null;
if(isset($_GET['edit'])){$st=$db->prepare("SELECT * FROM Festivos WHERE id=?");$st->execute([(int)$_GET['edit']]);$editing=$st->fetch();}
$festivos=$db->query("SELECT * FROM Festivos ORDER BY fecha DESC")->fetchAll();
layout_open('Festivos','festivos');
?>
<h2 style="margin:0 0 16px;font-size:1.1rem;">Gestión de festivos</h2>
<?php if($msg): ?><div class="flash <?=$msgType?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<div style="display:grid;grid-template-columns:320px 1fr;gap:20px;align-items:start;">
  <div class="card">
    <div class="card-title"><?=$editing?'Editar festivo':'Añadir festivo'?></div>
    <form method="post">
      <input type="hidden" name="action" value="save">
      <?php if($editing): ?><input type="hidden" name="id" value="<?=(int)$editing['id']?>"><?php endif; ?>
      <div class="form-group" style="margin-bottom:10px;"><label>Fecha</label><input type="date" name="fecha" required value="<?=htmlspecialchars($editing['fecha']??'')?>"></div>
      <div class="form-group" style="margin-bottom:10px;"><label>Nombre</label><input type="text" name="nombre" required value="<?=htmlspecialchars($editing['nombre']??'')?>"></div>
      <div class="form-group" style="margin-bottom:14px;"><label>Descripción</label><input type="text" name="descripcion" value="<?=htmlspecialchars($editing['descripcion']??'')?>"></div>
      <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" type="submit"><?=$editing?'Guardar':'Añadir'?></button>
        <?php if($editing): ?><a href="festivos.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
      </div>
    </form>
  </div>
  <div class="table-wrap"><table>
    <thead><tr><th>Fecha</th><th>Nombre</th><th>Descripción</th><th></th></tr></thead>
    <tbody>
      <?php foreach($festivos as $f): ?>
      <tr><td><?=htmlspecialchars($f['fecha'])?></td><td><?=htmlspecialchars($f['nombre'])?></td><td style="color:var(--muted)"><?=htmlspecialchars($f['descripcion'])?></td>
      <td style="white-space:nowrap;"><a href="?edit=<?=$f['id']?>" class="btn btn-secondary btn-sm">Editar</a>
        <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$f['id']?>"><button class="btn btn-danger btn-sm">Eliminar</button></form>
      </td></tr>
      <?php endforeach; ?>
      <?php if(!$festivos): ?><tr><td colspan="4" style="color:var(--muted);text-align:center;">Sin festivos</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<?php layout_close(); ?>
