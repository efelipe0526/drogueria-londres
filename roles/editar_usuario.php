<?php
include '../db.php';

$db = new Database();

$usuario_id = $_GET['id'] ?? null;

// Obtener la información del usuario
$usuario = null;
if ($usuario_id) {
    $result = $db->query("SELECT * FROM usuarios WHERE id = $usuario_id");
    $usuario = $result->fetch_assoc();
}

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $usuario['password'];
    $rol_id = $_POST['rol_id'];

    $db->query("UPDATE usuarios SET username = '$username', password = '$password', rol_id = $rol_id WHERE id = $usuario_id");
    echo "<div class='alert alert-success'>Usuario actualizado correctamente.</div>";
}

// Obtener roles
$roles = $db->query("SELECT * FROM roles");
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <h1 class="mb-4">Editar Usuario</h1>

    <form method="POST">
        <div class="form-group">
            <label>Nombre de Usuario:</label>
            <input type="text" name="username" class="form-control" value="<?php echo $usuario['username']; ?>" required>
        </div>
        <div class="form-group">
            <label>Contraseña (dejar en blanco para no cambiar):</label>
            <input type="password" name="password" class="form-control">
        </div>
        <div class="form-group">
            <label>Rol:</label>
            <select name="rol_id" class="form-control" required>
                <?php while ($row = $roles->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>" <?php echo $row['id'] == $usuario['rol_id'] ? 'selected' : ''; ?>><?php echo $row['nombre']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>