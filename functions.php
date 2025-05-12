<?php
// Función para obtener todos los clientes
function obtenerClientes() {
    global $conexion; // Asegúrate de que $conexion esté definida y sea una conexión válida a la base de datos
    $query = "SELECT * FROM clientes";
    $resultado = $conexion->query($query);

    if ($resultado->num_rows > 0) {
        $clientes = [];
        while ($fila = $resultado->fetch_assoc()) {
            $clientes[] = $fila;
        }
        return $clientes;
    } else {
        return []; // Devuelve un array vacío si no hay clientes
    }
}

// Función para obtener todos los productos
function obtenerProductos() {
    global $conexion; // Asegúrate de que $conexion esté definida y sea una conexión válida a la base de datos
    $query = "SELECT * FROM productos";
    $resultado = $conexion->query($query);

    if ($resultado->num_rows > 0) {
        $productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $fila;
        }
        return $productos;
    } else {
        return []; // Devuelve un array vacío si no hay productos
    }
}

// Función para obtener productos por categoría
function obtenerProductosPorCategoria($categoria_id) {
    global $conexion;
    $query = "SELECT * FROM productos WHERE categoria_id = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $categoria_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $fila;
        }
        return $productos;
    } else {
        return []; // Devuelve un array vacío si no hay productos en la categoría
    }
}

// Función para obtener todos los meseros
function obtenerMeseros() {
    global $conexion;
    $query = "SELECT * FROM meseros";
    $resultado = $conexion->query($query);

    if ($resultado->num_rows > 0) {
        $meseros = [];
        while ($fila = $resultado->fetch_assoc()) {
            $meseros[] = $fila;
        }
        return $meseros;
    } else {
        return []; // Devuelve un array vacío si no hay meseros
    }
}

// Función para obtener un mesero por ID
function obtenerMeseroPorId($mesero_id) {
    global $conexion;
    $query = "SELECT * FROM meseros WHERE id = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $mesero_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        return $resultado->fetch_assoc();
    } else {
        return null; // Devuelve null si no se encuentra el mesero
    }
}

// Función para obtener todas las categorías
function obtenerCategorias() {
    global $conexion;
    $query = "SELECT * FROM categorias";
    $resultado = $conexion->query($query);

    if ($resultado->num_rows > 0) {
        $categorias = [];
        while ($fila = $resultado->fetch_assoc()) {
            $categorias[] = $fila;
        }
        return $categorias;
    } else {
        return []; // Devuelve un array vacío si no hay categorías
    }
}

// Función para obtener todas las formas de pago
function obtenerFormasPago() {
    global $conexion;
    $query = "SELECT * FROM formas_pago";
    $resultado = $conexion->query($query);

    if ($resultado->num_rows > 0) {
        $formas_pago = [];
        while ($fila = $resultado->fetch_assoc()) {
            $formas_pago[] = $fila;
        }
        return $formas_pago;
    } else {
        return []; // Devuelve un array vacío si no hay formas de pago
    }
}

// Función para obtener la configuración de impuestos
function obtenerConfiguracionImpuestos() {
    global $conexion;
    $query = "SELECT * FROM configuracion_impuestos WHERE id = 1";
    $resultado = $conexion->query($query);

    if ($resultado->num_rows > 0) {
        return $resultado->fetch_assoc();
    } else {
        return null; // Devuelve null si no hay configuración de impuestos
    }
}

// Función para obtener el número de factura inicial
function obtenerNumeroFacturaInicial() {
    global $conexion;
    $query = "SELECT numero_factura_inicial FROM configuracion_impuestos WHERE id = 1";
    $resultado = $conexion->query($query);

    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        return $fila['numero_factura_inicial'];
    } else {
        return 100; // Devuelve un valor por defecto si no hay configuración
    }
}

// Función para obtener las ventas
function obtenerVentas() {
    global $conexion;
    $query = "SELECT * FROM ventas";
    $resultado = $conexion->query($query);

    if ($resultado->num_rows > 0) {
        $ventas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $ventas[] = $fila;
        }
        return $ventas;
    } else {
        return []; // Devuelve un array vacío si no hay ventas
    }
}

// Función para obtener los detalles de una venta
function obtenerDetallesVenta($venta_id) {
    global $conexion;
    $query = "SELECT * FROM detalles_venta WHERE venta_id = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $detalles = [];
        while ($fila = $resultado->fetch_assoc()) {
            $detalles[] = $fila;
        }
        return $detalles;
    } else {
        return []; // Devuelve un array vacío si no hay detalles de venta
    }
}
?>