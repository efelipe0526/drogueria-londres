<?php
include 'db.php';

$db = new Database();
$conexion = $db->getConnection();

$categoria_id = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : 0;

header('Content-Type: application/json');

if ($categoria_id > 0) {
    try {
        $query = $conexion->prepare("SELECT p.id, p.nombre, p.precio, p.precio_por_unidad, pl.stock, pl.unidad_original as unidad 
                                    FROM productos p 
                                    LEFT JOIN producto_laboratorio pl ON p.id = pl.producto_id 
                                    WHERE p.categoria_id = ?");
        $query->bind_param("i", $categoria_id);
        $query->execute();
        $result = $query->get_result();

        $productos = [];

        while ($row = $result->fetch_assoc()) {
            $productos[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'precio' => $row['precio'],
                'stock' => $row['stock'] ?? 0,
                'unidad' => $row['unidad'] ?? '',
                'precio_por_unidad' => $row['precio_por_unidad'] ?? 0
            ];
        }

        echo json_encode($productos);
    } catch (Exception $e) {
        error_log("Error en obtener_productos.php: " . $e->getMessage());
        echo json_encode(['error' => 'Error al obtener productos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}
