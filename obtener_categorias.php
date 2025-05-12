<?php
include 'db.php';

$db = new Database();
$result = $db->query("SELECT * FROM categorias");
$categorias = [];

while ($row = $result->fetch_assoc()) {
    $categorias[] = $row;
}

echo json_encode($categorias);
?>