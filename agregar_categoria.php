<?php
include 'db.php';

$db = new Database();

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_categoria = $_POST['nombre_categoria'];

    // Insertar la nueva categoría en la base de datos
    $sql = "INSERT INTO categorias (nombre) VALUES ('$nombre_categoria')";
    $db->query($sql);

    // Redirigir de vuelta a productos.php
    header("Location: productos.php");
    exit();
}
?>