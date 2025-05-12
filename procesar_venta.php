<?php
// Iniciar el buffer de salida
ob_start();

// Habilitar errores para depuración (quitar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

require_once('tcpdf/tcpdf.php'); // Incluir la biblioteca TCPDF

// Conexión a la base de datos
$mysqli = new mysqli("localhost", "root", "", "marci");

// Verificar conexión
if ($mysqli->connect_error) {
    error_log("Error de conexión: " . $mysqli->connect_error);
    die("Error de conexión a la base de datos.");
}

// Obtener el porcentaje de impuesto desde la base de datos
$impuesto_query = $mysqli->query("SELECT porcentaje, numero_factura_inicial FROM configuracion_impuestos WHERE id = 1");
if ($impuesto_query && $impuesto_query->num_rows > 0) {
    $impuesto_data = $impuesto_query->fetch_assoc();
    $impuesto_porcentaje = $impuesto_data['porcentaje'] / 100; // Convertir a decimal
    $numero_factura = sprintf("%07d", $impuesto_data['numero_factura_inicial'] ?? 10); // Formato 0000010
} else {
    error_log("Error: No se encontró la configuración de impuestos.");
    die("Error: No se encontró la configuración de impuestos.");
}

// Procesar datos de la factura
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar campos obligatorios
    if (!isset($_POST['numero_factura'], $_POST['total'], $_POST['cliente_id'], $_POST['productos'], $_POST['forma_pago'])) {
        error_log("Error: Campos obligatorios faltantes. POST: " . print_r($_POST, true));
        die("Error: Los campos 'numero_factura', 'total', 'cliente_id', 'productos' y 'forma_pago' son obligatorios.");
    }

    $numero_factura = $_POST['numero_factura'];
    $total = floatval($_POST['total']);
    $cliente_id = intval($_POST['cliente_id']);
    $productos = json_decode($_POST['productos'], true);
    $forma_pago = intval($_POST['forma_pago']);
    $banco = $_POST['banco'] ?? null;
    $cuenta = !empty($_POST['cuenta']) ? $_POST['cuenta'] : null;
    $aplicar_impuesto = isset($_POST['aplicar_impuesto']) && $_POST['aplicar_impuesto'] == 1 ? 1 : 0;
    $dividir_pago = isset($_POST['dividir_pago']) && $_POST['dividir_pago'] == 1 ? 1 : 0;
    $monto_efectivo = floatval($_POST['monto_efectivo'] ?? 0);
    $monto_otra_forma_pago = floatval($_POST['monto_otra_forma_pago'] ?? 0);

    // Validar cliente_id
    if ($cliente_id <= 0) {
        error_log("Error: cliente_id inválido ($cliente_id). POST: " . print_r($_POST, true));
        die("Error: El ID del cliente es inválido.");
    }
    $stmt = $mysqli->prepare("SELECT id FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        error_log("Error: Cliente con ID $cliente_id no encontrado. POST: " . print_r($_POST, true));
        die("Error: El cliente especificado no existe.");
    }
    $stmt->close();

    // Validar productos
    if (!is_array($productos) || empty($productos)) {
        error_log("Error: Productos inválidos o vacíos. POST: " . print_r($_POST, true));
        die("Error: Los productos son inválidos o están vacíos.");
    }

    // Verificar si el número de factura ya existe
    $stmt = $mysqli->prepare("SELECT id FROM ventas WHERE numero_factura = ? UNION SELECT id FROM reportes WHERE numero_factura = ?");
    $stmt->bind_param("ss", $numero_factura, $numero_factura);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        ob_end_clean();
        echo "<!DOCTYPE html><html><head>";
        echo "<script src='alerta/sweetalert2.all.min.js'></script>";
        echo "</head><body>";
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El número de factura ya existe. Por favor, genere una nueva factura.',
                confirmButtonText: 'Aceptar'
            }).then(() => {
                window.location.href = 'ventas.php';
            });
        </script>";
        echo "</body></html>";
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Calcular subtotal y validar precios contra la base de datos
    $subtotal = 0;
    foreach ($productos as $producto) {
        if (
            !isset($producto['cantidad'], $producto['usar_unidad'], $producto['id'], $producto['laboratorio_id']) ||
            ($producto['usar_unidad'] && !isset($producto['precio_por_unidad'])) ||
            (!$producto['usar_unidad'] && !isset($producto['precio_venta']))
        ) {
            error_log("Error: Producto inválido: " . print_r($producto, true));
            die("Error: Datos de producto incompletos. Falta laboratorio_id u otros campos requeridos.");
        }
        $cantidad = floatval($producto['cantidad']);
        $producto_id = intval($producto['id']);
        $laboratorio_id = intval($producto['laboratorio_id']);

        // Validar precio contra la base de datos
        $stmt = $mysqli->prepare("SELECT precio, precio_por_unidad FROM productos WHERE id = ?");
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            error_log("Error: Producto ID {$producto_id} no encontrado en la base de datos.");
            die("Error: Producto ID {$producto_id} no encontrado.");
        }
        $db_producto = $result->fetch_assoc();
        $stmt->close();

        if ($producto['usar_unidad']) {
            $precio_por_unidad = floatval($producto['precio_por_unidad']);
            if (abs($precio_por_unidad - $db_producto['precio_por_unidad']) > 0.01) {
                error_log("Error: Precio por unidad inválido para producto ID {$producto_id}: recibido $precio_por_unidad, esperado {$db_producto['precio_por_unidad']}");
                die("Error: Precio por unidad inválido para producto ID {$producto_id}.");
            }
            $subtotal += $cantidad * $precio_por_unidad;
        } else {
            $precio_venta = floatval($producto['precio_venta']);
            if (abs($precio_venta - $db_producto['precio']) > 0.01) {
                error_log("Error: Precio de venta inválido para producto ID {$producto_id}: recibido $precio_venta, esperado {$db_producto['precio']}");
                die("Error: Precio de venta inválido para producto ID {$producto_id}.");
            }
            $subtotal += $cantidad * $precio_venta;
        }

        // Advertencia para precios altos (solo para monitoreo, no detiene la operación)
        if (($producto['usar_unidad'] && $precio_por_unidad > 15000) || (!$producto['usar_unidad'] && $precio_venta > 15000)) {
            error_log("Advertencia: Precio alto para producto ID {$producto_id} (usar_unidad={$producto['usar_unidad']}, precio_por_unidad=" . ($producto['precio_por_unidad'] ?? 'N/A') . ", precio_venta=" . ($producto['precio_venta'] ?? 'N/A') . "): " . print_r($producto, true));
        }
    }

    // Calcular impuesto
    $impuesto = $aplicar_impuesto ? $subtotal * $impuesto_porcentaje : 0;
    $total_con_impuesto = $subtotal + $impuesto;

    // Validar que $_POST['total'] coincida con total_con_impuesto
    if (abs($total - $total_con_impuesto) > 0.01) {
        error_log("Error: Total enviado ($total) no coincide con el calculado ($total_con_impuesto). Productos: " . print_r($productos, true));
        die("Error: El total enviado no coincide con el total calculado.");
    }

    // Validar subtotal y total razonables
    if ($subtotal <= 0 || $total_con_impuesto <= 0) {
        error_log("Error: Subtotal ($subtotal) o total_con_impuesto ($total_con_impuesto) inválidos.");
        die("Error: El subtotal o total calculado es inválido.");
    }

    // Debug: Log variables before inserting
    error_log("Venta: numero_factura=$numero_factura, subtotal=$subtotal, impuesto=$impuesto, total_con_impuesto=$total_con_impuesto, cliente_id=$cliente_id, forma_pago=$forma_pago, banco=" . ($banco ?? 'NULL') . ", cuenta=" . ($cuenta ?? 'NULL') . ", monto_efectivo=$monto_efectivo, monto_otra_forma_pago=$monto_otra_forma_pago");
    error_log("POST total: $total, Calculated total_con_impuesto: $total_con_impuesto");
    error_log("POST data: " . print_r($_POST, true));

    // Iniciar transacción para asegurar consistencia
    $mysqli->begin_transaction();

    try {
        // Insertar datos en la tabla ventas
        $stmt = $mysqli->prepare("INSERT INTO ventas (fecha, numero_factura, total, impuesto, total_con_impuesto, cliente_id, forma_pago_id, cuenta, monto_efectivo, monto_otra_forma_pago) 
                                 VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdddiisdd", $numero_factura, $subtotal, $impuesto, $total_con_impuesto, $cliente_id, $forma_pago, $cuenta, $monto_efectivo, $monto_otra_forma_pago);
        if (!$stmt->execute()) {
            throw new Exception("Error al insertar en ventas: " . $stmt->error);
        }
        $venta_id = $stmt->insert_id;
        $stmt->close();

        // Procesar detalles de la venta y actualizar unidad/stock
        $stmt = $mysqli->prepare("INSERT INTO detalles_venta (venta_id, producto_id, cantidad, unidad, precio_unitario, precio_venta) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($productos as $producto) {
            $producto_id = intval($producto['id']);
            $laboratorio_id = intval($producto['laboratorio_id']);
            $cantidad = floatval($producto['cantidad']);
            $unidad = !empty($producto['unidad']) ? $producto['unidad'] : '';
            $precio_unitario = $producto['usar_unidad'] ? floatval($producto['precio_por_unidad']) : 0.0;
            $precio_venta = !$producto['usar_unidad'] ? floatval($producto['precio_venta']) : 0.0;
            $usar_unidad = isset($producto['usar_unidad']) && ($producto['usar_unidad'] === true || $producto['usWithdrawUnidad'] === 'true' || $producto['usar_unidad'] === 1 || $producto['usar_unidad'] === '1');

            // Debug: Log detalle values
            error_log("Detalle: venta_id=$venta_id, producto_id=$producto_id, cantidad=$cantidad, unidad='$unidad', precio_unitario=$precio_unitario, precio_venta=$precio_venta, usar_unidad=" . ($usar_unidad ? 'true' : 'false'));

            $stmt->bind_param("iidsdd", $venta_id, $producto_id, $cantidad, $unidad, $precio_unitario, $precio_venta);
            if (!$stmt->execute()) {
                throw new Exception("Error al insertar detalle de venta: " . $stmt->error);
            }

            // Actualizar unidad y stock solo si usas unidad
            if ($usar_unidad) {
                error_log("Procesando actualización de unidad para producto_id=$producto_id, laboratorio_id=$laboratorio_id");

                // Obtener datos actuales con bloqueo
                $stmt_unidad = $mysqli->prepare("SELECT unidad, unidad_original, stock FROM producto_laboratorio WHERE producto_id = ? AND laboratorio_id = ? FOR UPDATE");
                $stmt_unidad->bind_param("ii", $producto_id, $laboratorio_id);
                $stmt_unidad->execute();
                $result = $stmt_unidad->get_result();
                $producto_laboratorio = $result->fetch_assoc();
                $stmt_unidad->close();

                if (!$producto_laboratorio) {
                    throw new Exception("Producto ID {$producto_id} con laboratorio ID {$laboratorio_id} no encontrado al actualizar unidad/stock.");
                }

                $unidad_actual = intval($producto_laboratorio['unidad']);
                $unidad_original = intval($producto_laboratorio['unidad_original']);
                $stock_actual = intval($producto_laboratorio['stock']);

                error_log("Datos actuales: unidad_actual=$unidad_actual, unidad_original=$unidad_original, stock_actual=$stock_actual");

                if ($unidad_original <= 0) {
                    throw new Exception("Unidad original no definida para producto ID {$producto_id} y laboratorio ID {$laboratorio_id}.");
                }

                // Calcular nueva unidad
                $nueva_unidad = $unidad_actual - $cantidad;

                // Si la nueva unidad es cero o negativa, ajustar stock y reponer unidad si hay más stock
                $nuevo_stock = $stock_actual;
                if ($nueva_unidad <= 0 && $stock_actual > 0) {
                    $nuevo_stock = $stock_actual - 1;
                    $nueva_unidad = $unidad_original + $nueva_unidad; // Reponer con unidad_original y restar lo que sobra
                    if ($nueva_unidad < 0 && $nuevo_stock > 0) {
                        $nuevo_stock -= 1; // Consumir otra caja si es necesario
                        $nueva_unidad += $unidad_original;
                    }
                }

                // Validar que no queden valores negativos
                if ($nuevo_stock < 0 || $nueva_unidad < 0) {
                    throw new Exception("Stock insuficiente para producto ID {$producto_id} y laboratorio ID {$laboratorio_id}. Unidad actual: $unidad_actual, Stock actual: $stock_actual, Cantidad solicitada: $cantidad");
                }

                error_log("Después de cálculo: nueva_unidad=$nueva_unidad, nuevo_stock=$nuevo_stock");

                // Actualizar solo si hay cambios
                if ($nueva_unidad != $unidad_actual || $nuevo_stock != $stock_actual) {
                    if ($nueva_unidad == 0 && $nuevo_stock == 0) {
                        $stmt_delete = $mysqli->prepare("DELETE FROM producto_laboratorio WHERE producto_id = ? AND laboratorio_id = ?");
                        $stmt_delete->bind_param("ii", $producto_id, $laboratorio_id);
                        if (!$stmt_delete->execute()) {
                            throw new Exception("Error al eliminar entrada de producto_laboratorio: " . $stmt_delete->error);
                        }
                        $stmt_delete->close();
                        error_log("Registro eliminado de producto_laboratorio: producto_id=$producto_id, laboratorio_id=$laboratorio_id");
                    } else {
                        $stmt_update = $mysqli->prepare("UPDATE producto_laboratorio SET unidad = ?, stock = ? WHERE producto_id = ? AND laboratorio_id = ?");
                        $stmt_update->bind_param("iiii", $nueva_unidad, $nuevo_stock, $producto_id, $laboratorio_id);
                        if (!$stmt_update->execute()) {
                            throw new Exception("Error al actualizar unidad/stock en producto_laboratorio: " . $stmt_update->error);
                        }
                        $stmt_update->close();

                        // Verificar actualización
                        error_log("Actualización exitosa: producto_id=$producto_id, laboratorio_id=$laboratorio_id, nueva_unidad=$nueva_unidad, nuevo_stock=$nuevo_stock");
                    }

                    // Actualizar stock general en productos
                    $stmt_stock_total = $mysqli->prepare("UPDATE productos p 
                                                         SET stock = (SELECT COALESCE(SUM(stock), 0) FROM producto_laboratorio pl WHERE pl.producto_id = p.id) 
                                                         WHERE id = ?");
                    $stmt_stock_total->bind_param("i", $producto_id);
                    if (!$stmt_stock_total->execute()) {
                        throw new Exception("Error al actualizar stock general: " . $stmt_stock_total->error);
                    }
                    $stmt_stock_total->close();
                } else {
                    error_log("No se realizó actualización: unidad y stock sin cambios para producto_id=$producto_id, laboratorio_id=$laboratorio_id");
                }
            } else {
                error_log("No se usa unidad, descontando solo stock para producto_id=$producto_id, laboratorio_id=$laboratorio_id");

                // Si no usas unidad, descontar solo stock
                $stmt_stock = $mysqli->prepare("UPDATE producto_laboratorio SET stock = stock - ? WHERE producto_id = ? AND laboratorio_id = ?");
                $stmt_stock->bind_param("dii", $cantidad, $producto_id, $laboratorio_id);
                if (!$stmt_stock->execute()) {
                    throw new Exception("Error al actualizar stock en producto_laboratorio: " . $stmt_stock->error);
                }
                $stmt_stock->close();

                // Actualizar stock general en productos
                $stmt_stock_total = $mysqli->prepare("UPDATE productos p 
                                                     SET stock = (SELECT COALESCE(SUM(stock), 0) FROM producto_laboratorio pl WHERE pl.producto_id = p.id) 
                                                     WHERE id = ?");
                $stmt_stock_total->bind_param("i", $producto_id);
                if (!$stmt_stock_total->execute()) {
                    throw new Exception("Error al actualizar stock general: " . $stmt_stock_total->error);
                }
                $stmt_stock_total->close();
            }
        }
        $stmt->close();

        // Insertar en la tabla reportes
        $stmt = $mysqli->prepare("INSERT INTO reportes (venta_id, cliente_id, numero_factura, fecha, total, impuesto, total_con_impuesto, forma_pago_id, subtotal, banco, cuenta, monto_efectivo, monto_otra_forma_pago) 
                                 VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        error_log("Reportes bind_param: type_string=iisddddidsdd, params_count=12, venta_id=$venta_id, cliente_id=$cliente_id, numero_factura=$numero_factura, total=$total, impuesto=$impuesto, total_con_impuesto=$total_con_impuesto, forma_pago=$forma_pago, subtotal=$subtotal, banco=" . ($banco ?? 'NULL') . ", cuenta=" . ($cuenta ?? 'NULL') . ", monto_efectivo=$monto_efectivo, monto_otra_forma_pago=$monto_otra_forma_pago");
        $stmt->bind_param("iisddddidsdd", $venta_id, $cliente_id, $numero_factura, $total, $impuesto, $total_con_impuesto, $forma_pago, $subtotal, $banco, $cuenta, $monto_efectivo, $monto_otra_forma_pago);
        if (!$stmt->execute()) {
            throw new Exception("Error al insertar en reportes: " . $stmt->error);
        }
        $stmt->close();

        // Confirmar transacción
        $mysqli->commit();

        // Generar el PDF
        try {
            ob_end_clean();
            generarTicketVenta($venta_id, $numero_factura, $subtotal, $impuesto, $total_con_impuesto, $productos, $impuesto_porcentaje, $cliente_id, $forma_pago, $aplicar_impuesto, $monto_efectivo, $monto_otra_forma_pago);
        } catch (Exception $e) {
            throw new Exception("Error al generar PDF: " . $e->getMessage());
        }
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $mysqli->rollback();
        error_log($e->getMessage());
        die($e->getMessage());
    }
}

// Cerrar conexión
$mysqli->close();

// Función para generar el ticket de venta en PDF
function generarTicketVenta($venta_id, $numero_factura, $subtotal, $impuesto, $total_con_impuesto, $productos, $impuesto_porcentaje, $cliente_id, $forma_pago, $aplicar_impuesto, $monto_efectivo, $monto_otra_forma_pago)
{
    // Inicializar TCPDF
    $pdf = new TCPDF('P', 'mm', array(80, 210), true, 'UTF-8', false);
    $pdf->SetCreator('Sistema de Facturación');
    $pdf->SetAuthor('Sistema de Facturación');
    $pdf->SetTitle('Factura de Venta #' . $numero_factura);
    $pdf->SetSubject('Factura de Venta');
    $pdf->SetKeywords('Factura, Venta');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(5, 5, 5); // Ajustar márgenes para mejor espaciado
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 8);

    // Obtener datos de la empresa
    $mysqli = new mysqli("localhost", "root", "", "marci");
    $datos_empresa = $mysqli->query("SELECT * FROM datos_empresa LIMIT 1")->fetch_assoc();
    if (!$datos_empresa) {
        error_log("Error: No se encontraron datos de la empresa.");
        die("Error: No se encontraron datos de la empresa.");
    }

    // Obtener datos del cliente
    $stmt = $mysqli->prepare("SELECT nombre, direccion, identificacion FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$cliente) {
        error_log("Error: Cliente no encontrado.");
        die("Error: Cliente no encontrado.");
    }

    // Obtener datos de la forma de pago
    $stmt = $mysqli->prepare("SELECT tipo, banco, cuenta FROM formas_pago WHERE id = ?");
    $stmt->bind_param("i", $forma_pago);
    $stmt->execute();
    $forma_pago_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$forma_pago_data) {
        error_log("Error: Forma de pago no encontrada.");
        die("Error: Forma de pago no encontrada.");
    }

    // Configurar la hora de Colombia (UTC-5)
    date_default_timezone_set('America/Bogota');
    $fecha_emision = date('d-m-Y H:i A');

    // Contenido del ticket
    $html = '<table cellpadding="2" cellspacing="0" border="0" width="100%">';

    // Encabezado de la empresa
    $html .= '<tr><td colspan="5" align="center"><strong>' . htmlspecialchars($datos_empresa['razon_social']) . '</strong></td></tr>';
    $html .= '<tr><td colspan="5" align="center">NIT: ' . htmlspecialchars($datos_empresa['nit']) . '</td></tr>';
    $html .= '<tr><td colspan="5" align="center">' . htmlspecialchars($datos_empresa['direccion']) . '</td></tr>';
    $html .= '<tr><td colspan="5" align="center">TEL: ' . htmlspecialchars($datos_empresa['telefono']) . '</td></tr>';
    $html .= '<tr><td colspan="5" align="center">Resolución: ' . htmlspecialchars($datos_empresa['resolucion']) . '</td></tr>';
    $html .= '<tr><td colspan="5" align="center"><strong>FACTURA DE VENTA ELECTRÓNICA</strong></td></tr>';
    $html .= '<tr><td colspan="5" align="center">FV' . htmlspecialchars($numero_factura) . '</td></tr>';
    $html .= '<tr><td colspan="5" align="center">FECHA DE EMISIÓN: ' . htmlspecialchars($fecha_emision) . '</td></tr>';

    // Datos del cliente
    $html .= '<tr><td colspan="5"><br></td></tr>'; // Espacio antes de los datos del cliente
    $html .= '<tr><td colspan="5"><strong>Cliente:</strong> ' . htmlspecialchars($cliente['nombre']) . '</td></tr>';
    $html .= '<tr><td colspan="5"><strong>Dirección:</strong> ' . htmlspecialchars($cliente['direccion']) . '</td></tr>';
    $html .= '<tr><td colspan="5"><strong>Cédula/NIT:</strong> ' . htmlspecialchars($cliente['identificacion']) . '</td></tr>';

    // Detalle de productos
    $html .= '<tr><td colspan="5"><br></td></tr>'; // Espacio antes del detalle
    $html .= '<tr><td colspan="5"><strong>Detalle de Productos</strong></td></tr>';
    $html .= '<tr><td><strong>Cant.</strong></td><td><strong>Unidad</strong></td><td><strong>Producto</strong></td><td><strong>Precio</strong></td><td><strong>Total</strong></td></tr>';
    foreach ($productos as $producto) {
        $precio = $producto['usar_unidad'] ? $producto['precio_por_unidad'] : $producto['precio_venta'];
        $unidad = !empty($producto['unidad']) ? $producto['unidad'] : 'N/A';
        $cantidad = $producto['cantidad'];
        $subtotal_producto = $cantidad * $precio;
        $laboratorio_id = intval($producto['laboratorio_id']);

        // Obtener el nombre del laboratorio
        $stmt = $mysqli->prepare("SELECT nombre FROM laboratorios WHERE id = ?");
        $stmt->bind_param("i", $laboratorio_id);
        $stmt->execute();
        $laboratorio = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $laboratorio_nombre = $laboratorio['nombre'] ?? 'N/A';

        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($cantidad) . '</td>';
        $html .= '<td>' . htmlspecialchars($unidad) . '</td>';
        $html .= '<td>' . htmlspecialchars($producto['nombre']) . '<br><small>Laboratorio: ' . htmlspecialchars($laboratorio_nombre) . '</small></td>';
        $html .= '<td>$' . number_format($precio, 2) . '</td>';
        $html .= '<td>$' . number_format($subtotal_producto, 2) . '</td>';
        $html .= '</tr>';
    }

    // Subtotal
    $html .= '<tr><td colspan="5"><br></td></tr>'; // Espacio antes del subtotal
    $html .= '<tr><td colspan="5"><strong>SUBTOTAL:</strong> $' . number_format($subtotal, 2) . '</td></tr>';

    // Impuesto al consumo
    if ($aplicar_impuesto) {
        $html .= '<tr><td colspan="5"><strong>IMP CONSUMO (' . ($impuesto_porcentaje * 100) . '%):</strong> $' . number_format($impuesto, 2) . '</td></tr>';
    }

    // Total
    $html .= '<tr><td colspan="5"><strong>TOTAL:</strong> $' . number_format($total_con_impuesto, 2) . '</td></tr>';

    // Monto en efectivo
    if ($monto_efectivo > 0) {
        $html .= '<tr><td colspan="5"><strong>Monto en Efectivo:</strong> $' . number_format($monto_efectivo, 2) . '</td></tr>';
    }

    // Monto en otra forma de pago
    if ($monto_otra_forma_pago > 0) {
        $html .= '<tr><td colspan="5"><strong>Monto en Otra Forma de Pago:</strong> $' . number_format($monto_otra_forma_pago, 2) . '</td></tr>';
    }

    // Forma de pago
    $html .= '<tr><td colspan="5"><strong>Forma de Pago:</strong> ' . htmlspecialchars($forma_pago_data['tipo']) . '</td></tr>';
    if ($forma_pago_data['tipo'] == 'transferencia') {
        $html .= '<tr><td colspan="5"><strong>Banco:</strong> ' . htmlspecialchars($forma_pago_data['banco'] ?: 'N/A') . '</td></tr>';
        $html .= '<tr><td colspan="5"><strong>Cuenta:</strong> ' . htmlspecialchars($forma_pago_data['cuenta'] ?: 'N/A') . '</td></tr>';
    }

    // Mensaje final
    $html .= '<tr><td colspan="5"><br></td></tr>'; // Espacio antes del mensaje final
    $html .= '<tr><td colspan="5" align="center"><strong>GRACIAS POR SU PREFERENCIA</strong></td></tr>';
    $html .= '</table>';

    // Escribir el contenido en el PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    // Guardar PDF en carpetas
    try {
        guardarFacturaEnCarpeta($pdf, $numero_factura, 'facturas');
        guardarFacturaEnCarpeta($pdf, $numero_factura, 'copiaf');
    } catch (Exception $e) {
        error_log("Error al guardar PDF: " . $e->getMessage());
        die("Error al guardar el PDF: " . $e->getMessage());
    }

    // Mostrar el PDF en el navegador
    $pdf->Output('factura_venta_' . $numero_factura . '.pdf', 'I');

    // Cerrar conexión
    $mysqli->close();
    exit;
}

// Función para guardar la factura en una carpeta
function guardarFacturaEnCarpeta($pdf, $numero_factura, $carpeta)
{
    $carpeta_path = __DIR__ . '/marci/' . $carpeta;
    if (!file_exists($carpeta_path)) {
        if (!mkdir($carpeta_path, 0777, true)) {
            error_log("Error: No se pudo crear la carpeta '$carpeta_path'.");
            throw new Exception("No se pudo crear la carpeta '$carpeta_path'.");
        }
    }
    if (!is_writable($carpeta_path)) {
        error_log("Error: La carpeta '$carpeta_path' no tiene permisos de escritura.");
        throw new Exception("La carpeta '$carpeta_path' no tiene permisos de escritura.");
    }
    $ruta_archivo = $carpeta_path . '/factura_venta_' . $numero_factura . '.pdf';
    $pdf->Output($ruta_archivo, 'F');
}
