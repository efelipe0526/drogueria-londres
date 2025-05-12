<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

include './includes/header.php';

// Obtener los módulos asignados al usuario desde la sesión
$modulos_asignados = $_SESSION['modulos_asignados'] ?? [];

// Definir todos los módulos disponibles con sus nombres y rutas
$modulos_disponibles = [
    'Productos' => 'productos.php',
    'Ventas' => 'ventas.php',
    'Reportes' => 'reportes.php',
    'Impuestos' => 'impuestos.php',
    'Clientes' => 'clientes/clientes.php',
    'Formas de Pago' => 'formas_pago.php',
    'Proveedores' => 'proveedores/proveedores.php',
    'Contabilidad' => 'contabilidad/contabilidad.php',
    'Roles' => 'roles/modulo_roles.php',
    'Copia de Seguridad' => 'copia_seguridad.php'
];
?>

<div class="container mt-5">
    <!-- Botón para salir del sistema -->
    <div class="text-right mb-3">
        <a href="roles/logout.php" class="btn btn-danger">Salir del Sistema</a>
    </div>

    <h1 class="text-center mb-4">Sistema de Facturación y Ventas Drogueria Londres</h1>
    <div class="row">
        <?php
        // Mostrar solo los módulos asignados al usuario
        foreach ($modulos_asignados as $modulo_id) {
            // Obtener el nombre del módulo desde la base de datos
            include 'db.php';
            $db = new Database();
            $result = $db->query("SELECT nombre FROM modulos WHERE id = $modulo_id");
            if ($result->num_rows > 0) {
                $modulo = $result->fetch_assoc();
                $nombre_modulo = $modulo['nombre'];

                // Verificar si el módulo está en la lista de módulos disponibles
                if (array_key_exists($nombre_modulo, $modulos_disponibles)) {
                    $ruta_modulo = $modulos_disponibles[$nombre_modulo];

                    // Determinar si el módulo debe abrirse en una nueva pestaña
                    $abrir_en_nueva_ventana = ($nombre_modulo === 'Lista de Pedidos' || $nombre_modulo === 'Ventas');
        ?>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?php echo $nombre_modulo; ?></h5>
                                <a href="<?php echo $ruta_modulo; ?>" class="btn btn-primary" <?= $abrir_en_nueva_ventana ? 'target="_blank"' : '' ?>>Gestionar</a>
                            </div>
                        </div>
                    </div>
        <?php
                }
            }
        }
        ?>
    </div>
</div>

<?php include './includes/footer.php'; ?>