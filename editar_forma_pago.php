<?php
include 'db.php';

$db = new Database();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $forma_pago = $db->query("SELECT * FROM formas_pago WHERE id = $id")->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $tipo = $_POST['tipo'];
    $banco = ($tipo == 'transferencia') ? $_POST['banco'] : null;
    $cuenta = ($tipo == 'transferencia') ? $_POST['cuenta'] : null;

    $sql = "UPDATE formas_pago SET tipo = '$tipo', banco = '$banco', cuenta = '$cuenta' WHERE id = $id";
    $db->query($sql);

    header("Location: formas_pago.php");
    exit();
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5">
    <h1 class="mb-4">Editar Forma de Pago</h1>

    <form method="POST">
        <input type="hidden" name="id" value="<?php echo $forma_pago['id']; ?>">
        <div class="form-group">
            <label>Tipo de Pago</label>
            <select name="tipo" class="form-control" id="tipo-pago" required>
                <option value="efectivo" <?php echo ($forma_pago['tipo'] == 'efectivo') ? 'selected' : ''; ?>>Efectivo</option>
                <option value="transferencia" <?php echo ($forma_pago['tipo'] == 'transferencia') ? 'selected' : ''; ?>>Transferencia</option>
            </select>
        </div>

        <div class="form-group" id="banco-group" style="display: <?php echo ($forma_pago['tipo'] == 'transferencia') ? 'block' : 'none'; ?>;">
            <label>Nombre del Banco</label>
            <input type="text" name="banco" class="form-control" value="<?php echo $forma_pago['banco']; ?>">
        </div>

        <div class="form-group" id="cuenta-group" style="display: <?php echo ($forma_pago['tipo'] == 'transferencia') ? 'block' : 'none'; ?>;">
            <label>Número de Cuenta</label>
            <input type="text" name="cuenta" class="form-control" value="<?php echo $forma_pago['cuenta']; ?>">
        </div>

        <button type="submit" class="btn btn-primary mt-3">Guardar</button>
    </form>
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