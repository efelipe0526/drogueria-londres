<?php
include '../db.php'; // Incluye el archivo de conexión a la base de datos desde la raíz

$db = new Database();

// Verificar si se envía el formulario para agregar o modificar datos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $tipo = $_POST['tipo'];
    $nombre = $_POST['nombre'];
    $identificacion = $_POST['identificacion'];
    $correo = $_POST['correo'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];

    if ($id) {
        // Actualizar datos existentes
        $sql = "UPDATE clientes SET 
                tipo = '$tipo', 
                nombre = '$nombre', 
                identificacion = '$identificacion', 
                correo = '$correo', 
                direccion = '$direccion', 
                telefono = '$telefono' 
                WHERE id = $id";
    } else {
        // Insertar nuevos datos
        $sql = "INSERT INTO clientes (tipo, nombre, identificacion, correo, direccion, telefono) 
                VALUES ('$tipo', '$nombre', '$identificacion', '$correo', '$direccion', '$telefono')";
    }

    $db->query($sql);
}

// Verificar si se solicita eliminar un registro
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];

    // Mostrar un aviso de confirmación antes de eliminar
    echo "<script>
        if (confirm('¿Estás seguro de eliminar este cliente? Esto también eliminará todas las ventas relacionadas con este cliente.')) {
            window.location.href = 'clientes.php?confirmar_eliminar=$id';
        } else {
            window.location.href = 'clientes.php';
        }
    </script>";
}

// Verificar si se confirma la eliminación
if (isset($_GET['confirmar_eliminar'])) {
    $id = $_GET['confirmar_eliminar'];

    // Eliminar registros relacionados en la tabla ventas
    $db->query("DELETE FROM ventas WHERE cliente_id = $id");

    // Luego eliminar el cliente
    $db->query("DELETE FROM clientes WHERE id = $id");

    // Redirigir para evitar reenvío del formulario
    header("Location: clientes.php");
    exit;
}

// Obtener el término de búsqueda si existe
$search = $_GET['search'] ?? '';

// Configuración de paginación
$limit = 10; // Número de registros por página
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Consulta para obtener los datos de los clientes con paginación y búsqueda
$sql = "SELECT * FROM clientes";
if ($search) {
    $sql .= " WHERE nombre LIKE '%$search%' OR identificacion LIKE '%$search%'";
}
$sql .= " LIMIT $limit OFFSET $offset";

$clientes = $db->query($sql);

// Obtener el total de registros para la paginación
$total_sql = "SELECT COUNT(*) as total FROM clientes";
if ($search) {
    $total_sql .= " WHERE nombre LIKE '%$search%' OR identificacion LIKE '%$search%'";
}
$total_result = $db->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total = $total_row['total'];
$total_pages = ceil($total / $limit);

// Obtener los datos del cliente para editar
$cliente_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = $_GET['editar'];
    $cliente_editar = $db->query("SELECT * FROM clientes WHERE id = $id_editar")->fetch_assoc();
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <!-- Botón para volver al menú principal -->
    <a href="../index.php" class="btn btn-secondary mb-3">Volver al Menú Principal</a>

    <h1 class="mb-4">Gestión de Clientes</h1>

    <!-- Formulario para agregar o modificar datos -->
    <form method="POST" class="mb-3">
        <input type="hidden" name="id" value="<?php echo $cliente_editar['id'] ?? ''; ?>">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Tipo de Cliente</label>
                    <select name="tipo" class="form-control" required>
                        <option value="natural" <?php echo ($cliente_editar['tipo'] ?? '') == 'natural' ? 'selected' : ''; ?>>Persona Natural</option>
                        <option value="empresa" <?php echo ($cliente_editar['tipo'] ?? '') == 'empresa' ? 'selected' : ''; ?>>Empresa</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" class="form-control" value="<?php echo $cliente_editar['nombre'] ?? ''; ?>" required>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Identificación</label>
                    <input type="text" name="identificacion" class="form-control" value="<?php echo $cliente_editar['identificacion'] ?? ''; ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="correo" class="form-control" value="<?php echo $cliente_editar['correo'] ?? ''; ?>">
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="form-control" value="<?php echo $cliente_editar['direccion'] ?? ''; ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="<?php echo $cliente_editar['telefono'] ?? ''; ?>">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Guardar</button>
    </form>

    <!-- Formulario de búsqueda por nombre o cédula -->
    <form method="GET" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o cédula" value="<?php echo $search; ?>">
            <button type="submit" class="btn btn-outline-secondary">Buscar</button>
        </div>
    </form>

    <!-- Tabla para mostrar los datos de los clientes -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Nombre</th>
                <th>Identificación</th>
                <th>Correo</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($cliente = $clientes->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $cliente['tipo'] == 'natural' ? 'Persona Natural' : 'Empresa'; ?></td>
                    <td><?php echo $cliente['nombre']; ?></td>
                    <td><?php echo $cliente['identificacion']; ?></td>
                    <td><?php echo $cliente['correo']; ?></td>
                    <td><?php echo $cliente['direccion']; ?></td>
                    <td><?php echo $cliente['telefono']; ?></td>
                    <td>
                        <a href="?editar=<?php echo $cliente['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                        <a href="?eliminar=<?php echo $cliente['id']; ?>" class="btn btn-danger btn-sm">Eliminar</a>
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
</div>

<?php include '../includes/footer.php'; ?>