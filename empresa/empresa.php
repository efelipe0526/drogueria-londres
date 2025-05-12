<?php
include '../db.php'; // Incluye el archivo de conexión a la base de datos desde la raíz

$db = new Database();

// Verificar si se envía el formulario para agregar o modificar datos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $razon_social = $_POST['razon_social'];
    $direccion = $_POST['direccion'];
    $resolucion = $_POST['resolucion'];
    $nit = $_POST['nit'];
    $dv = $_POST['dv'];
    $telefono = $_POST['telefono'];
    $correo_electronico = $_POST['correo_electronico'];

    if ($id) {
        // Actualizar datos existentes
        $sql = "UPDATE datos_empresa SET razon_social = '$razon_social', direccion = '$direccion', resolucion = '$resolucion', nit = '$nit', dv = '$dv', telefono = '$telefono', correo_electronico = '$correo_electronico' WHERE id = $id";
    } else {
        // Insertar nuevos datos
        $sql = "INSERT INTO datos_empresa (razon_social, direccion, resolucion, nit, dv, telefono, correo_electronico) VALUES ('$razon_social', '$direccion', '$resolucion', '$nit', '$dv', '$telefono', '$correo_electronico')";
    }

    $db->query($sql);
}

// Verificar si se solicita eliminar un registro
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $db->query("DELETE FROM datos_empresa WHERE id = $id");
}

// Obtener los datos de la empresa
$datos_empresa = $db->query("SELECT * FROM datos_empresa")->fetch_assoc();
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    
    <h1 class="mb-4">Gestión de Datos de la Empresa</h1>

    <!-- Formulario para agregar o modificar datos -->
    <form method="POST" class="mb-3">
        <input type="hidden" name="id" value="<?php echo $datos_empresa['id'] ?? ''; ?>">
        <div class="row">
            <div class="col-md-6">
                <!-- Botón para volver al menú principal -->
            <a href="../index.php" class="btn btn-secondary mb-3">Volver al Menú Principal</a>

                <div class="form-group">
                    <label>Razón Social</label>
                    <input type="text" name="razon_social" class="form-control" value="<?php echo $datos_empresa['razon_social'] ?? ''; ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="form-control" value="<?php echo $datos_empresa['direccion'] ?? ''; ?>" required>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Resolución</label>
                    <input type="text" name="resolucion" class="form-control" value="<?php echo $datos_empresa['resolucion'] ?? ''; ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>NIT</label>
                    <input type="text" name="nit" class="form-control" value="<?php echo $datos_empresa['nit'] ?? ''; ?>" required>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label>DV</label>
                    <input type="text" name="dv" class="form-control" value="<?php echo $datos_empresa['dv'] ?? ''; ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="<?php echo $datos_empresa['telefono'] ?? ''; ?>">
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="correo_electronico" class="form-control" value="<?php echo $datos_empresa['correo_electronico'] ?? ''; ?>">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Guardar</button>
    </form>

    <!-- Tabla para mostrar los datos de la empresa -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Razón Social</th>
                <th>Dirección</th>
                <th>Resolución</th>
                <th>NIT</th>
                <th>DV</th>
                <th>Teléfono</th>
                <th>Correo Electrónico</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($datos_empresa): ?>
                <tr>
                    <td><?php echo $datos_empresa['razon_social']; ?></td>
                    <td><?php echo $datos_empresa['direccion']; ?></td>
                    <td><?php echo $datos_empresa['resolucion']; ?></td>
                    <td><?php echo $datos_empresa['nit']; ?></td>
                    <td><?php echo $datos_empresa['dv']; ?></td>
                    <td><?php echo $datos_empresa['telefono']; ?></td>
                    <td><?php echo $datos_empresa['correo_electronico']; ?></td>
                    <td>
                        <a href="?eliminar=<?php echo $datos_empresa['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este registro?')">Eliminar</a>
                    </td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No hay datos de la empresa registrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>