<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../roles/login.php");
    exit();
}

// Verificar si el usuario tiene acceso al módulo solicitado
$modulo_solicitado = basename($_SERVER['PHP_SELF'], '.php');
$modulos_asignados = $_SESSION['modulos_asignados'] ?? [];

if (!in_array($modulo_solicitado, $modulos_asignados)) {
    header("Location: ../index.php");
    exit();
}
?>