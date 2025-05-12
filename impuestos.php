<?php
include 'db.php';

$db = new Database();

// Verifica si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $porcentaje = $_POST['porcentaje'];
    $aplica_impuesto = isset($_POST['aplica_impuesto']) ? 1 : 0;

    // Actualiza el porcentaje y la comisión en la base de datos
    $sql = "UPDATE configuracion_impuestos SET 
            porcentaje = $porcentaje, 
            aplica_impuesto = $aplica_impuesto 
            WHERE id = 1";
    $db->query($sql);
}

// Obtiene el porcentaje actual de la base de datos
$impuesto = $db->query("SELECT porcentaje, aplica_impuesto FROM configuracion_impuestos WHERE id = 1")->fetch_assoc();
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5">
    <!-- Botón para volver al menú principal -->
    <a href="index.php" class="btn btn-secondary mb-3">Volver al Menú Principal</a>
    <h1 class="mb-4">Configuración de Impuestos</h1>
    <form method="POST">
        <div class="form-group">
            <label>Porcentaje de Impuesto</label>
            <select name="porcentaje" class="form-control" required>
                <?php
                // Genera las opciones del 1% al 20%
                for ($i = 1; $i <= 20; $i++) {
                    $selected = ($impuesto['porcentaje'] == $i) ? 'selected' : '';
                    echo "<option value='$i' $selected>$i%</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="aplica_impuesto" id="aplica_impuesto" <?php echo $impuesto['aplica_impuesto'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="aplica_impuesto">Aplicar Impuesto</label>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Guardar</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>