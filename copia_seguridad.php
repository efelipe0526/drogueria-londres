<?php
// Archivo: copia_seguridad.php

// Habilitar la visualización de errores (solo para desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si el usuario está logueado
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: roles/login.php"); // Redirigir al login si no está logueado
    exit();
}

// Configuración de la base de datos
define('DB_USER', 'root');      // Usuario de la base de datos
define('DB_PASS', '');          // Sin contraseña
define('DB_HOST', 'localhost'); // Host de la base de datos
define('DB_NAME', 'marci'); // Nombre de la base de datos

// Ruta de mysqldump.exe
$mysqldump_path = 'C:\xampp\mysql\bin\mysqldump.exe';

// Definir la ruta de la carpeta de copias de seguridad
$carpeta_copia = 'copia/';

// Verificar si la carpeta de copias existe, si no, crearla
if (!file_exists($carpeta_copia)) {
    if (!mkdir($carpeta_copia, 0777, true)) {
        die("Error: No se pudo crear la carpeta de copias de seguridad.");
    }
}

// Nombre del archivo de copia de seguridad
$nombre_archivo = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
$ruta_completa = $carpeta_copia . $nombre_archivo;

// Comando para exportar la base de datos usando mysqldump
$comando = "\"$mysqldump_path\" --user=" . DB_USER . " --password=" . DB_PASS . " --host=" . DB_HOST . " " . DB_NAME . " > " . $ruta_completa;

// Ejecutar el comando y capturar la salida y el código de retorno
exec($comando . " 2>&1", $output, $return_var);

// Verificar si la copia de seguridad se realizó correctamente
if ($return_var === 0) {
    $mensaje = "Copia de seguridad realizada correctamente. Archivo guardado en: " . $ruta_completa;
} else {
    $mensaje = "Error al realizar la copia de seguridad. Código de error: $return_var<br>Detalles: " . implode("<br>", $output);
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5">
    <!-- Botón para volver al menú principal -->
    <a href="index.php" class="btn btn-secondary mb-3">Volver al Menú Principal</a>

    <h1 class="mb-4">Copia de Seguridad</h1>

    <!-- Botón para realizar la copia de seguridad -->
    <form method="POST" action="copia_seguridad.php">
        <button type="submit" class="btn btn-primary">Realizar Copia de Seguridad</button>
    </form>

    <!-- Mostrar el mensaje de resultado -->
    <?php if (isset($mensaje)) : ?>
        <div class="alert <?php echo ($return_var === 0) ? 'alert-success' : 'alert-danger'; ?> mt-3">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>