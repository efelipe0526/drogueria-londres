<?php
include 'db.php';

$db = new Database();
$conexion = $db->getConnection();

if (!$conexion) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Configuración de paginación
$registros_por_pagina = 10; // Número de registros por página
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1; // Página actual
$offset = ($pagina - 1) * $registros_por_pagina; // Cálculo del offset

// Obtener el número de factura para buscar
$numero_factura_buscar = isset($_GET['numero_factura_buscar']) ? $_GET['numero_factura_buscar'] : '';

// Obtener la fecha del día para filtrar
$fecha_dia = isset($_GET['fecha_dia']) ? $_GET['fecha_dia'] : '';

// Obtener el mes y año para filtrar
$fecha_mes = isset($_GET['fecha_mes']) ? $_GET['fecha_mes'] : '';

// Construir la condición de fecha para el día
$condicion_fecha_dia = '';
if (!empty($fecha_dia)) {
    $condicion_fecha_dia = "AND DATE(r.fecha) = '$fecha_dia'";
}

// Construir la condición de fecha para el mes
$condicion_fecha_mes = '';
if (!empty($fecha_mes)) {
    $fecha_mes = date('Y-m', strtotime($fecha_mes)); // Formato YYYY-MM
    $condicion_fecha_mes = "AND DATE_FORMAT(r.fecha, '%Y-%m') = '$fecha_mes'";
}

// Consulta para obtener el total de registros (para la paginación)
$query_total = "SELECT COUNT(*) as total 
                FROM reportes r
                LEFT JOIN formas_pago f ON r.forma_pago_id = f.id
                WHERE NOT (r.monto_efectivo = 0 AND f.tipo = 'transferencia')
                AND r.numero_factura LIKE '%$numero_factura_buscar%'
                $condicion_fecha_dia
                $condicion_fecha_mes";
$result_total = $conexion->query($query_total);
$total_registros = $result_total->fetch_assoc()['total']; // Total de registros
$total_paginas = ceil($total_registros / $registros_por_pagina); // Total de páginas

// Consulta para obtener los datos de los reportes con paginación, búsqueda y filtros de fecha
$query = "SELECT r.numero_factura, r.total_con_impuesto, 
                 CASE 
                     WHEN r.monto_efectivo > 0 THEN r.monto_efectivo
                     ELSE r.total_con_impuesto
                 END AS monto_efectivo
          FROM reportes r
          LEFT JOIN formas_pago f ON r.forma_pago_id = f.id
          WHERE NOT (r.monto_efectivo = 0 AND f.tipo = 'transferencia')
          AND r.numero_factura LIKE '%$numero_factura_buscar%'
          $condicion_fecha_dia
          $condicion_fecha_mes
          ORDER BY r.fecha DESC
          LIMIT $offset, $registros_por_pagina";
$result = $conexion->query($query);

if (!$result) {
    die("Error en la consulta: " . $conexion->error);
}

$reportes = [];

while ($row = $result->fetch_assoc()) {
    // Asegurarse de que los valores sean números
    $row['total_con_impuesto'] = (float)$row['total_con_impuesto'];
    $row['monto_efectivo'] = (float)$row['monto_efectivo'];

    $reportes[] = $row;
}

// Consulta para obtener el acumulado de los montos en efectivo de los reportes filtrados
$query_acumulado = "SELECT SUM(
                        CASE 
                            WHEN r.monto_efectivo > 0 THEN r.monto_efectivo
                            ELSE r.total_con_impuesto
                        END
                    ) as total_acumulado 
                    FROM reportes r
                    LEFT JOIN formas_pago f ON r.forma_pago_id = f.id
                    WHERE NOT (r.monto_efectivo = 0 AND f.tipo = 'transferencia')
                    AND r.numero_factura LIKE '%$numero_factura_buscar%'
                    $condicion_fecha_dia
                    $condicion_fecha_mes";
$result_acumulado = $conexion->query($query_acumulado);
$total_acumulado = $result_acumulado->fetch_assoc()['total_acumulado'] ?? 0;

// Consulta para obtener el total por mes de los reportes filtrados
$query_total_mes = "SELECT SUM(
                        CASE 
                            WHEN r.monto_efectivo > 0 THEN r.monto_efectivo
                            ELSE r.total_con_impuesto
                        END
                    ) as total_mes 
                    FROM reportes r
                    LEFT JOIN formas_pago f ON r.forma_pago_id = f.id
                    WHERE NOT (r.monto_efectivo = 0 AND f.tipo = 'transferencia')
                    AND r.numero_factura LIKE '%$numero_factura_buscar%'
                    $condicion_fecha_mes";
