<?php
include 'db.php';

$db = new Database();

// Verificar si se envía el formulario para agregar o modificar datos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $tipo = $_POST['tipo'];
    $banco = ($tipo == 'transferencia') ? $_POST['banco'] : null;
    $cuenta = ($tipo == 'transferencia') ? $_POST['cuenta'] : null;

    if ($id) {
        // Actualizar datos existentes
        $sql = "UPDATE formas_pago SET 
                tipo = '$tipo', 
                banco = '$banco', 
                cuenta = '$cuenta' 
                WHERE id = $id";
    } else {
        // Insertar nuevos datos (sin especificar el id)
        $sql = "INSERT INTO formas_pago (tipo, banco, cuenta) 
                VALUES ('$tipo', '$banco', '$cuenta')";
    }

    $db->query($sql);

    // Redirigir para evitar reenvío del formulario
    header("Location: formas_pago.php");
    exit();
}

// Verificar si se solicita eliminar un registro
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $db->query("DELETE FROM formas_pago WHERE id = $id");

    // Redirigir para evitar reenvío del formulario
    header("Location: formas_pago.php");
    exit();
}

// Obtener los datos de las formas de pago
$formas_pago = $db->query("SELECT * FROM formas_pago");

// Obtener los datos de la forma de pago para editar
$forma_pago_editar = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $forma_pago_editar = $db->query("SELECT * FROM formas_pago WHERE id = $id")->fetch_assoc();
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5">
    <!-- Botón para volver al menú principal -->
    <a href="index.php" class="btn btn-secondary mb-3">Volver al Menú Principal</a>

    <h1 class="mb-4">Formas de Pago</h1>

    <!-- Formulario para agregar o modificar datos -->
    <form method="POST" class="mb-3">
        <input type="hidden" name="id" value="<?php echo $forma_pago_editar['id'] ?? ''; ?>">
        <div class="form-group">
            <label>Tipo de Pago</label>
            <select name="tipo" class="form-control" id="tipo-pago" required>
                <option value="efectivo" <?php echo ($forma_pago_editar['tipo'] ?? '') == 'efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                <option value="transferencia" <?php echo ($forma_pago_editar['tipo'] ?? '') == 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
            </select>
        </div>

        <div class="form-group" id="banco-group" style="display: <?php echo ($forma_pago_editar['tipo'] ?? '') == 'transferencia' ? 'block' : 'none'; ?>;">
            <label>Nombre del Banco</label>
            <input type="text" name="banco" class="form-control" value="<?php echo $forma_pago_editar['banco'] ?? ''; ?>">
        </div>

        <div class="form-group" id="cuenta-group" style="display: <?php echo ($forma_pago_editar['tipo'] ?? '') == 'transferencia' ? 'block' : 'none'; ?>;">
            <label>Número de Cuenta</label>
            <input type="text" name="cuenta" class="form-control" value="<?php echo $forma_pago_editar['cuenta'] ?? ''; ?>">
        </div>

        <button type="submit" class="btn btn-primary mt-3">Guardar</button>
    </form>

    <!-- Tabla para mostrar los datos de las formas de pago -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tipo</th>
                <th>Banco</th>
                <th>Cuenta</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($forma_pago = $formas_pago->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $forma_pago['id']; ?></td>
                    <td><?php echo $forma_pago['tipo']; ?></td>
                    <td><?php echo $forma_pago['banco']; ?></td>
                    <td><?php echo $forma_pago['cuenta']; ?></td>
                    <td>
                        <a href="?editar=<?php echo $forma_pago['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                        <a href="?eliminar=<?php echo $forma_pago['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar esta forma de pago?')">Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
    // Mostrar u ocultar campos de banco y cuenta según el tipo de pago
    document.getElementById('tipo-pago').addEventListener('change', function() {
        const tipo = this.value;
        const bancoGroup = document.getElementById('banco-group');
        const cuentaGroup = document.getElementById('cuenta-group');

        if (tipo === 'transferencia') {
            bancoGroup.style.display = 'block';
            cuentaGroup.style.display = 'block';
        } else {
            bancoGroup.style.display = 'none';
            cuentaGroup.style.display = 'none';
        }
    });
</script>

<?php include 'includes/footer.php'; ?>