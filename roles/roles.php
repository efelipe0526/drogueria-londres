<?php
include '../db.php';
include 'includes/auth.php'; // Ruta para auth.php dentro de la carpeta includes dentro de roles

$db = new Database();

// Lista de módulos permitidos (excluyendo explícitamente los eliminados)
$modulos_permitidos = [
    'productos' => 'Productos',
    'clientes' => 'Clientes',
    'roles' => 'Roles',
    'ventas' => 'Ventas',
    'reportes' => 'Reportes',
    'impuestos' => 'Impuestos',
    'proveedores' => 'Proveedores',
    'contabilidad' => 'Contabilidad',
    'formas_de_pago' => 'Formas de Pago',
    'copia_seguridad' => 'Copia de Seguridad'
];

// --- Agregar rol ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre'])) {
    $nombre = trim($_POST['nombre']);
    if (!empty($nombre)) {
        $db->preparedQuery("INSERT INTO roles (nombre) VALUES (?)", [$nombre]);
        header("Location: roles.php");
        exit();
    } else {
        die("El nombre del rol no puede estar vacío.");
    }
}

// --- Editar rol ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_id'])) {
    $id = $_POST['editar_id'];
    $nombre = trim($_POST['nombre_editar']);
    if (!empty($nombre)) {
        $db->preparedQuery("UPDATE roles SET nombre = ? WHERE id = ?", [$nombre, $id]);
        header("Location: roles.php");
        exit();
    } else {
        die("El nombre del rol no puede estar vacío.");
    }
}

// --- Eliminar rol ---
if (isset($_GET['eliminar_id'])) {
    $id = $_GET['eliminar_id'];
    $db->preparedQuery("DELETE FROM roles WHERE id = ?", [$id]);
    header("Location: roles.php");
    exit();
}

// Obtener roles
$roles = $db->query("SELECT * FROM roles");
$rol_editar = null;

// Obtener rol para editar
if (isset($_GET['editar_id'])) {
    $id = $_GET['editar_id'];
    $result = $db->preparedQuery("SELECT * FROM roles WHERE id = ?", [$id]);
    $rol_editar = $result->fetch_assoc();
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <a href="modulo_roles.php" class="btn btn-secondary mb-3">Volver al Menú</a>
    <h1>Gestión de Roles</h1>

    <!-- Formulario único para agregar/editar -->
    <form method="POST">
        <input type="hidden" name="editar_id" value="<?= $rol_editar['id'] ?? '' ?>">
        <div class="form-group">
            <label>Nombre del Rol</label>
            <input type="text"
                name="<?= isset($rol_editar) ? 'nombre_editar' : 'nombre' ?>"
                class="form-control"
                value="<?= $rol_editar['nombre'] ?? '' ?>"
                required>
        </div>
        <button type="submit" class="btn btn-primary">
            <?= isset($rol_editar) ? 'Guardar Cambios' : 'Agregar Rol' ?>
        </button>
        <?php if (isset($rol_editar)): ?>
            <a href="roles.php" class="btn btn-secondary">Cancelar</a>
        <?php endif; ?>
    </form>

    <!-- Tabla de roles -->
    <table class="table table-striped mt-4">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $roles->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['nombre'] ?></td>
                    <td>
                        <a href="?editar_id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                        <a href="?eliminar_id=<?= $row['id'] ?>"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('¿Eliminar este rol?');">
                            Eliminar
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Formulario para asignar módulos -->
    <h2 class="mt-5">Asignar Módulos al Rol</h2>
    <?php
    if (isset($_POST['asignar_modulos']) && isset($_POST['rol_id']) && isset($_POST['modulos'])) {
        $rol_id = $_POST['rol_id'];
        $modulos_seleccionados = $_POST['modulos'];

        // Eliminar módulos anteriores del rol
        $db->preparedQuery("DELETE FROM rol_modulos WHERE rol_id = ?", [$rol_id]);

        // Insertar los nuevos módulos seleccionados
        foreach ($modulos_seleccionados as $modulo_id) {
            $modulo_id = intval($modulo_id);
            if (isset($modulos_permitidos[array_search($modulo_id, array_keys($modulos_permitidos))])) {
                $db->preparedQuery("INSERT INTO rol_modulos (rol_id, modulo_id) VALUES (?, ?)", [$rol_id, $modulo_id]);
            }
        }
        echo "<div class='alert alert-success'>Módulos asignados correctamente.</div>";
    }

    // Obtener roles para el formulario de asignación
    $roles_list = $db->query("SELECT * FROM roles");
    ?>
    <form method="POST" class="mt-3">
        <div class="form-group">
            <label>Seleccionar Rol:</label>
            <select name="rol_id" class="form-control" required>
                <?php while ($rol = $roles_list->fetch_assoc()): ?>
                    <option value="<?= $rol['id'] ?>"><?= $rol['nombre'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Seleccionar Módulos:</label>
            <?php
            $modulos_db = $db->query("SELECT * FROM modulos");
            while ($modulo = $modulos_db->fetch_assoc()):
                // Excluir explícitamente los módulos no deseados
                $modulo_nombre_lower = strtolower(str_replace(' ', '_', $modulo['nombre']));
                if (in_array($modulo_nombre_lower, ['comision_de_meseros', 'gestion_de_mesas', 'lista_de_pedidos'])) {
                    continue; // Saltar estos módulos
                }
                if (array_key_exists($modulo_nombre_lower, $modulos_permitidos)):
            ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="modulos[]" value="<?= $modulo['id'] ?>">
                        <label class="form-check-label"><?= $modulo['nombre'] ?></label>
                    </div>
            <?php endif;
            endwhile; ?>
        </div>
        <button type="submit" name="asignar_modulos" class="btn btn-primary">Asignar Módulos</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>