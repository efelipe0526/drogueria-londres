<?php
// Ajusta la ruta de db.php
include '../db.php';

$db = new Database();

// Obtener la fecha seleccionada (día y mes)
$fecha_filtro = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$mes_filtro = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');

// Procesar la solicitud para actualizar el número inicial de factura
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['numero_factura_inicial'])) {
    $numero_factura_inicial = $_POST['numero_factura_inicial'];

    // Asegurarse de que el número tenga 7 dígitos
    $numero_factura_inicial = str_pad($numero_factura_inicial, 7, '0', STR_PAD_LEFT);

    // Actualizar el número de factura inicial en la base de datos
    $sql = "UPDATE configuracion_impuestos SET numero_factura_inicial = '$numero_factura_inicial' WHERE id = 1";
    $db->query($sql);
}

// Obtener el número inicial de factura
$impuesto = $db->query("SELECT porcentaje, numero_factura_inicial FROM configuracion_impuestos WHERE id = 1")->fetch_assoc();
$numero_factura_inicial = $impuesto['numero_factura_inicial'] ?? '0000001';

// Obtener ingresos (suma de subtotales de las ventas) con filtro por fecha
$ingresos_query = $db->query("SELECT SUM(subtotal) AS total_ingresos FROM reportes WHERE DATE(fecha) = '$fecha_filtro' OR DATE_FORMAT(fecha, '%Y-%m') = '$mes_filtro'");
$ingresos = $ingresos_query->fetch_assoc()['total_ingresos'] ?? 0;

// Obtener impuestos por pagar (suma de impuestos de las ventas) con filtro por fecha
$impuestos_query = $db->query("SELECT SUM(impuesto) AS total_impuestos FROM reportes WHERE DATE(fecha) = '$fecha_filtro' OR DATE_FORMAT(fecha, '%Y-%m') = '$mes_filtro'");
$impuestos = $impuestos_query->fetch_assoc()['total_impuestos'] ?? 0;

// Obtener egresos (suma de los valores de las compras) con filtro por fecha
$egresos_query = $db->query("SELECT SUM(valor) AS total_egresos FROM compras WHERE DATE(fecha_compra) = '$fecha_filtro' OR DATE_FORMAT(fecha_compra, '%Y-%m') = '$mes_filtro'");
$egresos = $egresos_query->fetch_assoc()['total_egresos'] ?? 0;

// Calcular ganancias
$ganancias = $ingresos - $egresos;

// Obtener datos por día
$ingresos_dia_query = $db->query("SELECT SUM(subtotal) AS total_ingresos FROM reportes WHERE DATE(fecha) = '$fecha_filtro'");
$ingresos_dia = $ingresos_dia_query->fetch_assoc()['total_ingresos'] ?? 0;

$impuestos_dia_query = $db->query("SELECT SUM(impuesto) AS total_impuestos FROM reportes WHERE DATE(fecha) = '$fecha_filtro'");
$impuestos_dia = $impuestos_dia_query->fetch_assoc()['total_impuestos'] ?? 0;

$egresos_dia_query = $db->query("SELECT SUM(valor) AS total_egresos FROM compras WHERE DATE(fecha_compra) = '$fecha_filtro'");
$egresos_dia = $egresos_dia_query->fetch_assoc()['total_egresos'] ?? 0;

$ganancias_dia = $ingresos_dia - $egresos_dia;

// Obtener datos por mes
$ingresos_mes_query = $db->query("SELECT SUM(subtotal) AS total_ingresos FROM reportes WHERE DATE_FORMAT(fecha, '%Y-%m') = '$mes_filtro'");
$ingresos_mes = $ingresos_mes_query->fetch_assoc()['total_ingresos'] ?? 0;

$impuestos_mes_query = $db->query("SELECT SUM(impuesto) AS total_impuestos FROM reportes WHERE DATE_FORMAT(fecha, '%Y-%m') = '$mes_filtro'");
$impuestos_mes = $impuestos_mes_query->fetch_assoc()['total_impuestos'] ?? 0;

