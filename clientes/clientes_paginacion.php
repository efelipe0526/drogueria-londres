<?php
// Incluir el archivo original de clientes.php
include 'clientes.php';

// Agregar la lógica de paginación y búsqueda
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$porPagina = 10; // Número de registros por página
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

// Modificar la consulta para incluir la búsqueda
$sql = "SELECT * FROM clientes";
if (!empty($busqueda)) {
    $sql .= " WHERE nombre LIKE '%$busqueda%'";
}
$sql .= " LIMIT " . ($pagina - 1) * $porPagina . ", $porPagina";

// Ejecutar la consulta modificada
$clientes = $db->query($sql);

// Obtener el total de registros para la paginación
$totalRegistros = $db->query("SELECT COUNT(*) as total FROM clientes" . (!empty($busqueda) ? " WHERE nombre LIKE '%$busqueda%'" : ""))->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $porPagina);
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <!-- Botón para volver al menú principal -->
    <a href="../index.php" class="btn btn-secondary mb-3">Volver al Menú Principal</a>

    <h1 class="mb-4">Gestión de Clientes</h1>

    <!-- Formulario de búsqueda -->
    <form method="GET" class="mb-3">
        <div class="input-group">
            <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre" value="<?php echo $busqueda; ?>">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>

    <!-- Formulario para agregar o modificar datos -->
    <form method="POST" class="mb-3">
        <input type="hidden" name="id" value="<?php echo $_GET['editar'] ?? ''; ?>">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Tipo de Cliente</label>
                    <select name="tipo" class="form-control" required>
                        <option value="natural">Persona Natural</option>
                        <option value="empresa">Empresa</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Identificación</label>
                    <input type="text" name="identificacion" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="correo" class="form-control">
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Guardar</button>
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
                        <a href="?eliminar=<?php echo $cliente['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este registro?')">Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Paginación -->
    <nav aria-label="Page navigation example">
        <ul class="pagination">
            <?php if ($pagina > 1): ?>
                <li class="page-item"><a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>&busqueda=<?php echo $busqueda; ?>">Anterior</a></li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <li class="page-item <?php echo ($pagina == $i) ? 'active' : ''; ?>"><a class="page-link" href="?pagina=<?php echo $i; ?>&busqueda=<?php echo $busqueda; ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>
            <?php if ($pagina < $totalPaginas): ?>
                <li class="page-item"><a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>&busqueda=<?php echo $busqueda; ?>">Siguiente</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<?php include '../includes/footer.php'; ?>