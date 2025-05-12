<?php
include '../db.php'; // Ruta corregida para apuntar a la raíz del proyecto

$db = new Database();

// Agregar usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    $username = $_POST['username'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    $rol_id = $_POST['rol_id'];

    // Verificar si el nombre de usuario ya existe
    $sql_check = "SELECT id FROM usuarios WHERE username = '$username'";
    $result = $db->query($sql_check);

    if ($result->num_rows > 0) {
        echo "<div class='alert alert-danger'>El nombre de usuario ya está en uso. Por favor, elige otro.</div>";
    } else {
        // Insertar el nuevo usuario
        $sql = "INSERT INTO usuarios (username, password, rol_id) VALUES ('$username', '$password', $rol_id)";
        $db->query($sql);
        echo "<div class='alert alert-success'>Usuario agregado correctamente.</div>";
    }
}

// Eliminar usuario
if (isset($_GET['eliminar'])) {
    $usuario_id = $_GET['eliminar'];

    // Eliminar registros relacionados en usuario_modulos
    $db->query("DELETE FROM usuario_modulos WHERE usuario_id = $usuario_id");

    // Eliminar el usuario
    $db->query("DELETE FROM usuarios WHERE id = $usuario_id");

    header("Location: usuarios.php"); // Recargar la página
    exit();
}

// Obtener usuarios y roles
$usuarios = $db->query("SELECT u.id, u.username, r.nombre AS rol FROM usuarios u JOIN roles r ON u.rol_id = r.id");
$roles = $db->query("SELECT * FROM roles");
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <!-- Enlace para volver al menú principal -->
    <a href="modulo_roles.php" class="btn btn-secondary mb-3">Volver al Menú Principal</a>
    <h1 class="mb-4">Gestión de Usuarios</h1>

    <!-- Formulario para agregar usuario -->
    <form method="POST" class="mb-3">
        <div class="form-group">
            <label>Nombre de Usuario</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Contraseña (dejar en blanco para no asignar)</label>
            <input type="password" name="password" class="form-control">
        </div>
        <div class="form-group">
            <label>Rol</label>
            <select name="rol_id" class="form-control" required>
                <?php while ($row = $roles->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['nombre']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Agregar Usuario</button>
    </form>

    <!-- Tabla de usuarios -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre de Usuario</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $usuarios->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['username']; ?></td>
                    <td><?php echo $row['rol']; ?></td>
                    <td>
                        <!-- Botón para asignar módulos -->
                        <a href="asignar_modulos.php?usuario_id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">Asignar Módulos</a>
                        <!-- Botón para editar -->
                        <a href="editar_usuario.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                        <!-- Botón para eliminar -->
                        <a href="usuarios.php?eliminar=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este usuario?');">Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>