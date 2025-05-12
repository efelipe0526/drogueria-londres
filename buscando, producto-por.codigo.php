<?php
header('Content-Type: application/json');

include 'db.php';

$db = new Database();
$conexion = $db->getConnection();

$codigo_barras = $_GET['codigo_barras'] ?? '';

if (empty($codigo_barras)) {
    echo json_encode(['error' => 'Código de barras no proporcionado']);
    exit;
}

$stmt = $conexion->prepare("SELECT id, categoria_id FROM productos WHERE codigo_barras = ?");
$stmt->bind_param("s", $codigo_barras);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Producto no encontrado']);
    $stmt->close();
    exit;
}

$producto = $result->fetch_assoc();
echo json_encode($producto);

$stmt->close();
$conexion->close();
