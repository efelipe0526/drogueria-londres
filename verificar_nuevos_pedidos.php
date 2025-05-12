<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$password = "";
$database = "marci";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Error de conexión']));
}

// Obtener el ID del último pedido
$result = $conn->query("SELECT MAX(id) AS ultimo_pedido_id FROM pedidos");
$row = $result->fetch_assoc();

echo json_encode([
    'ultimo_pedido_id' => $row['ultimo_pedido_id'] ? (int)$row['ultimo_pedido_id'] : 0
]);

$conn->close();
?>