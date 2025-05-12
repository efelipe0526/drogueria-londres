<?php
include 'db.php';
$db = new Database();

// Procesar nueva categoría
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_categoria'])) {
    $nombre = $db->getConnection()->real_escape_string($_POST['nombre_categoria']);
    if ($db->query("SELECT COUNT(*) as total FROM categorias WHERE nombre = '$nombre'")->fetch_assoc()['total'] > 0) {
        echo "<div class='alert alert-warning'>Categoría ya existe</div>";
    } else {
        $db->query("INSERT INTO categorias (nombre) VALUES ('$nombre')");
        header("Location: productos.php");
        exit();
    }
}

// Eliminar categoría
if (isset($_GET['eliminar_categoria'])) {
    $id = (int)$_GET['eliminar_categoria'];
    if ($db->query("SELECT COUNT(*) as total FROM productos WHERE categoria_id = $id")->fetch_assoc()['total'] > 0) {
        echo "<div class='alert alert-danger'>Categoría tiene productos asociados</div>";
    } else {
        $db->query("DELETE FROM categorias WHERE id = $id");
        header("Location: productos.php");
        exit();
    }
}

// Procesar nuevo laboratorio
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_laboratorio'])) {
    $nombre = $db->getConnection()->real_escape_string($_POST['nombre_laboratorio']);
    if ($db->query("SELECT COUNT(*) as total FROM laboratorios WHERE nombre = '$nombre'")->fetch_assoc()['total'] > 0) {
        echo "<div class='alert alert-warning'>Laboratorio ya existe</div>";
    } else {
        $db->query("INSERT INTO laboratorios (nombre) VALUES ('$nombre')");
        header("Location: productos.php");
        exit();
    }
}

// Eliminar laboratorio
if (isset($_GET['eliminar_laboratorio'])) {
    $id = (int)$_GET['eliminar_laboratorio'];
    if ($db->query("SELECT COUNT(*) as total FROM producto_laboratorio WHERE laboratorio_id = $id")->fetch_assoc()['total'] > 0) {
        echo "<div class='alert alert-danger'>Laboratorio tiene productos asociados</div>";
    } else {
        $db->query("DELETE FROM laboratorios WHERE id = $id");
        header("Location: productos.php");
        exit();
    }
}

