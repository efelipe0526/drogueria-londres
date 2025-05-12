<?php
include 'db.php';

$db = new Database();
$conexion = $db->getConnection();

if (!$conexion) {
    // Si no hay conexión, devolver un mensaje de error en JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No se pudo conectar a la base de datos']);
    exit;
}

// Consulta para obtener los datos de los reportes
$query = "SELECT numero_factura, banco, cuenta, total_con_impuesto, monto_otra_forma_pago 
          FROM reportes 
          WHERE monto_otra_forma_pago > 0 OR total_con_impuesto > 0
          ORDER BY fecha DESC";
$result = $conexion->query($query);

if (!$result) {
    // Si hay un error en la consulta, devolver un mensaje de error en JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error en la consulta: ' . $conexion->error]);
    exit;
}

$reportes = [];

while ($row = $result->fetch_assoc()) {
    // Asegurarse de que los valores sean números
    $row['total_con_impuesto'] = (float)$row['total_con_impuesto'];
    $row['monto_otra_forma_pago'] = (float)$row['monto_otra_forma_pago'];
    $reportes[] = $row;
}

// Devolver los datos en formato JSON
header('Content-Type: application/json');
echo json_encode($reportes);
?>