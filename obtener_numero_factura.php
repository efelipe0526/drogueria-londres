<?php
include 'db.php';

$db = new Database();

// Obtener el número de factura inicial desde la base de datos
$numero_factura_query = $db->query("SELECT numero_factura_inicial FROM configuracion_impuestos WHERE id = 1");
$numero_factura = $numero_factura_query->fetch_assoc()['numero_factura_inicial'] ?? 100;

// Incrementar el número de factura
$nuevo_numero_factura = intval($numero_factura) + 1;

// Actualizar el número de factura en la base de datos
$db->query("UPDATE configuracion_impuestos SET numero_factura_inicial = $nuevo_numero_factura WHERE id = 1");

// Devolver el nuevo número de factura en formato JSON
echo json_encode(['numero_factura' => $nuevo_numero_factura]);
?>