// Procesar productos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'nombre' => $db->getConnection()->real_escape_string($_POST['nombre']),
        'descripcion' => $db->getConnection()->real_escape_string($_POST['descripcion']),
        'precio' => (float)$_POST['precio'],
        'stock' => 0, // Stock se calculará como la suma de los stocks por laboratorio
        'categoria_id' => (int)$_POST['categoria_id'],
        'precio_por_unidad' => (float)$_POST['precio_por_unidad'],
        'fecha_vencimiento' => $db->getConnection()->real_escape_string($_POST['fecha_vencimiento']),
        'codigo_barras' => $db->getConnection()->real_escape_string($_POST['codigo_barras'] ?? '')
    ];

    // Datos de laboratorios
    $laboratorios = isset($_POST['laboratorios']) ? $_POST['laboratorios'] : [];
    $stocks = isset($_POST['stocks']) ? $_POST['stocks'] : [];
    $unidades = isset($_POST['unidades']) ? $_POST['unidades'] : [];

    if ($id > 0) {
        // Actualizar producto existente
        $sql = "UPDATE productos SET 
                nombre = '{$data['nombre']}',
                descripcion = '{$data['descripcion']}',
                precio = {$data['precio']},
                stock = {$data['stock']},
                categoria_id = {$data['categoria_id']},
                precio_por_unidad = {$data['precio_por_unidad']},
                fecha_vencimiento = '{$data['fecha_vencimiento']}',
                codigo_barras = '{$data['codigo_barras']}' 
                WHERE id = $id";
        $db->query($sql);

        // Actualizar datos de laboratorios
        foreach ($laboratorios as $index => $laboratorio_id) {
            $laboratorio_id = (int)$laboratorio_id;
            $stock = (int)($stocks[$index] ?? 0);
            $unidad_original = (int)($unidades[$index] ?? 0);

            // Verificar si ya existe una entrada para este producto y laboratorio
            $exists = $db->query("SELECT id FROM producto_laboratorio WHERE producto_id = $id AND laboratorio_id = $laboratorio_id");
            if ($exists->num_rows > 0) {
                // Actualizar entrada existente
                $db->query("UPDATE producto_laboratorio 
                            SET stock = $stock, unidad_original = $unidad_original, unidad = $unidad_original 
                            WHERE producto_id = $id AND laboratorio_id = $laboratorio_id");
            } else {
                // Insertar nueva entrada
                $db->query("INSERT INTO producto_laboratorio (producto_id, laboratorio_id, stock, unidad_original, unidad) 
                            VALUES ($id, $laboratorio_id, $stock, $unidad_original, $unidad_original)");
            }
        }

        // Actualizar el stock general
        $db->query("UPDATE productos p 
                    SET stock = (SELECT COALESCE(SUM(stock), 0) FROM producto_laboratorio pl WHERE pl.producto_id = p.id) 
                    WHERE id = $id");
    } else {
        // Insertar nuevo producto
        $sql = "INSERT INTO productos 
                (nombre, descripcion, precio, stock, categoria_id, precio_por_unidad, fecha_vencimiento, codigo_barras)
                VALUES 
                ('{$data['nombre']}', '{$data['descripcion']}', {$data['precio']}, {$data['stock']}, 
                {$data['categoria_id']}, {$data['precio_por_unidad']}, '{$data['fecha_vencimiento']}', '{$data['codigo_barras']}')";
        $db->query($sql);
        $id = $db->getConnection()->insert_id;

        // Insertar datos de laboratorios
        foreach ($laboratorios as $index => $laboratorio_id) {
            $laboratorio_id = (int)$laboratorio_id;
            $stock = (int)($stocks[$index] ?? 0);
            $unidad_original = (int)($unidades[$index] ?? 0);

            if ($stock > 0 && $laboratorio_id > 0) {
                $db->query("INSERT INTO producto_laboratorio (producto_id, laboratorio_id, stock, unidad_original, unidad) 
                            VALUES ($id, $laboratorio_id, $stock, $unidad_original, $unidad_original)");
            }
        }

        // Actualizar el stock general
        $db->query("UPDATE productos p 
                    SET stock = (SELECT COALESCE(SUM(stock), 0) FROM producto_laboratorio pl WHERE pl.producto_id = p.id) 
                    WHERE id = $id");
    }
    header("Location: productos.php?refresh=1");
    exit();
}

// Eliminar producto
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    // Las entradas en producto_laboratorio se eliminarán automáticamente por la restricción ON DELETE CASCADE
    $db->query("DELETE FROM productos WHERE id = $id");
    header("Location: productos.php?refresh=1");
    exit();
}

// Búsqueda y paginación
$search = $db->getConnection()->real_escape_string($_GET['search'] ?? '');
$categoria_buscar = $db->getConnection()->real_escape_string($_GET['categoria_buscar'] ?? '');
$pagina = (int)($_GET['pagina'] ?? 1);
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Calcular el total de productos para la paginación
$total_query = $db->query("SELECT COUNT(*) as total 
                           FROM productos p 
                           LEFT JOIN categorias c ON p.categoria_id = c.id 
                           WHERE (p.nombre LIKE '%$search%' OR p.codigo_barras LIKE '%$search%') 
                           AND (c.nombre LIKE '%$categoria_buscar%' OR '$categoria_buscar' = '')");
$total_resultados = $total_query->fetch_assoc()['total'];

// Forzar recarga para asegurar datos actualizados
if (isset($_GET['refresh'])) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
}

$productos = $db->query("SELECT p.*, c.nombre as categoria 
    FROM productos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id 
    WHERE (p.nombre LIKE '%$search%' OR p.codigo_barras LIKE '%$search%') 
    AND (c.nombre LIKE '%$categoria_buscar%' OR '$categoria_buscar' = '') 
    ORDER BY p.nombre 
    LIMIT $offset, $por_pagina");

// Productos a vencer en un mes
$productos_vencer = [];
if (isset($_GET['mostrar_vencer'])) {
    $fecha_actual = date('Y-m-d');
    $fecha_un_mes = date('Y-m-d', strtotime('+1 month'));
    $productos_vencer = $db->query("SELECT p.*, c.nombre as categoria 
                                    FROM productos p 
                                    LEFT JOIN categorias c ON p.categoria_id = c.id 
                                    WHERE fecha_vencimiento BETWEEN '$fecha_actual' AND '$fecha_un_mes' 
                                    ORDER BY fecha_vencimiento ASC");
}

// Productos más vendidos
$top_vendidos_dia = [];
$top_vendidos_mes = [];
$top_vendidos_anio = [];
if (isset($_GET['mostrar_vendidos'])) {
    $fecha_actual = date('Y-m-d');
    $fecha_mes = date('Y-m-01');
    $fecha_anio = date('Y-01-01');

    $top_vendidos_dia = $db->query("SELECT p.nombre, SUM(dv.cantidad) as total_vendido 
                                    FROM productos p 
                                    LEFT JOIN detalles_venta dv ON p.id = dv.producto_id 
                                    LEFT JOIN ventas v ON dv.venta_id = v.id 
                                    WHERE DATE(v.fecha) = '$fecha_actual' 
                                    GROUP BY p.id, p.nombre 
                                    ORDER BY total_vendido DESC 
                                    LIMIT 5");
    $top_vendidos_mes = $db->query("SELECT p.nombre, SUM(dv.cantidad) as total_vendido 
                                    FROM productos p 
                                    LEFT JOIN detalles_venta dv ON p.id = dv.producto_id 
                                    LEFT JOIN ventas v ON dv.venta_id = v.id 
                                    WHERE DATE(v.fecha) >= '$fecha_mes' 
                                    GROUP BY p.id, p.nombre 
                                    ORDER BY total_vendido DESC 
                                    LIMIT 5");
    $top_vendidos_anio = $db->query("SELECT p.nombre, SUM(dv.cantidad) as total_vendido 
                                    FROM productos p 
                                    LEFT JOIN detalles_venta dv ON p.id = dv.producto_id 
                                    LEFT JOIN ventas v ON dv.venta_id = v.id 
                                    WHERE DATE(v.fecha) >= '$fecha_anio' 
                                    GROUP BY p.id, p.nombre 
                                    ORDER BY total_vendido DESC 
                                    LIMIT 5");
}

$categorias = $db->query("SELECT * FROM categorias");
$laboratorios = $db->query("SELECT * FROM laboratorios");
$producto_editar = isset($_GET['editar']) ? $db->query("SELECT * FROM productos WHERE id = " . (int)$_GET['editar'])->fetch_assoc() : null;

// Obtener datos de laboratorios para el producto editado
$producto_laboratorios = [];
if ($producto_editar) {
    $result = $db->query("SELECT pl.*, l.nombre as laboratorio_nombre 
                          FROM producto_laboratorio pl 
                          JOIN laboratorios l ON pl.laboratorio_id = l.id 
                          WHERE pl.producto_id = " . (int)$producto_editar['id']);
    while ($row = $result->fetch_assoc()) {
        $producto_laboratorios[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <h1 class="mb-4">Gestión de Productos</h1>

    <!-- Botones para reportes -->
    <div class="mb-4">
        <button id="btn-vencer" class="btn btn-info me-2">Mostrar Productos a Vencer en 1 Mes</button>
        <button id="btn-vendidos" class="btn btn-info">Mostrar Productos Más Vendidos</button>
    </div>

    <!-- Formulario Categoría -->
    <form method="POST" class="mb-4 border p-3 rounded">
        <h5>Agregar Categoría</h5>
        <div class="row g-3">
            <div class="col-md-8">
                <input type="text" name="nombre_categoria" class="form-control" placeholder="Nombre de categoría" required>
            </div>
            <div class="col-md-4">
                <button name="agregar_categoria" class="btn btn-success w-100">Agregar</button>
            </div>
        </div>
    </form>

    <!-- Formulario Laboratorio -->
    <form method="POST" class="mb-4 border p-3 rounded">
        <h5>Agregar Laboratorio</h5>
        <div class="row g-3">
            <div class="col-md-8">
                <input type="text" name="nombre_laboratorio" class="form-control" placeholder="Nombre de laboratorio" required>
            </div>
            <div class="col-md-4">
                <button name="agregar_laboratorio" class="btn btn-success w-100">Agregar</button>
            </div>
        </div>
    </form>

    <!-- Lista de Laboratorios -->
    <div class="mb-4 border p-3 rounded">
        <h5>Lista de Laboratorios</h5>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $laboratorios->data_seek(0);
                    while ($lab = $laboratorios->fetch_assoc()): ?>
                        <tr>
                            <td><?= $lab['id'] ?></td>
                            <td><?= $lab['nombre'] ?></td>
                            <td>
                                <a href="?eliminar_laboratorio=<?= $lab['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar laboratorio?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Búsqueda -->
    <form method="GET" class="mb-4 border p-3 rounded">
        <h5>Búsqueda</h5>
        <div class="row g-3">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Nombre o Código de Barras" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-5">
                <select name="categoria_buscar" class="form-control">
                    <option value="">Todas las categorías</option>
                    <?php while ($c = $categorias->fetch_assoc()): ?>
                        <option value="<?= $c['nombre'] ?>" <?= $c['nombre'] == $categoria_buscar ? 'selected' : '' ?>>
                            <?= $c['nombre'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
        </div>
    </form>

    <!-- Formulario Producto -->
    <form method="POST" id="producto-form" class="mb-4 border p-3 rounded">
        <h5><?= isset($producto_editar) ? 'Editar' : 'Nuevo' ?> Producto</h5>
        <input type="hidden" name="id" value="<?= $producto_editar['id'] ?? '' ?>">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" name="nombre" class="form-control" placeholder="Nombre" value="<?= $producto_editar['nombre'] ?? '' ?>" required>
            </div>
            <div class="col-md-8">
                <textarea name="descripcion" class="form-control" placeholder="Descripción"><?= $producto_editar['descripcion'] ?? '' ?></textarea>
            </div>
            <div class="col-md-2">
                <input type="number" name="precio" step="0.01" class="form-control" placeholder="Precio" value="<?= $producto_editar['precio'] ?? '' ?>" required>
            </div>
            <div class="col-md-2">
                <input type="number" name="precio_por_unidad" step="0.01" class="form-control" placeholder="Precio/Unidad" value="<?= $producto_editar['precio_por_unidad'] ?? '' ?>" required>
            </div>
            <div class="col-md-2">
                <input type="date" name="fecha_vencimiento" class="form-control" value="<?= $producto_editar['fecha_vencimiento'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <select name="categoria_id" class="form-control" required>
                    <option value="">Categoría</option>
                    <?php
                    $categorias->data_seek(0); // Resetear el puntero para reutilizar el resultado
                    while ($c = $categorias->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= ($producto_editar['categoria_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= $c['nombre'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="codigo_barras" id="barcode-input" class="form-control" placeholder="Escanear Código de Barras" value="<?= $producto_editar['codigo_barras'] ?? '' ?>" autofocus>
                <svg id="barcode-preview" style="margin-top: 5px;"></svg>
            </div>
            <!-- Laboratorios -->
            <div class="col-md-12">
                <h6>Laboratorios y Stock</h6>
                <div id="laboratorios-container">
                    <?php if ($producto_laboratorios): ?>
                        <?php foreach ($producto_laboratorios as $index => $pl): ?>
                            <div class="row g-3 mb-2 laboratorio-row">
                                <div class="col-md-4">
                                    <select name="laboratorios[]" class="form-control" required>
                                        <option value="">Seleccionar Laboratorio</option>
                                        <?php
                                        $laboratorios->data_seek(0);
                                        while ($lab = $laboratorios->fetch_assoc()): ?>
                                            <option value="<?= $lab['id'] ?>" <?= $pl['laboratorio_id'] == $lab['id'] ? 'selected' : '' ?>>
                                                <?= $lab['nombre'] ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="stocks[]" class="form-control" placeholder="Stock" value="<?= $pl['stock'] ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="unidades[]" class="form-control" placeholder="Unidades por Caja" value="<?= $pl['unidad_original'] ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger w-100 remove-laboratorio">Eliminar</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="row g-3 mb-2 laboratorio-row">
                            <div class="col-md-4">
                                <select name="laboratorios[]" class="form-control" required>
                                    <option value="">Seleccionar Laboratorio</option>
                                    <?php
                                    $laboratorios->data_seek(0);
                                    while ($lab = $laboratorios->fetch_assoc()): ?>
                                        <option value="<?= $lab['id'] ?>"><?= $lab['nombre'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="stocks[]" class="form-control" placeholder="Stock" value="0" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="unidades[]" class="form-control" placeholder="Unidades por Caja" value="0" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger w-100 remove-laboratorio">Eliminar</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" id="add-laboratorio" class="btn btn-secondary w-100 mt-2">Agregar Laboratorio</button>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary w-100"><?= isset($producto_editar) ? 'Actualizar' : 'Guardar' ?></button>
                <button type="button" id="start-over" class="btn btn-secondary w-100 mt-2">Inicio</button>
            </div>
        </div>
    </form>

    <!-- Lista de Productos -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Stock Total</th>
                    <th>Stock por Laboratorio</th>
                    <th>Precio/Unidad</th>
                    <th>Vencimiento</th>
                    <th>Categoría</th>
                    <th>Código de Barras</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($p = $productos->fetch_assoc()): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= $p['nombre'] ?></td>
                        <td><?= $p['descripcion'] ?></td>
                        <td>$<?= number_format($p['precio'], 2) ?></td>
                        <td><?= $p['stock'] ?></td>
                        <td>
                            <?php
                            $lab_stock = $db->query("SELECT pl.stock, pl.unidad_original, pl.unidad, l.nombre as laboratorio_nombre 
                                                     FROM producto_laboratorio pl 
                                                     JOIN laboratorios l ON pl.laboratorio_id = l.id 
                                                     WHERE pl.producto_id = " . (int)$p['id']);
                            while ($ls = $lab_stock->fetch_assoc()) {
                                if ($ls['stock'] <= 0) {
                                    echo htmlspecialchars($ls['laboratorio_nombre']) . ": <span class='text-danger'>0 unidades restantes</span><br>";
                                } else {
                                    echo htmlspecialchars($ls['laboratorio_nombre']) . ": " . $ls['stock'] . " cajas (" . $ls['unidad_original'] . " unidades/caja, " . $ls['unidad'] . " unidades restantes)<br>";
                                }
                            }
                            ?>
                        </td>
                        <td>$<?= number_format($p['precio_por_unidad'], 2) ?></td>
                        <td><?= $p['fecha_vencimiento'] ? date('d/m/Y', strtotime($p['fecha_vencimiento'])) : 'N/A' ?></td>
                        <td><?= $p['categoria'] ?></td>
                        <td><?= $p['codigo_barras'] ?? 'N/A' ?></td>
                        <td>
                            <a href="?editar=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="?eliminar=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar producto?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <nav>
        <ul class="pagination justify-content-center">
            <?php if ($pagina > 1): ?>
                <li class="page-item"><a class="page-link" href="?pagina=<?= $pagina - 1 ?>&search=<?= $search ?>&categoria_buscar=<?= $categoria_buscar ?>">Anterior</a></li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= ceil($total_resultados / $por_pagina); $i++): ?>
                <li class="page-item <?= $i == $pagina ? 'active' : '' ?>"><a class="page-link" href="?pagina=<?= $i ?>&search=<?= $search ?>&categoria_buscar=<?= $categoria_buscar ?>"><?= $i ?></a></li>
            <?php endfor; ?>

            <?php if ($pagina < ceil($total_resultados / $por_pagina)): ?>
                <li class="page-item"><a class="page-link" href="?pagina=<?= $pagina + 1 ?>&search=<?= $search ?>&categoria_buscar=<?= $categoria_buscar ?>">Siguiente</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Sección de Productos a Vencer -->
    <div id="productos-vencer-section">
        <?php if (!empty($productos_vencer) && $productos_vencer->num_rows > 0): ?>
            <div class="mt-4">
                <h4>Productos a Vencer en 1 Mes</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Fecha de Vencimiento</th>
                                <th>Categoría</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($pv = $productos_vencer->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $pv['id'] ?></td>
                                    <td><?= $pv['nombre'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($pv['fecha_vencimiento'])) ?></td>
                                    <td><?= $pv['categoria'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sección de Productos Más Vendidos -->
    <div id="top-vendidos-section">
        <?php if (!empty($top_vendidos_dia) && $top_vendidos_dia->num_rows > 0): ?>
            <div class="mt-4">
                <h4>Top 5 Productos Más Vendidos - Día (<?= date('d/m/Y') ?>)</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Cantidad Vendida</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($tv = $top_vendidos_dia->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $tv['nombre'] ?></td>
                                    <td><?= $tv['total_vendido'] ?? 0 ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($top_vendidos_mes) && $top_vendidos_mes->num_rows > 0): ?>
            <div class="mt-4">
                <h4>Top 5 Productos Más Vendidos - Mes (<?= date('m/Y') ?>)</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Cantidad Vendida</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($tv = $top_vendidos_mes->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $tv['nombre'] ?></td>
                                    <td><?= $tv['total_vendido'] ?? 0 ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($top_vendidos_anio) && $top_vendidos_anio->num_rows > 0): ?>
            <div class="mt-4">
                <h4>Top 5 Productos Más Vendidos - Año (<?= date('Y') ?>)</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Cantidad Vendida</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($tv = $top_vendidos_anio->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $tv['nombre'] ?></td>
                                    <td><?= $tv['total_vendido'] ?? 0 ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Dependencias y Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
    document.getElementById('add-laboratorio').addEventListener('click', function() {
        const container = document.getElementById('laboratorios-container');
        const row = document.createElement('div');
        row.className = 'row g-3 mb-2 laboratorio-row';
        row.innerHTML = `
        <div class="col-md-4">
            <select name="laboratorios[]" class="form-control" required>
                <option value="">Seleccionar Laboratorio</option>
                <?php
                $laboratorios->data_seek(0);
                while ($lab = $laboratorios->fetch_assoc()): ?>
                    <option value="<?= $lab['id'] ?>"><?= $lab['nombre'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" name="stocks[]" class="form-control" placeholder="Stock" value="0" required>
        </div>
        <div class="col-md-3">
            <input type="number" name="unidades[]" class="form-control" placeholder="Unidades por Caja" value="0" required>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger w-100 remove-laboratorio">Eliminar</button>
        </div>
    `;
        container.appendChild(row);
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-laboratorio')) {
            e.target.closest('.laboratorio-row').remove();
        }
    });

    // Manejar clics en botones de reportes
    $('#btn-vencer').on('click', function() {
        window.location.href = '?mostrar_vencer=1';
    });

    $('#btn-vendidos').on('click', function() {
        window.location.href = '?mostrar_vendidos=1';
    });

    // Manejar escaneo de código de barras
    const barcodeInput = document.getElementById('barcode-input');
    const barcodePreview = document.getElementById('barcode-preview');
    barcodeInput.focus(); // Enfocar el campo al cargar para escaneo inmediato

    barcodeInput.addEventListener('input', function() {
        const code = barcodeInput.value.trim();
        if (code) {
            JsBarcode(barcodePreview, code, {
                format: "CODE128",
                displayValue: true,
                width: 2,
                height: 40
            });
        } else {
            barcodePreview.innerHTML = ''; // Limpiar si no hay código
        }
    });

    // Generar código de barras al cargar si hay valor
    document.addEventListener('DOMContentLoaded', function() {
        const initialCode = barcodeInput.value.trim();
        if (initialCode) {
            JsBarcode(barcodePreview, initialCode, {
                format: "CODE128",
                displayValue: true,
                width: 2,
                height: 40
            });
        }
    });

    // Limpiar formulario y secciones de reportes al hacer clic en "Start Over"
    document.getElementById('start-over').addEventListener('click', function() {
        const form = document.getElementById('producto-form');
        // Resetear todos los campos del formulario
        form.reset();
        // Limpiar el código de barras
        barcodePreview.innerHTML = '';
        // Restaurar el valor del campo hidden "id" si existe
        form.querySelector('input[name="id"]').value = '<?= $producto_editar['id'] ?? '' ?>';
        // Limpiar las secciones de reportes
        document.getElementById('productos-vencer-section').innerHTML = '';
        document.getElementById('top-vendidos-section').innerHTML = '';
    });
</script>

<?php include 'includes/footer.php'; ?>