$egresos_mes_query = $db->query("SELECT SUM(valor) AS total_egresos FROM compras WHERE DATE_FORMAT(fecha_compra, '%Y-%m') = '$mes_filtro'");
$egresos_mes = $egresos_mes_query->fetch_assoc()['total_egresos'] ?? 0;

$ganancias_mes = $ingresos_mes - $egresos_mes;
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <!-- Botón para volver al menú principal -->
    <a href="../index.php" class="btn btn-secondary mb-3">Volver al Menú Principal</a>

    <h1 class="mb-4">Módulo de Contabilidad</h1>

    <!-- Formulario para establecer el número inicial de factura -->
    <form method="POST" class="mb-4">
        <div class="form-group">
            <label>Número Inicial de Factura</label>
            <input type="text" name="numero_factura_inicial" class="form-control" pattern="\d{7}" title="Debe ser un número de 7 dígitos" value="<?php echo str_pad($numero_factura_inicial, 7, '0', STR_PAD_LEFT); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>

    <!-- Filtros por fecha -->
    <form method="GET" action="contabilidad.php" class="mb-4">
        <div class="form-group">
            <label for="fecha">Filtrar por día:</label>
            <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo $fecha_filtro; ?>">
        </div>
        <div class="form-group">
            <label for="mes">Filtrar por mes:</label>
            <input type="month" class="form-control" id="mes" name="mes" value="<?php echo $mes_filtro; ?>">
        </div>
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </form>

    <!-- Resumen de contabilidad por día -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title">Resumen por Día (<?php echo $fecha_filtro; ?>)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Ingresos</h5>
                            <p class="card-text">Total de ingresos generados por ventas:</p>
                            <h3><?php echo number_format($ingresos_dia, 2); ?> COP</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Impuestos por Pagar</h5>
                            <p class="card-text">Total de impuestos generados por ventas:</p>
                            <h3><?php echo number_format($impuestos_dia, 2); ?> COP</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Egresos</h5>
                            <p class="card-text">Total de compras realizadas a proveedores:</p>
                            <h3><?php echo number_format($egresos_dia, 2); ?> COP</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Ganancias</h5>
                            <p class="card-text">Total de ganancias (Ingresos - Egresos):</p>
                            <h3><?php echo number_format($ganancias_dia, 2); ?> COP</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen de contabilidad por mes -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title">Resumen por Mes (<?php echo $mes_filtro; ?>)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Ingresos</h5>
                            <p class="card-text">Total de ingresos generados por ventas:</p>
                            <h3><?php echo number_format($ingresos_mes, 2); ?> COP</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Impuestos por Pagar</h5>
                            <p class="card-text">Total de impuestos generados por ventas:</p>
                            <h3><?php echo number_format($impuestos_mes, 2); ?> COP</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Egresos</h5>
                            <p class="card-text">Total de compras realizadas a proveedores:</p>
                            <h3><?php echo number_format($egresos_mes, 2); ?> COP</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Ganancias</h5>
                            <p class="card-text">Total de ganancias (Ingresos - Egresos):</p>
                            <h3><?php echo number_format($ganancias_mes, 2); ?> COP</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen de contabilidad acumulado -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Resumen Acumulado</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Ingresos</h5>
                            <p class="card-text">Total de ingresos generados por ventas:</p>
                            <h3><?php echo number_format($ingresos, 2); ?> COP</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Impuestos por Pagar</h5>
                            <p class="card-text">Total de impuestos generados por ventas:</p>
                            <h3><?php echo number_format($impuestos, 2); ?> COP</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Egresos</h5>
                            <p class="card-text">Total de compras realizadas a proveedores:</p>
                            <h3><?php echo number_format($egresos, 2); ?> COP</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Ganancias</h5>
                            <p class="card-text">Total de ganancias (Ingresos - Egresos):</p>
                            <h3><?php echo number_format($ganancias, 2); ?> COP</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>