$result_total_mes = $conexion->query($query_total_mes);
$total_mes = $result_total_mes->fetch_assoc()['total_mes'] ?? 0;

// Consulta para obtener el total por día de los reportes filtrados
$query_total_dia = "SELECT SUM(
                        CASE 
                            WHEN r.monto_efectivo > 0 THEN r.monto_efectivo
                            ELSE r.total_con_impuesto
                        END
                    ) as total_dia 
                    FROM reportes r
                    LEFT JOIN formas_pago f ON r.forma_pago_id = f.id
                    WHERE NOT (r.monto_efectivo = 0 AND f.tipo = 'transferencia')
                    AND r.numero_factura LIKE '%$numero_factura_buscar%'
                    $condicion_fecha_dia";
$result_total_dia = $conexion->query($query_total_dia);
$total_dia = $result_total_dia->fetch_assoc()['total_dia'] ?? 0;

// Cerrar conexión
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Pagos</title>
    <!-- Bootstrap CSS -->
    <link href="boot/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
        }
        .table {
            margin-top: 20px;
        }
        .table thead {
            background-color: #007bff;
            color: white;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        h1 {
            color: #007bff;
            text-align: center;
            margin-bottom: 30px;
        }
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        .filtros {
            margin-bottom: 20px;
        }
        .filtros .form-control {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reporte de Pagos</h1>
        <a href="reportes.php" class="btn btn-secondary mb-3">Volver a Reportes</a>

        <!-- Formulario de búsqueda y filtros -->
        <form method="GET" class="mb-3">
            <div class="row filtros">
                <div class="col-md-3">
                    <input type="text" name="numero_factura_buscar" class="form-control" placeholder="Buscar por número de factura" value="<?php echo htmlspecialchars($numero_factura_buscar); ?>">
                </div>
                <div class="col-md-3">
                    <input type="date" name="fecha_dia" class="form-control" value="<?php echo htmlspecialchars($fecha_dia); ?>">
                </div>
                <div class="col-md-3">
                    <input type="month" name="fecha_mes" class="form-control" value="<?php echo htmlspecialchars($fecha_mes); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </div>
        </form>

        <!-- Botón para limpiar el formulario -->
        <div class="mb-3">
            <a href="reportes_efectivo.php" class="btn btn-warning">Limpiar Filtros</a>
        </div>

        <!-- Tabla dinámica -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Número de Factura</th>
                    <th>Valor Total con Impuesto</th>
                    <th>Monto en Efectivo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reportes)): ?>
                    <tr>
                        <td colspan="3" class="text-center">No hay pagos registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reportes as $reporte): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reporte['numero_factura']); ?></td>
                            <td><?php echo number_format($reporte['total_con_impuesto'], 2); ?></td>
                            <td><?php echo number_format($reporte['monto_efectivo'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <nav aria-label="Paginación">
            <ul class="pagination">
                <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?= $pagina - 1; ?>&numero_factura_buscar=<?= $numero_factura_buscar; ?>&fecha_dia=<?= $fecha_dia; ?>&fecha_mes=<?= $fecha_mes; ?>" aria-label="Anterior">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?= ($pagina == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?pagina=<?= $i; ?>&numero_factura_buscar=<?= $numero_factura_buscar; ?>&fecha_dia=<?= $fecha_dia; ?>&fecha_mes=<?= $fecha_mes; ?>"><?= $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?= $pagina + 1; ?>&numero_factura_buscar=<?= $numero_factura_buscar; ?>&fecha_dia=<?= $fecha_dia; ?>&fecha_mes=<?= $fecha_mes; ?>" aria-label="Siguiente">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Totales -->
        <div class="mt-3">
            <strong>Total por Día:</strong> <?php echo number_format($total_dia, 2); ?><br>
            <strong>Total por Mes:</strong> <?php echo number_format($total_mes, 2); ?><br>
            <strong>Total Acumulado:</strong> <?php echo number_format($total_acumulado, 2); ?>
        </div>
    </div>

    <!-- Bootstrap JS y dependencias -->
    <script src="boot/bootstrap.bundle.min.js"></script>
</body>
</html>