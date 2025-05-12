<?php
// Ajusta la ruta de db.php
include '../db.php';

$db = new Database();

// Agregar proveedor
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tipo'])) {
        $tipo = $_POST['tipo'];
        $nombre = $_POST['nombre'];
        $identificacion = $_POST['identificacion'];
        $direccion = $_POST['direccion'];
        $correo = $_POST['correo'];
        $telefono = $_POST['telefono'];

        $sql = "INSERT INTO proveedores (tipo, nombre, identificacion, direccion, correo, telefono) 
                VALUES ('$tipo', '$nombre', '$identificacion', '$direccion', '$correo', '$telefono')";
        $db->query($sql);
    } elseif (isset($_POST['editar_id'])) {
        $id = $_POST['editar_id'];
        $tipo = $_POST['editar_tipo'];
        $nombre = $_POST['editar_nombre'];
        $identificacion = $_POST['editar_identificacion'];
        $direccion = $_POST['editar_direccion'];
        $correo = $_POST['editar_correo'];
        $telefono = $_POST['editar_telefono'];

        $sql = "UPDATE proveedores SET tipo = '$tipo', nombre = '$nombre', identificacion = '$identificacion', direccion = '$direccion', correo = '$correo', telefono = '$telefono' WHERE id = $id";
        $db->query($sql);
    }
}

// Eliminar proveedor
if (isset($_GET['eliminar_id'])) {
    $id = $_GET['eliminar_id'];
    $sql = "DELETE FROM proveedores WHERE id = $id";
    $db->query($sql);
}

// Configuración de paginación
$limit = 10; // Número de registros por página
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Obtener el término de búsqueda si existe
$search = $_GET['search'] ?? '';

// Consulta para obtener los proveedores con paginación y búsqueda
$sql = "SELECT * FROM proveedores";
if ($search) {
    $sql .= " WHERE nombre LIKE '%$search%'";
}
$sql .= " LIMIT $limit OFFSET $offset";

$proveedores = $db->query($sql);

// Obtener el total de registros para la paginación
$total_sql = "SELECT COUNT(*) as total FROM proveedores";
if ($search) {
    $total_sql .= " WHERE nombre LIKE '%$search%'";
}
$total_result = $db->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total = $total_row['total'];
$total_pages = ceil($total / $limit);
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <!-- Botón para volver al menú principal -->
    <a href="../index.php" class="btn btn-secondary mb-3">Volver al Menú Principal</a>

    <h1 class="mb-4">Gestión de Proveedores</h1>

    <!-- Formulario para agregar proveedor -->
    <form method="POST" class="mb-3">
        <div class="form-group">
            <label>Tipo de Proveedor</label>
            <select name="tipo" class="form-control" required>
                <option value="natural">Persona Natural</option>
                <option value="empresa">Empresa</option>
            </select>
        </div>
        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Identificación (Cédula o NIT)</label>
            <input type="text" name="identificacion" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Dirección</label>
            <input type="text" name="direccion" class="form-control">
        </div>
        <div class="form-group">
            <label>Correo Electrónico</label>
            <input type="email" name="correo" class="form-control">
        </div>
        <div class="form-group">
            <label>Teléfono</label>
            <input type="text" name="telefono" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary mt-3">Guardar</button>
    </form>

    <!-- Formulario de búsqueda -->
    <form method="GET" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Buscar por nombre" value="<?php echo $search; ?>">
            <button type="submit" class="btn btn-outline-secondary">Buscar</button>
        </div>
    </form>

    <!-- Tabla de proveedores -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tipo</th>
                <th>Nombre</th>
                <th>Identificación</th>
                <th>Dirección</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $proveedores->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['tipo']; ?></td>
                    <td><?php echo $row['nombre']; ?></td>
                    <td><?php echo $row['identificacion']; ?></td>
                    <td><?php echo $row['direccion']; ?></td>
                    <td><?php echo $row['correo']; ?></td>
                    <td><?php echo $row['telefono']; ?></td>
                    <td>
                        <a href="?editar_id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                        <a href="?eliminar_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este proveedor?');">Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Paginación -->
    <nav aria-label="Page navigation">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>

    <!-- Formulario para editar proveedor -->
    <?php if (isset($_GET['editar_id'])): ?>
        <?php
        $id = $_GET['editar_id'];
        $proveedor = $db->query("SELECT * FROM proveedores WHERE id = $id")->fetch_assoc();
        ?>
        <h2 class="mt-5">Editar Proveedor</h2>
        <form method="POST">
            <input type="hidden" name="editar_id" value="<?php echo $proveedor['id']; ?>">
            <div class="form-group">
                <label>Tipo de Proveedor</label>
                <select name="editar_tipo" class="form-control" required>
                    <option value="natural" <?php echo ($proveedor['tipo'] == 'natural') ? 'selected' : ''; ?>>Persona Natural</option>
                    <option value="empresa" <?php echo ($proveedor['tipo'] == 'empresa') ? 'selected' : ''; ?>>Empresa</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="editar_nombre" class="form-control" value="<?php echo $proveedor['nombre']; ?>" required>
            </div>
            <div class="form-group">
                <label>Identificación (Cédula o NIT)</label>
                <input type="text" name="editar_identificacion" class="form-control" value="<?php echo $proveedor['identificacion']; ?>" required>
            </div>
            <div class="form-group">
                <label>Dirección</label>
                <input type="text" name="editar_direccion" class="form-control" value="<?php echo $proveedor['direccion']; ?>">
            </div>
            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="editar_correo" class="form-control" value="<?php echo $proveedor['correo']; ?>">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="editar_telefono" class="form-control" value="<?php echo $proveedor['telefono']; ?>">
            </div>
            <button type="submit" class="btn btn-primary mt-3">Guardar Cambios</button>
            <a href="proveedores.php" class="btn btn-secondary mt-3">Cancelar</a>
        </form>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>