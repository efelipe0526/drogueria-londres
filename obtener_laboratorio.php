<?php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable displaying errors to prevent JSON corruption

header('Content-Type: application/json'); // Set response type to JSON
require_once 'db.php'; // Include the database connection class

// Create an instance of the Database class
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    ob_end_clean();
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// Check if producto_id is provided
if (!isset($_GET['producto_id']) || empty($_GET['producto_id'])) {
    ob_end_clean();
    echo json_encode(['laboratorio_id' => '']);
    exit;
}

$producto_id = (int)$_GET['producto_id'];

// Query to get the laboratorio_id from producto_laboratorio
$sql = "SELECT laboratorio_id FROM producto_laboratorio WHERE producto_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    ob_end_clean();
    echo json_encode(['error' => 'Error al preparar la consulta: ' . $conn->error]);
    $db->close();
    exit;
}

$stmt->bind_param("i", $producto_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0 && $row = $result->fetch_assoc()) {
    ob_end_clean();
    echo json_encode(['laboratorio_id' => $row['laboratorio_id']]);
} else {
    ob_end_clean();
    echo json_encode(['laboratorio_id' => '']);
}

$stmt->close();
$db->close();
