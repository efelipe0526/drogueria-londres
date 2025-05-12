<?php
// Ajusta la ruta de db.php
include '../db.php';

$db = new Database();

// Agregar compra
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['proveedor_id'])) {
        $proveedor_id = $_POST['proveedor_id'];
        $valor = $_POST['valor'];
        $descripcion = $_POST['descripcion'];

        $sql = "INSERT INTO compras (proveedor_id, fecha_compra, valor, descripcion) 
                VALUES ($proveedor_id, NOW(), $valor, '$descripcion')";
        $db->query($sql);
    } elseif (isset($_POST['editar_id'])) {
        $id = $_POST['editar_id'];
        $proveedor_id = $_POST['editar_proveedor_id'];
        $valor = $_POST['editar_valor'];
        $descripcion = $_POST['editar_descripcion'];

        $sql = "UPDATE compras SET proveedor_id = $proveedor_id, valor = $valor, descripcion = '$descripcion' WHERE id = $id";
        $db->query($sql);
    }
}

// Eliminar compra
if (isset($_GET['eliminar_id'])) {
    $id = $_GET['eliminar_id'];
    $sql = "DELETE FROM compras WHERE id = $id";
    $db->query($sql);
}

// Obtener proveedores para el select
$proveedores = $db->query("SELECT * FROM proveedores");

// Configuración de paginación
$limit = 10; // Número de registros por página
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Obtener el término de búsqueda si existe
$search = $_GET['search'] ?? '';

// Ordenar por fecha (ascendente o descendente)
$order = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'DESC' : 'ASC';

// Consulta para obtener las compras con paginación, búsqueda y ordenación
$sql = "SELECT c.id, c.fecha_compra, c.valor, c.descripcion, p.nombre AS proveedor_nombre 
        FROM compras c 
        INNER JOIN proveedores p ON c.proveedor_id = p.id";

if ($search) {
    $sql .= " WHERE p.nombre LIKE '%$search%'";
}

$sql .= " ORDER BY c.fecha_compra $order LIMIT $limit OFFSET $offset";

$compras = $db->query($sql);

// Obtener el total de registros para la paginación
$total_sql = "SELECT COUNT(*) as total FROM compras c INNER JOIN proveedores p ON c.proveedor_id = p.id";
if ($search) {
    $total_sql .= " WHERE p.nombre LIKE '%$search%'";
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

    <h1 class="mb-4">Registro de Compras</h1>

    <!-- Formulario para agregar compra -->
    <form method="POST" class="mb-3">
        <div class="form-group">
            <label>Proveedor</label>
            <select name="proveedor_id" class="form-control" required>
                <option value="">-- Seleccione un proveedor --</option>
                <?php while ($row = $proveedores->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['nombre']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Valor de la Compra (COP)</label>
            <input type="number" name="valor" step="0.01" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Descripción del Suministro</label>
            <textarea name="descripcion" class="form-control"></textarea>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Registrar Compra</button>
    </form>

    <!-- Formulario de búsqueda y ordenación -->
    <form method="GET" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Buscar por nombre del proveedor" value="<?php echo $search; ?>">
            <button type="submit" class="btn btn-outline-secondary">Buscar</button>
        </div>
    </form>
    <div class="mb-3">
        <a href="?order=<?php echo $order == 'ASC' ? 'desc' : 'asc'; ?>&search=<?php echo $search; ?>" class="btn btn-outline-primary">
            Ordenar por Fecha <?php echo $order == 'ASC' ? 'Descendente' : 'Ascendente'; ?>
        </a>
    </div>

    <!-- Tabla de compras -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Valor (COP)</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $compras->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['fecha_compra']; ?></td>
                    <td><?php echo $row['proveedor_nombre']; ?></td>
                    <td><?php echo number_format($row['valor'], 2); ?></td>
                    <td><?php echo $row['descripcion']; ?></td>
                    <td>
                        <a href="?editar_id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                        <a href="?eliminar_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar esta compra?');">Eliminar</a>
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
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&order=<?php echo $order; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>

    <!-- Formulario para editar compra -->
    <?php if (isset($_GET['editar_id'])): ?>
        <?php
        $id = $_GET['editar_id'];
        $compra = $db->query("SELECT * FROM compras WHERE id = $id")->fetch_assoc();
        ?>
        <h2 class="mt-5">Editar Compra</h2>
        <form method="POST">
            <input type="hidden" name="editar_id" value="<?php echo $compra['id']; ?>">
            <div class="form-group">
                <label>Proveedor</label>
                <select name="editar_proveedor_id" class="form-control" required>
                    <option value="">-- Seleccione un proveedor --</option>
                    <?php while ($row = $proveedores->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>" <?php echo ($row['id'] == $compra['proveedor_id']) ? 'selected' : ''; ?>><?php echo $row['nombre']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Valor de la Compra (COP)</label>
                <input type="number" name="editar_valor" step="0.01" class="form-control" value="<?php echo $compra['valor']; ?>" required>
            </div>
            <div class="form-group">
                <label>Descripción del Suministro</label>
                <textarea name="editar_descripcion" class="form-control"><?php echo $compra['descripcion']; ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Guardar Cambios</button>
            <a href="compras.php" class="btn btn-secondary mt-3">Cancelar</a>
        </form>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>