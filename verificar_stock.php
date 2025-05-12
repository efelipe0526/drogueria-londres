<?php
include 'db.php'; // Include database connection

// Initialize the database connection
$db = new Database();
$conexion = $db->getConnection();

// Set headers for JSON response
header('Content-Type: application/json');

// Get product and laboratory IDs from the request
$producto_id = isset($_GET['producto_id']) ? intval($_GET['producto_id']) : 0;
$laboratorio_id = isset($_GET['laboratorio_id']) ? intval($_GET['laboratorio_id']) : 0;

// Validate input
if ($producto_id <= 0 || $laboratorio_id <= 0) {
    echo json_encode(['error' => 'Producto o laboratorio no válido']);
    exit;
}

try {
    // Query to fetch stock from producto_laboratorio and expiration date from productos
    $query = "SELECT pl.stock, pl.unidad AS unidad_restante, pl.unidad_original, p.fecha_vencimiento 
              FROM producto_laboratorio pl
              JOIN productos p ON p.id = pl.producto_id
              WHERE pl.producto_id = ? AND pl.laboratorio_id = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('ii', $producto_id, $laboratorio_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        // Ensure all fields are present in the response
        $response = [
            'stock' => isset($data['stock']) ? intval($data['stock']) : 0,
            'unidad_restante' => isset($data['unidad_restante']) ? intval($data['unidad_restante']) : 0,
            'unidad_original' => isset($data['unidad_original']) ? intval($data['unidad_original']) : 0,
            'fecha_vencimiento' => isset($data['fecha_vencimiento']) ? $data['fecha_vencimiento'] : null
        ];
        echo json_encode($response);
    } else {
        echo json_encode(['error' => 'No se encontraron datos para el producto y laboratorio especificados']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al consultar la base de datos: ' . $e->getMessage()]);
}

$conexion->close();
