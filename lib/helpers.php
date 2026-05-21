<?php
declare(strict_types=1);

function fmtH(int $mins): string {
    return floor($mins / 60) . 'h' . sprintf('%02d', $mins % 60);
}

function semana_actual(): array {
    $ts = isset($_GET['week']) ? strtotime($_GET['week']) : strtotime('monday this week');
    $ts = strtotime('monday this week', $ts);
    return [
        'start' => date('Y-m-d', $ts),
        'end'   => date('Y-m-d', strtotime('+6 days', $ts)),
        'prev'  => date('Y-m-d', strtotime('-7 days', $ts)),
        'next'  => date('Y-m-d', strtotime('+7 days', $ts)),
        'ts'    => $ts,
        'label' => 'Semana del ' . date('d/m', $ts) . ' al ' . date('d/m/Y', strtotime('+6 days', $ts)),
    ];
}

function get_empleados(PDO $db): array {
    return $db->query("SELECT id, nombre, apellido FROM empleados WHERE activo=1 ORDER BY nombre, apellido")->fetchAll();
}

function get_festivos(PDO $db, string $start, string $end): array {
    $st = $db->prepare("SELECT fecha, nombre, descripcion FROM Festivos WHERE fecha BETWEEN ? AND ?");
    $st->execute([$start, $end]);
    $out = [];
    foreach ($st->fetchAll() as $r) $out[$r['fecha']] = $r;
    return $out;
}

function get_horario_teorico(PDO $db, int $empId, string $start, string $end, array $festivos, int $weekStartTs): array {
    $st = $db->prepare("
        SELECT dia_de_la_semana, hora_de_entrada, hora_de_salida, fecha_inicio, fecha_final
        FROM horarios WHERE empleado=?
        AND (fecha_inicio IS NULL OR fecha_inicio<=?)
        AND (fecha_final  IS NULL OR fecha_final >=?)
    ");
    $st->execute([$empId, $end, $start]);
    $theoretical = []; $dueMinutes = 0;
    foreach ($st->fetchAll() as $r) {
        $dayIdx  = (int)$r['dia_de_la_semana'] - 1;
        $dayDate = date('Y-m-d', strtotime("+{$dayIdx} days", $weekStartTs));
        if (isset($festivos[$dayDate])) continue;
        if (!empty($r['fecha_inicio']) && $r['fecha_inicio'] > $dayDate) continue;
        if (!empty($r['fecha_final'])  && $r['fecha_final']  < $dayDate) continue;
        if (empty($r['hora_de_entrada']) || empty($r['hora_de_salida'])) continue;
        [$h1,$m1] = explode(':', $r['hora_de_entrada']);
        [$h2,$m2] = explode(':', $r['hora_de_salida']);
        $startMin = (int)$h1*60+(int)$m1;
        $dur = (int)$h2*60+(int)$m2 - $startMin;
        if ($dur<=0) continue;
        $dueMinutes += $dur;
        $theoretical[] = ['day'=>$dayIdx,'start'=>$startMin,'duration'=>$dur];
    }
    return ['slots'=>$theoretical,'due'=>$dueMinutes];
}

function get_asistencia_semana(PDO $db, int $empId, string $start, string $end): array {
    $st = $db->prepare("SELECT id,fecha,hora_entrada,hora_salida,nota FROM Asistencia WHERE empleado_id=? AND fecha BETWEEN ? AND ? ORDER BY fecha,hora_entrada");
    $st->execute([$empId,$start,$end]);
    $events=[]; $worked=0;
    foreach ($st->fetchAll() as $r) {
        if (!$r['hora_entrada']||!$r['hora_salida']) continue;
        [$h1,$m1]=explode(':',$r['hora_entrada']);
        [$h2,$m2]=explode(':',$r['hora_salida']);
        $startMin=(int)$h1*60+(int)$m1;
        $dur=(int)$h2*60+(int)$m2-$startMin;
        if ($dur<=0) continue;
        $worked+=$dur;
        $events[]=['id'=>$r['id'],'day'=>(int)date('N',strtotime($r['fecha']))-1,'start'=>$startMin,'duration'=>$dur,'fecha'=>$r['fecha'],'entrada'=>substr($r['hora_entrada'],0,5),'salida'=>substr($r['hora_salida'],0,5),'nota'=>$r['nota']??''];
    }
    return ['events'=>$events,'worked'=>$worked];
}
