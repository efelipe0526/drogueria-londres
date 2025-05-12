<?php
include 'db.php';

$db = new Database();
$conexion = $db->getConnection();

$numero_factura = isset($_GET['numero_factura']) ? intval($_GET['numero_factura']) : 0;

if ($numero_factura > 0) {
    $query = $conexion->prepare("SELECT COUNT(*) as total FROM ventas WHERE numero_factura = ?");
    $query->bind_param("i", $numero_factura);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['existe' => $row['total'] > 0]);
    } else {
        echo json_encode(['existe' => false]);
    }
} else {
    echo json_encode(['existe' => false]);
}
?>