<?php
include '../db.php'; // Incluye la conexión a la base de datos

$db = new Database();

// Obtener el ID del usuario desde la URL
$usuario_id = $_GET['usuario_id'] ?? null;

if (!$usuario_id) {
    die("ID de usuario no proporcionado.");
}

// Obtener todos los módulos disponibles
$modulos = $db->query("SELECT * FROM modulos");

// Obtener los módulos asignados al usuario
$modulos_asignados = [];
if ($usuario_id) {
    $result = $db->query("SELECT modulo_id FROM usuario_modulos WHERE usuario_id = $usuario_id");
    while ($row = $result->fetch_assoc()) {
        $modulos_asignados[] = $row['modulo_id'];
    }
}

// Procesar el formulario de asignación de módulos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['modulos'])) {
        $modulos_seleccionados = $_POST['modulos'];

        // Eliminar módulos anteriores del usuario
        $db->query("DELETE FROM usuario_modulos WHERE usuario_id = $usuario_id");

        // Insertar los nuevos módulos seleccionados
        foreach ($modulos_seleccionados as $modulo_id) {
            $modulo_id = intval($modulo_id); // Asegurar que el ID sea un entero
            $sql = "INSERT INTO usuario_modulos (usuario_id, modulo_id) VALUES ($usuario_id, $modulo_id)";
            $db->query($sql);
        }

        echo "<div class='alert alert-success'>Módulos asignados correctamente.</div>";
    } else {
        // Si no se seleccionaron módulos, eliminar todos los módulos asignados
        $db->query("DELETE FROM usuario_modulos WHERE usuario_id = $usuario_id");
        echo "<div class='alert alert-warning'>No se seleccionaron módulos. Todos los módulos han sido desasignados.</div>";
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <h1 class="mb-4">Asignar Módulos a Usuario</h1>

    <!-- Formulario para asignar módulos -->
    <form method="POST">
        <div class="form-group">
            <label>Seleccionar Módulos:</label>
            <?php while ($modulo = $modulos->fetch_assoc()): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="modulos[]" value="<?php echo $modulo['id']; ?>"
                        <?php echo in_array($modulo['id'], $modulos_asignados) ? 'checked' : ''; ?>>
                    <label class="form-check-label"><?php echo $modulo['nombre']; ?></label>
                </div>
            <?php endwhile; ?>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Guardar</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>