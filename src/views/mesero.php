<?php
include_once 'conexion.php';

// Iniciar sesión
session_start();

// Obtenemos el ID del mesero desde la sesión
$id_mesero = $_SESSION['mesero_id'];

// La conexión a la base de datos
$con = conectar();
$conexion = $con;

// Funcion para modificar el estado del pedido general si los tickets asociados estan listos - - - utilizada en la vista de pedidos listos para entregar funcionalidad Andres


// Código PHP para procesar el pedido
// Función para agrupar productos por código
function agruparProductos($productos) {
    $productos_agrupados = [];
    
    foreach ($productos as $producto) {
        $cod = $producto['cod'];
        
        if (isset($productos_agrupados[$cod])) {
            // Si ya existe, sumar la cantidad
            $productos_agrupados[$cod]['cant'] += $producto['cant'];
            $productos_agrupados[$cod]['subtotal'] += $producto['subtotal'];
            
            // Combinar detalles si existen
            if (!empty($producto['detalle'])) {
                $detalle_existente = $productos_agrupados[$cod]['detalle'];
                $nuevo_detalle = $producto['detalle'];
                
                if (!empty($detalle_existente) && $detalle_existente !== $nuevo_detalle) {
                    $productos_agrupados[$cod]['detalle'] = $detalle_existente . ' | ' . $nuevo_detalle;
                } elseif (empty($detalle_existente)) {
                    $productos_agrupados[$cod]['detalle'] = $nuevo_detalle;
                }
            }
        } else {
            // Si no existe, agregar al array
            $productos_agrupados[$cod] = $producto;
        }
    }
    
    return array_values($productos_agrupados);
}

// Procesar la acción de crear pedido: Cuando se envía el formulario en la vista de mesero boton "Guardar Pedido" 
if (isset($_POST['accion']) && $_POST['accion'] === 'crear_pedido') {
    try {
        // Obtener datos del formulario
        $mesa_id = intval($_POST['mesa_id']);
        $mesero_id = intval($_POST['mesero_id']);
        $productos_cocina = json_decode($_POST['productos_cocina'], true) ?? [];
        $productos_barra = json_decode($_POST['productos_barra'], true) ?? [];
        $productos_licor = json_decode($_POST['productos_licor'], true) ?? [];
        
        // agrupar productos por código esto se hace para evitar duplicados
        $productos_cocina = agruparProductos($productos_cocina);
        $productos_barra = agruparProductos($productos_barra);
        $productos_licor = agruparProductos($productos_licor);
        
        // Validar que al menos haya un producto
        if (empty($productos_cocina) && empty($productos_barra) && empty($productos_licor)) {
            throw new Exception("Debe agregar al menos un producto al pedido");
        }
        
        // Validar que la mesa existe y pertenece al mesero
        $stmt_validar = $con->prepare("SELECT id FROM mesa WHERE id = ? AND mesero = ?");
        $stmt_validar->bind_param("ii", $mesa_id, $mesero_id);
        $stmt_validar->execute();
        $result_validar = $stmt_validar->get_result();
        
        if ($result_validar->num_rows === 0) {
            throw new Exception("Mesa no válida o no asignada al mesero");
        }

        // Cerrar la consulta de validación
        $stmt_validar->close();
        
        // Iniciar transacción para asegurar la atomicidad de la operación
        $con->begin_transaction();
        
        // Obtener la fecha y hora actual y formatearla
        $fecha_hora = date('Y-m-d H:i:s');

        // Determinar estados iniciales según qué categorías tienen productos
        $estado_barra = !empty($productos_barra) ? 'pendiente' : null;
        $estado_cocina = !empty($productos_cocina) ? 'pendiente' : null;
        $estado_licor = !empty($productos_licor) ? 'pendiente' : null;
        $estado_general = 'pendiente';

        $stmt_pedido = $con->prepare("INSERT INTO pedido_general (fecha_hora, id_mesa, id_mesero, estado_general, estado_barra, estado_cocina, estado_licor) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_pedido->bind_param("siissss", $fecha_hora, $mesa_id, $mesero_id, $estado_general, $estado_barra, $estado_cocina, $estado_licor);

        if (!$stmt_pedido->execute()) {
            throw new Exception("Error al crear el pedido general: " . $stmt_pedido->error);
        }

        $pedido_id = $con->insert_id;
        $stmt_pedido->close();

        // Insertar productos en tickets específicos
        if (!empty($productos_cocina)) {
            $stmt_cocina = $con->prepare("INSERT INTO ticket_comida (pedido_id, cod, cant, detalle) VALUES (?, ?, ?, ?)");
            foreach ($productos_cocina as $producto) {
                $detalle = $producto['detalle'] ?? '';
                $stmt_cocina->bind_param("iiss", $pedido_id, $producto['cod'], $producto['cant'], $detalle);
                if (!$stmt_cocina->execute()) {
                    throw new Exception("Error al insertar en ticket_comida: " . $stmt_cocina->error);
                }
            }
            $stmt_cocina->close();
        }

        if (!empty($productos_barra)) {
            $stmt_barra = $con->prepare("INSERT INTO ticket_barra (pedido_id, cod, cant, detalle) VALUES (?, ?, ?, ?)");
            foreach ($productos_barra as $producto) {
                $detalle = $producto['detalle'] ?? '';
                $stmt_barra->bind_param("iiss", $pedido_id, $producto['cod'], $producto['cant'], $detalle);
                if (!$stmt_barra->execute()) {
                    throw new Exception("Error al insertar en ticket_barra: " . $stmt_barra->error);
                }
            }
            $stmt_barra->close();
        }

        if (!empty($productos_licor)) {
            $stmt_licor = $con->prepare("INSERT INTO ticket_licor (pedido_id, cod, cant, detalle) VALUES (?, ?, ?, ?)");
            foreach ($productos_licor as $producto) {
                $detalle = $producto['detalle'] ?? '';
                $stmt_licor->bind_param("iiss", $pedido_id, $producto['cod'], $producto['cant'], $detalle);
                if (!$stmt_licor->execute()) {
                    throw new Exception("Error al insertar en ticket_licor: " . $stmt_licor->error);
                }
            }
            $stmt_licor->close();
        }

        // Calcular total del pedido
        $total_pedido = 0;
        $all_productos = array_merge($productos_cocina, $productos_barra, $productos_licor);
        foreach ($all_productos as $producto) {
            $total_pedido += floatval($producto['subtotal']);
        }

        // Actualizar el total en el pedido general
        $stmt_total = $con->prepare("UPDATE pedido_general SET total = ? WHERE id = ?");
        $stmt_total->bind_param("di", $total_pedido, $pedido_id);
        if (!$stmt_total->execute()) {
            throw new Exception("Error al actualizar el total: " . $stmt_total->error);
        }
        $stmt_total->close();

        // Actualizar estado de la mesa a 'ATENDIENDO'
        $stmt_mesa = $con->prepare("UPDATE mesa SET estado = 'ATENDIENDO' WHERE id = ?");
        $stmt_mesa->bind_param("i", $mesa_id);
        if (!$stmt_mesa->execute()) {
            throw new Exception("Error al actualizar el estado de la mesa: " . $stmt_mesa->error);
        }

        // Cerrar la consulta de actualización de mesa
        $stmt_mesa->close();
        
        // Confirmar transacción
        $con->commit();
        
        // Preparar respuesta de éxito
        $response = [
            'success' => true,
            'message' => 'Pedido creado exitosamente',
            'pedido_id' => $pedido_id,
            'total' => $total_pedido,
            'fecha_hora' => $fecha_hora
        ];
        
        // Si es una petición AJAX, devolver JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        
        // Si no es AJAX, redirigir o mostrar mensaje
        $_SESSION['mensaje'] = "Pedido #{$pedido_id} creado exitosamente";
        $_SESSION['tipo_mensaje'] = 'success';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
        
    } catch (Exception $e) {
        // En caso de error, revertir transacción
        $con->rollback();
        
        // Log del error para debugging
        error_log("Error al crear pedido: " . $e->getMessage());
        
        // Preparar respuesta de error
        $response = [
            'success' => false,
            'message' => 'Error al crear el pedido: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ];
        
        // Si es una petición AJAX, devolver JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode($response);
            exit;
        }
        
        // Si no es AJAX, mostrar mensaje de error
        $_SESSION['mensaje'] = "Error al crear el pedido: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'error';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Función auxiliar para obtener detalles del pedido --- UTILIZAR PARA OTRAS FUNCIONALIDADES LAS RESPECTIVAS VISTAS
function obtenerDetallesPedido($conexion, $pedido_id) {
    try {
        $stmt = $conexion->prepare("
            SELECT pg.*, m.numero as mesa_numero, u.nombre as mesero_nombre
            FROM pedido_general pg
            LEFT JOIN mesa m ON pg.id_mesa = m.id
            LEFT JOIN usuarios u ON pg.id_mesero = u.id
            WHERE pg.id = ?
        ");
        $stmt->execute([$pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pedido) {
            // Obtener productos de cada categoría
            $categorias = ['cocina', 'barra', 'licor'];
            $productos = [];
            
            foreach ($categorias as $categoria) {
                $stmt_productos = $conexion->prepare("
                    SELECT t.*, p.nombre, p.precio
                    FROM ticket_{$categoria} t
                    LEFT JOIN producto p ON t.cod = p.id_producto
                    WHERE t.id = ?
                ");
                $stmt_productos->execute([$pedido_id]);
                $productos[$categoria] = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $pedido['productos'] = $productos;
        }
        
        return $pedido;
        
    } catch (Exception $e) {
        error_log("Error al obtener detalles del pedido: " . $e->getMessage());
        return null;
    }
}

// Función para actualizar estado de un ticket específico
function actualizarEstadoTicket($conexion, $pedido_id, $categoria, $nuevo_estado) {
    try {
        $conexion->beginTransaction();
        
        // Actualizar estado específico
        $stmt = $conexion->prepare("UPDATE pedido_general SET estado_{$categoria} = ? WHERE id = ?");
        $stmt->execute([$nuevo_estado, $pedido_id]);
        
        // Verificar si todos los tickets están completados para actualizar estado general
        $stmt_check = $conexion->prepare("
            SELECT estado_cocina, estado_barra, estado_licor
            FROM pedido_general
            WHERE id = ?
        ");
        $stmt_check->execute([$pedido_id]);
        $estados = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        $todos_completados = true;
        foreach ($estados as $estado) {
            if ($estado !== null && $estado !== 'completado') {
                $todos_completados = false;
                break;
            }
        }
        
        if ($todos_completados) {
            $stmt_general = $conexion->prepare("UPDATE pedido_general SET estado_general = 'completado' WHERE id = ?");
            $stmt_general->execute([$pedido_id]);
        }
        
        $conexion->commit();
        return true;
        
    } catch (Exception $e) {
        $conexion->rollBack();
        error_log("Error al actualizar estado del ticket: " . $e->getMessage());
        return false;
    }
}

// Función para validar disponibilidad de productos
function validarDisponibilidadProductos($conexion, $productos) {
    try {
        $productos_no_disponibles = [];
        
        foreach ($productos as $categoria => $items) {
            foreach ($items as $producto) {
                $stmt = $conexion->prepare("
                    SELECT id_producto, nombre, disponible, stock
                    FROM producto
                    WHERE id_producto = ? AND disponible = 1
                ");
                $stmt->execute([$producto['cod']]);
                $producto_db = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$producto_db) {
                    $productos_no_disponibles[] = [
                        'cod' => $producto['cod'],
                        'nombre' => $producto['nombre'],
                        'motivo' => 'Producto no disponible'
                    ];
                } elseif (isset($producto_db['stock']) && $producto_db['stock'] < $producto['cant']) {
                    $productos_no_disponibles[] = [
                        'cod' => $producto['cod'],
                        'nombre' => $producto['nombre'],
                        'motivo' => "Stock insuficiente (disponible: {$producto_db['stock']})"
                    ];
                }
            }
        }
        
        return $productos_no_disponibles;
        
    } catch (Exception $e) {
        error_log("Error al validar disponibilidad: " . $e->getMessage());
        return ['error' => 'Error al validar disponibilidad'];
    }
}

// Función para obtener los pedidos del mesero actual
function obtenerPedidos($id_mesero) {
    $con = conectar();
    
    $query = "SELECT pg.id, pg.fecha_hora, pg.id_mesa, pg.estado_general, m.id as mesa_numero
                FROM pedido_general pg
                INNER JOIN mesa m ON pg.id_mesa = m.id
                WHERE pg.id_mesero = ?
                ORDER BY 
                CASE pg.estado_general
                    WHEN 'pendiente' THEN 1
                    WHEN 'servir' THEN 2
                    WHEN 'entregado' THEN 3
                    WHEN 'pagado' THEN 4
                    ELSE 5
                END,
                pg.id DESC";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $id_mesero);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $pedidos = [];
    while ($row = $resultado->fetch_assoc()) {
        $pedidos[] = $row;
    }
    
    $stmt->close();
    mysqli_close($con);
    
    return $pedidos;
}

// Obtener pedidos del mesero actual
$pedidos = obtenerPedidos($id_mesero);

// --- FUNCIONALIDAD NUEVA: Notificaciones de tickets listos para entregar ---

// Función para obtener notificaciones de tickets listos para el mesero actual
function obtenerNotificacionesTicketsListos($con, $id_mesero) {
    $notificaciones = [];
    $query = "
        SELECT pg.id as pedido_id, m.id as mesa_numero, pg.fecha_hora,
            'barra' as dependencia, pg.estado_barra as estado_ticket
        FROM pedido_general pg
        INNER JOIN mesa m ON pg.id_mesa = m.id
        WHERE pg.id_mesero = ? AND pg.estado_barra = 'listo'
        UNION ALL
        SELECT pg.id as pedido_id, m.id as mesa_numero, pg.fecha_hora,
            'cocina' as dependencia, pg.estado_cocina as estado_ticket
        FROM pedido_general pg
        INNER JOIN mesa m ON pg.id_mesa = m.id
        WHERE pg.id_mesero = ? AND pg.estado_cocina = 'listo'
        UNION ALL
        SELECT pg.id as pedido_id, m.id as mesa_numero, pg.fecha_hora,
            'licor' as dependencia, pg.estado_licor as estado_ticket
        FROM pedido_general pg
        INNER JOIN mesa m ON pg.id_mesa = m.id
        WHERE pg.id_mesero = ? AND pg.estado_licor = 'listo'
        ORDER BY fecha_hora DESC
    ";
    $stmt = $con->prepare($query);
    $stmt->bind_param("iii", $id_mesero, $id_mesero, $id_mesero);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $notificaciones[] = $row;
    }
    $stmt->close();
    return $notificaciones;
}

// Procesar acción de entregar ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'entregar_ticket') {
    $pedido_id = intval($_POST['pedido_id']);
    $dependencia = $_POST['dependencia'];
    $campo_estado = "estado_" . $dependencia;
    $stmt = $con->prepare("UPDATE pedido_general SET $campo_estado = 'entregado' WHERE id = ?");
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $stmt->close();

    // Verificar estados de todos los tickets para actualizar estado_general
    $stmt_check = $con->prepare("SELECT estado_cocina, estado_barra, estado_licor FROM pedido_general WHERE id = ?");
    $stmt_check->bind_param("i", $pedido_id);
    $stmt_check->execute();
    $res = $stmt_check->get_result();
    $estados = $res->fetch_assoc();
    $stmt_check->close();

    // Filtrar solo los estados que no son NULL
    $estados_validos = [];
    foreach (['estado_cocina', 'estado_barra', 'estado_licor'] as $campo) {
        if ($estados[$campo] !== null) {
            $estados_validos[] = $estados[$campo];
        }
    }

$estados_validos = array_filter($estados_validos); // Elimina posibles valores null, "", false

// Eliminar duplicados para facilitar la lógica
$estados_unicos = array_unique($estados_validos);

// Caso 1: todos entregados
if (count($estados_unicos) === 1 && $estados_unicos[0] === 'entregado') {
    $stmt_general = $con->prepare("UPDATE pedido_general SET estado_general = 'entregado' WHERE id = ?");
    $stmt_general->bind_param("i", $pedido_id);
    $stmt_general->execute();
    $stmt_general->close();

// Caso 2: todos listos
} elseif (count($estados_unicos) === 1 && $estados_unicos[0] === 'listo') {
    $stmt_general = $con->prepare("UPDATE pedido_general SET estado_general = 'listo' WHERE id = ?");
    $stmt_general->bind_param("i", $pedido_id);
    $stmt_general->execute();
    $stmt_general->close();

// Caso 3: mezcla de listo y entregado (todos válidos en esos dos)
} elseif (!array_diff($estados_unicos, ['listo', 'entregado'])) {
    $stmt_general = $con->prepare("UPDATE pedido_general SET estado_general = 'listo' WHERE id = ?");
    $stmt_general->bind_param("i", $pedido_id);
    $stmt_general->execute();
    $stmt_general->close();

// Caso contrario: aún pendiente
} else {
    $stmt_general = $con->prepare("UPDATE pedido_general SET estado_general = 'pendiente' WHERE id = ?");
    $stmt_general->bind_param("i", $pedido_id);
    $stmt_general->execute();
    $stmt_general->close();
}


    $_SESSION['mensaje'] = "Ticket entregado correctamente.";
    $_SESSION['tipo_mensaje'] = "success";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Obtener notificaciones para mostrar en la vista
$notificacionesTickets = obtenerNotificacionesTicketsListos($con, $id_mesero);

// Función para obtener el nombre del estado con ícono
function getEstadoConIcono($estado) {
    switch ($estado) {
        case 'pendiente':
            return '⏳ Pendiente';
        case 'servir':
            return '👨‍🍳 Listo para servir';
        case 'entregado':
            return '✅ Entregado';
        case 'pagado':
            return '💲 Pagado';
        default:
            return $estado;
    }
}

// Función para obtener la clase CSS según el estado
function getClaseEstado($estado) {
    switch ($estado) {
        case 'pendiente':
            return 'warning';
        case 'servir':
            return 'success';
        case 'entregado':
            return 'primary';
        case 'pagado':
            return 'secondary';
        default:
            return 'light';
    }
}

// Procesar acciones POST y redirigir para evitar reenvío
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'actualizar_estado') {
        $id_mesa = intval($_POST['id_mesa']);
        $nuevo_estado = $_POST['nuevo_estado'];

        // Validar que la mesa pertenece al mesero actual
        $stmt = $con->prepare("SELECT id FROM mesa WHERE id = ? AND mesero = ?");
        $stmt->bind_param("ii", $id_mesa, $id_mesero);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            // Solo permitir cambios de OCUPADA a ATENDIENDO
            if ($nuevo_estado === 'ATENDIENDO') {
                $stmt_update = $con->prepare("UPDATE mesa SET estado = 'ATENDIENDO' WHERE id = ? AND mesero = ? AND estado = 'OCUPADA'");
                $stmt_update->bind_param("ii", $id_mesa, $id_mesero);
                
                if ($stmt_update->execute() && $stmt_update->affected_rows > 0) {
                    $_SESSION['mensaje'] = "Mesa #$id_mesa ahora está siendo atendida.";
                    $_SESSION['tipo_mensaje'] = "success";
                } else {
                    $_SESSION['mensaje'] = "No se pudo actualizar el estado de la mesa. Verifica que esté ocupada.";
                    $_SESSION['tipo_mensaje'] = "warning";
                }
                $stmt_update->close();
            } else {
                $_SESSION['mensaje'] = "Acción no permitida.";
                $_SESSION['tipo_mensaje'] = "warning";
            }
        } else {
            $_SESSION['mensaje'] = "No tienes permisos para modificar esta mesa.";
            $_SESSION['tipo_mensaje'] = "warning";
        }
        $stmt->close();
    }
    
    // Redirigir para evitar el reenvío del formulario
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Mostrar mensajes de sesión
$mensaje = $_SESSION['mensaje'] ?? null;
$tipo_mensaje = $_SESSION['tipo_mensaje'] ?? null;
unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);

// Función para calcular el tiempo transcurrido
function calcularTiempo($fecha_asignacion) {
    if (!$fecha_asignacion) return '-';
    
    $ahora = new DateTime();
    $fecha = new DateTime($fecha_asignacion);
    $diferencia = $ahora->diff($fecha);
    
    if ($diferencia->h > 0) {
        return $diferencia->h . 'h ' . $diferencia->i . 'm';
    } elseif ($diferencia->i > 0) {
        return $diferencia->i . ' min';
    } else {
        return 'Recién asignada';
    }
}

// Función para calcular prioridad basada en tiempo
function calcularPrioridad($fecha_asignacion) {
    if (!$fecha_asignacion) return ['prioridad' => '-', 'class' => 'secondary'];
    
    $ahora = new DateTime();
    $fecha = new DateTime($fecha_asignacion);
    $diferencia = $ahora->diff($fecha);
    $minutos = ($diferencia->h * 60) + $diferencia->i;
    
    if ($minutos > 30) {
        return ['prioridad' => 'Alta', 'class' => 'danger'];
    } elseif ($minutos > 15) {
        return ['prioridad' => 'Media', 'class' => 'warning'];
    } else {
        return ['prioridad' => 'Baja', 'class' => 'info'];
    }
}

// Obtener las mesas asignadas al mesero actual (solo OCUPADA y ATENDIENDO)
try {
    $stmt = $con->prepare("
        SELECT id, estado, tipo, mesero, fecha_asignacion 
        FROM mesa
        WHERE mesero = ? AND estado IN ('OCUPADA', 'ATENDIENDO')
        ORDER BY 
            CASE 
                WHEN estado = 'OCUPADA' THEN 1 
                WHEN estado = 'ATENDIENDO' THEN 2 
                ELSE 3 
            END,
            fecha_asignacion ASC
    ");
    $stmt->bind_param("i", $id_mesero);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $mesas = [];
    $stats = [
        'total' => 0,
        'ocupadas' => 0,
        'atendiendo' => 0,
    ];
    
    while ($row = $resultado->fetch_assoc()) {
        // Calcular tiempo y prioridad
        $tiempo = calcularTiempo($row['fecha_asignacion']);
        $prioridad_info = calcularPrioridad($row['fecha_asignacion']);
        
        // Agregar información adicional a la mesa
        $row['tiempo_asignacion'] = $tiempo;
        $row['prioridad'] = $prioridad_info['prioridad'];
        $row['prioridad_class'] = $prioridad_info['class'];
        
        // Agregar acción requerida basada en el estado
        switch ($row['estado']) {
            case 'OCUPADA':
                $row['accion_requerida'] = 'Tomar orden';
                break;
            case 'ATENDIENDO':
                $row['accion_requerida'] = 'Continuar servicio';
                break;
            default:
                $row['accion_requerida'] = 'Verificar estado';
        }
        
        $mesas[] = $row;
        
        // Actualizar estadísticas
        $stats['total']++;
        switch ($row['estado']) {
            case 'OCUPADA':
                $stats['ocupadas']++;
                break;
            case 'ATENDIENDO':
                $stats['atendiendo']++;
                break;
        }
    }
    
    $stmt->close();
    
    // Organizar datos para el template
    $dataMesas = [
        'mesa' => $mesas,
        'stats' => $stats
    ];
    
    // Calcular total de mesas que requieren atención (solo ocupadas sin atender)
    $total_pendientes = $stats['ocupadas'];
    
    // Inicializar notificaciones
    $notificaciones = [];
    
    // Agregar notificaciones para mesas con alta prioridad
    foreach ($mesas as $mesa) {
        if ($mesa['prioridad'] === 'Alta' && $mesa['estado'] === 'OCUPADA') {
            $notificaciones[] = "Mesa {$mesa['id']} requiere atención urgente";
        }
    }
    
} catch (Exception $e) {
    $mensaje = "Error al cargar las mesas: " . $e->getMessage();
    $tipo_mensaje = "danger";
    
    // Valores por defecto en caso de error
    $dataMesas = ['mesa' => [], 'stats' => ['total' => 0, 'ocupadas' => 0, 'atendiendo' => 0]];
    $total_pendientes = 0;
    $notificaciones = [];
}

// Obtener productos de la base de datos
try {
    $stmt_productos = $con->prepare("
        SELECT id_producto, nombre, tipo, precio 
        FROM producto 
        ORDER BY tipo ASC, nombre ASC
    ");
    $stmt_productos->execute();
    $resultado_productos = $stmt_productos->get_result();
    
    $productos = [];
    $stats_productos = [
        'total' => 0,
        'comida' => 0,
        'coctel' => 0,
        'licor' => 0
    ];
    
    while ($row = $resultado_productos->fetch_assoc()) {
        $productos[] = $row;
        
        // Actualizar estadísticas
        $stats_productos['total']++;
        $tipo_lower = strtolower($row['tipo']);
        if (isset($stats_productos[$tipo_lower])) {
            $stats_productos[$tipo_lower]++;
        }
    }
    
    $stmt_productos->close();
    
    // Organizar datos para el template
    $dataProductos = [
        'producto' => $productos,
        'stats' => $stats_productos
    ];
    
} catch (Exception $e) {
    // En caso de error, inicializar con valores vacíos
    $dataProductos = [
        'producto' => [],
        'stats' => ['total' => 0, 'comida' => 0, 'coctel' => 0, 'licor' => 0]
    ];
    
    // Opcional: agregar mensaje de error
    if (!isset($mensaje)) {
        $mensaje = "Error al cargar productos: " . $e->getMessage();
        $tipo_mensaje = "warning";
    }
}

// Función auxiliar para formatear precio -- Utilizada en el template para mostrar precios
function formatearPrecio($precio) {
    return '$' . number_format($precio, 0, ',', '.');
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/img/icono.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../css/styles1.css">
    <title>Mesero - Plaza Andina</title>
</head>
<body>
    <!-- Header General -->
    <header class="main-header">
        <div class="header-content">
            <div class="header-left">
                <div class="logo-container">
                    🏪
                </div>
                <div class="header-title">
                    <h1>Plaza Andina</h1>
                    <p>Sistema de Gestión Restaurante</p>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar">👨‍🍳</div>
                    <div>
                        <div style="font-weight: 600;">Meser@ <?php echo htmlspecialchars($_SESSION["mesero_name"] ?? 'Usuario'); ?></div>
                        <div style="font-size: 0.8rem; opacity: 0.8;">Sesión Activa</div>
                    </div>
                </div>
                <?php if (count($notificaciones) > 0): ?>
                <div class="notification-badge">
                    <span class="badge bg-danger"><?php echo count($notificaciones); ?></span>
                </div>
                <?php endif; ?>
                <a href="../index.php" class="logout-btn">
                    🚪 Cerrar Sesión
                </a>
            </div>
        </div>
    </header>
    
    <!-- Mostrar mensajes -->
    <?php if (isset($mensaje)): ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Dashboard Content -->
    <div class="dashboard-container">
        <div class="container">
            <div class="dashboard-card">
                <div class="role-header">
                    <h1 class="role-title">🍽️ Mesero</h1>
                    <p class="role-subtitle">Atención al Cliente y Servicio</p>
                </div>

                <div class="quick-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $dataMesas['stats']['total']; ?></div>
                        <div class="stat-label">Mesas Activas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_pendientes; ?></div>
                        <div class="stat-label">Esperan Atención</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $dataMesas['stats']['atendiendo']; ?></div>
                        <div class="stat-label">Atendiendo</div>
                    </div>
                </div>

                <div class="feature-grid">
                    <div class="feature-card" data-bs-toggle="modal" data-bs-target="#mesasModal">
                        <div class="feature-icon">🏠</div>
                        <h3 class="feature-title">Mis Mesas</h3>
                        <p class="feature-description">Ver y gestionar las mesas que tengo asignadas</p>
                        <button class="btn-dashboard">Ver Mesas</button>
                    </div>

                    <div class="feature-card" data-bs-toggle="modal" data-bs-target="#ordenAtencionModal">
                        <div class="feature-icon">💳</div>
                        <h3 class="feature-title">Orden de Atención</h3>
                        <p class="feature-description">Ver orden de prioridad de atención</p>
                        <button class="btn-dashboard">Ver Orden</button>
                    </div>

                    <div class="feature-card" data-bs-toggle="modal" data-bs-target="#catalogoProductosModal">
                        <div class="feature-icon">🍔</div>
                        <h3 class="feature-title">Catálogo</h3>
                        <p class="feature-description">Productos de comida, coctel y licor.</p>
                        <button class="btn-dashboard">Ver Catálogo</button>
                    </div>

                    <div class="feature-card" data-bs-toggle="modal" data-bs-target="#nuevoPedidoModal">
                        <div class="feature-icon">📦</div>
                        <h3 class="feature-title">Nuevo Pedido</h3>
                        <p class="feature-description">Nueva Orden</p>
                        <button class="btn-dashboard">Tomar Pedidos</button>
                    </div>

                    <div class="feature-card" data-bs-toggle="modal" data-bs-target="#pedidosActivosModal">
                        <div class="feature-icon">📦</div>
                        <h3 class="feature-title">Ver Pedidos</h3>
                        <p class="feature-description">Ver y gestionar mis pedidos activos</p>
                        <button class="btn-dashboard">Ver Pedidos</button>
                    </div>

                    <!-- Agregar botón para abrir el modal de notificaciones en el panel de funcionalidades -->
                    <div class="feature-card" data-bs-toggle="modal" data-bs-target="#notificacionesModal">
                        <div class="feature-icon">🔔</div>
                        <h3 class="feature-title">Historial de notificaciones</h3>
                        <p class="feature-description">Tickets listos para entregar</p>
                        <button class="btn-dashboard">Ver Notificaciones</button>
                        <?php if (count($notificacionesTickets) > 0): ?>
                            <span class="badge bg-danger position-absolute top-0 end-0"><?php echo count($notificacionesTickets); ?></span>
                        <?php endif; ?>
                    </div>
            </div>
        </div>
    </div>

    <!-- Modal Mis Mesas -->
    <div class="modal fade" id="mesasModal" tabindex="-1" aria-labelledby="mesasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-lg-down modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="mesasModalLabel">🏠 Mis Mesas Asignadas</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>    
                </div>
                <div class="modal-body">
                    <?php if (empty($dataMesas['mesa'])): ?>
                        <div class="text-center">
                            <div class="alert alert-info">
                                <h4>📋 Sin mesas activas</h4>
                                <p>No tienes mesas ocupadas o en atención en este momento.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Buscar y Filtrar -->
                        <div class="search-filter-container">
                            <div class="row">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <div class="input-group">
                                        <span class="input-group-text">🔍</span>
                                        <input type="text" class="form-control" id="buscarMesa" placeholder="Buscar mesa por número...">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" id="filtroEstado">
                                        <option value="">🏷️ Todos los estados</option>
                                        <option value="OCUPADA">🔴 Sin atender</option>
                                        <option value="ATENDIENDO">👋 Atendiendo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Sección de tabla -->
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover" id="tablaMesas">
                                    <thead>
                                        <tr>
                                            <th>Mesa</th>
                                            <th>Estado</th>
                                            <th>Tipo</th>
                                            <th>Tiempo</th>
                                            <th>Prioridad</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($dataMesas['mesa'] as $mesa): ?>
                                        <tr class="<?php 
                                            if ($mesa['estado'] == 'OCUPADA') echo 'table-danger';
                                            elseif ($mesa['estado'] == 'ATENDIENDO') echo 'table-warning';
                                        ?>">
                                            <td>
                                                <strong>🏠 Mesa <?php echo htmlspecialchars($mesa['id']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    switch($mesa['estado']) {
                                                        case 'OCUPADA': echo 'danger'; break;
                                                        case 'ATENDIENDO': echo 'primary'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php 
                                                        switch($mesa['estado']) {
                                                            case 'OCUPADA': echo '🔴 Sin atender'; break;
                                                            case 'ATENDIENDO': echo '👋 Atendiendo'; break;
                                                            default: echo ucfirst(strtolower($mesa['estado']));
                                                        }
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo ucfirst(strtolower($mesa['tipo'] ?? 'Mesa')); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($mesa['tiempo_asignacion']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $mesa['prioridad_class']; ?>">
                                                    <?php echo htmlspecialchars($mesa['prioridad']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($mesa['estado'] == 'OCUPADA'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="cambiarEstado(<?php echo $mesa['id']; ?>, 'ATENDIENDO')">
                                                        👋 Atender
                                                    </button>
                                                <?php elseif ($mesa['estado'] == 'ATENDIENDO'): ?>
                                                    <span class="badge bg-primary">✅ En atención</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Resumen -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <strong>📊 Total Activas:</strong> <?php echo $dataMesas['stats']['total']; ?>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>🔴 Sin Atender:</strong> <?php echo $dataMesas['stats']['ocupadas']; ?>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>👋 Atendiendo:</strong> <?php echo $dataMesas['stats']['atendiendo']; ?>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>⚡ Pendientes:</strong> <?php echo $total_pendientes; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="location.reload()">
                        🔄 Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Orden de Atención -->
    <div class="modal fade" id="ordenAtencionModal" tabindex="-1" aria-labelledby="ordenAtencionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="ordenAtencionModalLabel">💳 Orden de Atención</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <?php 
                        // Filtrar solo mesas ocupadas (sin atender), ordenar por prioridad
                        $mesasParaAtencion = array_filter($dataMesas['mesa'], function($mesa) {
                            return $mesa['estado'] === 'OCUPADA';
                        });
                        
                        // Ordenar por prioridad (Alta, Media, Baja) y luego por tiempo de asignación
                        usort($mesasParaAtencion, function($a, $b) {
                            $prioridadOrden = ['Alta' => 1, 'Media' => 2, 'Baja' => 3];
                            $prioA = isset($prioridadOrden[$a['prioridad']]) ? $prioridadOrden[$a['prioridad']] : 4;
                            $prioB = isset($prioridadOrden[$b['prioridad']]) ? $prioridadOrden[$b['prioridad']] : 4;
                            
                            if ($prioA != $prioB) {
                                return $prioA - $prioB;
                            }
                            
                            // Si tienen la misma prioridad, ordenar por fecha de asignación (más antigua primero)
                            return strtotime($a['fecha_asignacion']) - strtotime($b['fecha_asignacion']);
                        });
                        
                        if (empty($mesasParaAtencion)): ?>
                            <div class="col-12 text-center">
                                <div class="alert alert-success">
                                    <h4>🎉 ¡Excelente trabajo!</h4>
                                    <p>Todas las mesas asignadas están siendo atendidas o no hay mesas pendientes.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach($mesasParaAtencion as $index => $mesa): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-<?php echo $mesa['prioridad_class']; ?>">
                                    <div class="card-header bg-<?php echo $mesa['prioridad_class']; ?> text-white">
                                        <h5 class="card-title mb-0">
                                            <span class="badge bg-light text-dark me-2">#<?php echo $index + 1; ?></span>
                                            🏠 Mesa <?php echo htmlspecialchars($mesa['id']); ?>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">Tiempo esperando:</small>
                                                <div class="fw-bold"><?php echo htmlspecialchars($mesa['tiempo_asignacion']); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Prioridad:</small>
                                                <div>
                                                    <span class="badge bg-<?php echo $mesa['prioridad_class']; ?>">
                                                        <?php echo htmlspecialchars($mesa['prioridad']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="text-center">
                                            <small class="text-muted">Acción requerida:</small>
                                            <div class="fw-bold text-<?php echo $mesa['prioridad_class']; ?>">
                                                <?php echo htmlspecialchars($mesa['accion_requerida']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer text-center">
                                        <button class="btn btn-success" onclick="cambiarEstado(<?php echo $mesa['id']; ?>, 'ATENDIENDO')">
                                            👋 Atender
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="location.reload()">
                        🔄 Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>



    <!-- Modal Catálogo de Productos -->
    <div class="modal fade" id="catalogoProductosModal" tabindex="-1" aria-labelledby="catalogoProductosModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="catalogoProductosModalLabel">🍽️ Catálogo de Productos</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Barra de búsqueda -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text">🔍</span>
                                <input type="text" class="form-control" id="buscarProducto" placeholder="Buscar productos por nombre, código o categoría..." onkeyup="filtrarProductos(this.value)">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-secondary w-100" onclick="limpiarBusqueda()">
                                🗑️ Limpiar búsqueda
                            </button>
                        </div>
                    </div>

                    <!-- Contador de resultados -->
                    <div class="alert alert-light border" id="contadorResultados" style="display: none;">
                        <small id="textoContador"></small>
                    </div>

                    <!-- Pestañas de navegación -->
                    <ul class="nav nav-tabs mb-4" id="productTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="todos-tab" data-bs-toggle="tab" data-bs-target="#todos" type="button" role="tab" aria-controls="todos" aria-selected="true">
                                🏷️ Todos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="comida-tab" data-bs-toggle="tab" data-bs-target="#comida" type="button" role="tab" aria-controls="comida" aria-selected="false">
                                🍝 Comida
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="coctel-tab" data-bs-toggle="tab" data-bs-target="#coctel" type="button" role="tab" aria-controls="coctel" aria-selected="false">
                                🍹 Cócteles
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="licor-tab" data-bs-toggle="tab" data-bs-target="#licor" type="button" role="tab" aria-controls="licor" aria-selected="false">
                                🥃 Licores
                            </button>
                        </li>
                    </ul>

                    <!-- Contenido de las pestañas -->
                    <div class="tab-content" id="productTabsContent">
                        <?php 
                        // Obtener productos de la base de datos
                        $productos = $dataProductos['producto'] ?? [];
                        
                        // Organizar productos por tipo
                        $productosPorTipo = [];
                        foreach($productos as $producto) {
                            $tipo = strtolower($producto['tipo']);
                            $productosPorTipo[$tipo][] = $producto;
                        }
                        
                        // Función para mostrar productos
                        function mostrarProductos($productos, $mostrarTodos = false) {
                            if (empty($productos)): ?>
                                <div class="col-12 text-center">
                                    <div class="alert alert-info">
                                        <h5>📦 No hay productos disponibles</h5>
                                        <p>No se encontraron productos en esta categoría.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach($productos as $producto): 
                                    // Determinar el ícono según el tipo
                                    $iconos = [
                                        'comida' => '🍽️',
                                        'coctel' => '🍹',
                                        'licor' => '🥃'
                                    ];
                                    $icono = $iconos[strtolower($producto['tipo'])] ?? '🏷️';
                                    
                                    // Determinar clase de color según el tipo
                                    $colores = [
                                        'comida' => 'success',
                                        'coctel' => 'info',
                                        'licor' => 'warning'
                                    ];
                                    $color = $colores[strtolower($producto['tipo'])] ?? 'secondary';
                                ?>
                                <div class="col-lg-4 col-md-6 mb-3">
                                    <div class="card h-100 border-<?php echo $color; ?> producto-card" data-producto-id="<?php echo $producto['id_producto']; ?>">
                                        <div class="card-header bg-<?php echo $color; ?> text-white">
                                            <h6 class="card-title mb-0">
                                                <?php echo $icono; ?> <?php echo htmlspecialchars($producto['nombre']); ?>
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-6">
                                                    <small class="text-muted">Código:</small>
                                                    <div class="fw-bold">#<?php echo htmlspecialchars($producto['id_producto']); ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Categoría:</small>
                                                    <div>
                                                        <span class="badge bg-<?php echo $color; ?>">
                                                            <?php echo ucfirst(htmlspecialchars($producto['tipo'])); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="text-center">
                                                <small class="text-muted">Precio:</small>
                                                <div class="fs-4 fw-bold text-<?php echo $color; ?>">
                                                    $<?php echo number_format($producto['precio'], 0, ',', '.'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif;
                        }
                        ?>

                        <!-- Pestaña Todos -->
                        <div class="tab-pane fade show active" id="todos" role="tabpanel" aria-labelledby="todos-tab">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <div class="alert alert-primary">
                                        📊 <strong>Total de productos:</strong> <?php echo count($productos); ?>
                                    </div>
                                </div>
                                <?php mostrarProductos($productos, true); ?>
                            </div>
                        </div>

                        <!-- Pestaña Comida -->
                        <div class="tab-pane fade" id="comida" role="tabpanel" aria-labelledby="comida-tab">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <div class="alert alert-success">
                                        🍽️ <strong>Productos de Comida:</strong> <?php echo count($productosPorTipo['comida'] ?? []); ?>
                                    </div>
                                </div>
                                <?php mostrarProductos($productosPorTipo['comida'] ?? []); ?>
                            </div>
                        </div>

                        <!-- Pestaña Cócteles -->
                        <div class="tab-pane fade" id="coctel" role="tabpanel" aria-labelledby="coctel-tab">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <div class="alert alert-info">
                                        🍹 <strong>Cócteles disponibles:</strong> <?php echo count($productosPorTipo['coctel'] ?? []); ?>
                                    </div>
                                </div>
                                <?php mostrarProductos($productosPorTipo['coctel'] ?? []); ?>
                            </div>
                        </div>

                        <!-- Pestaña Licores -->
                        <div class="tab-pane fade" id="licor" role="tabpanel" aria-labelledby="licor-tab">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <div class="alert alert-warning">
                                        🥃 <strong>Licores en stock:</strong> <?php echo count($productosPorTipo['licor'] ?? []); ?>
                                    </div>
                                </div>
                                <?php mostrarProductos($productosPorTipo['licor'] ?? []); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" onclick="location.reload()">
                            🔄 Actualizar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Nuevo Pedido -->
    <div class="modal fade" id="nuevoPedidoModal" tabindex="-1" aria-labelledby="nuevoPedidoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="nuevoPedidoModalLabel">🍽️ Nuevo Pedido</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">📝 Selección de Productos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="mesaPedido" class="form-label">Mesa</label>
                                        <select class="form-select" id="mesaPedido" name="mesa_id" required onchange="validarFormulario()">
                                            <option value="">Seleccionar mesa...</option>
                                            <?php foreach($dataMesas['mesa'] as $mesa): ?>
                                                <?php if ($mesa['estado'] === 'OCUPADA'): ?>
                                                    <option value="<?php echo $mesa['id']; ?>">
                                                        Mesa #<?php echo $mesa['id']; ?> (<?php echo $mesa['estado'] === 'OCUPADA' ? 'Sin atender' : 'Atendiendo'; ?>)
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Barra de búsqueda -->
                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">🔍</span>
                                                <input type="text" class="form-control" id="buscarProductoPedido" placeholder="Buscar productos..." onkeyup="filtrarProductosPedido(this.value)">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <button class="btn btn-outline-secondary btn-sm w-100" onclick="limpiarBusquedaPedido()">
                                                🗑️ Limpiar
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Contador de resultados -->
                                    <div class="alert alert-light border py-2" id="contadorResultadosPedido" style="display: none;">
                                        <small id="textoContadorPedido"></small>
                                    </div>

                                    <!-- Pestañas de categorías -->
                                    <ul class="nav nav-tabs nav-tabs-sm mb-3" id="productTabsPedido" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active small" id="todos-pedido-tab" data-bs-toggle="tab" data-bs-target="#todos-pedido" type="button" role="tab" aria-controls="todos-pedido" aria-selected="true">
                                                🏷️ Todos
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link small" id="comida-pedido-tab" data-bs-toggle="tab" data-bs-target="#comida-pedido" type="button" role="tab" aria-controls="comida-pedido" aria-selected="false">
                                                🍝 Comida
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link small" id="coctel-pedido-tab" data-bs-toggle="tab" data-bs-target="#coctel-pedido" type="button" role="tab" aria-controls="coctel-pedido" aria-selected="false">
                                                🍹 Cócteles
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link small" id="licor-pedido-tab" data-bs-toggle="tab" data-bs-target="#licor-pedido" type="button" role="tab" aria-controls="licor-pedido" aria-selected="false">
                                                🥃 Licores
                                            </button>
                                        </li>
                                    </ul>

                                    <?php 
                                    // Organizar productos por tipo para el modal de pedido
                                    $productos = $dataProductos['producto'] ?? [];
                                    $productosPorTipoPedido = [];
                                    foreach($productos as $producto) {
                                        $tipo = strtolower($producto['tipo']);
                                        $productosPorTipoPedido[$tipo][] = $producto;
                                    }
                                    
                                    // Función para mostrar productos del pedido en formato lista compacta
                                    function mostrarProductosPedido($productos, $categoria = 'todos') {
                                        if (empty($productos)): ?>
                                            <div class="text-center py-3">
                                                <div class="alert alert-info">
                                                    <small>📦 No hay productos disponibles en esta categoría</small>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="row">
                                                <?php foreach($productos as $index => $producto): 
                                                    // Determinar clase de color según el tipo
                                                    $colores = [
                                                        'comida' => 'success',
                                                        'coctel' => 'info',
                                                        'licor' => 'warning'
                                                    ];
                                                    $color = $colores[strtolower($producto['tipo'])] ?? 'secondary';
                                                ?>
                                                <div class="col-md-6 col-sm-12 mb-3">
                                                    <div class="card card-sm border-<?php echo $color; ?> producto-pedido-card"
                                                        data-producto-id="<?php echo $producto['id_producto']; ?>"
                                                        data-nombre="<?php echo strtolower($producto['nombre']); ?>"
                                                        data-categoria="<?php echo strtolower($producto['tipo']); ?>"
                                                        data-codigo="<?php echo $producto['id_producto']; ?>">
                                                        
                                                        <div class="card-body py-2 px-3">
                                                        <div class="row align-items-center">
                                                            <!-- Información del producto -->
                                                            <div class="col-7">
                                                            <h6 class="mb-0 small fw-bold"><?php echo htmlspecialchars($producto['nombre']); ?></h6>
                                                            <small class="text-muted">#<?php echo $producto['id_producto']; ?></small>
                                                            <span class="badge bg-<?php echo $color; ?> badge-sm ms-1">
                                                                $<?php echo number_format($producto['precio'], 0, ',', '.'); ?>
                                                            </span>
                                                            </div>

                                                            <!-- Control de cantidad -->
                                                            <div class="col-5 text-end">
                                                                <div class="input-group input-group-sm justify-content-end">
                                                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                                                            onclick="cambiarCantidad('<?php echo $producto['id_producto']; ?>', -1)">-</button>
                                                                    <input type="number"
                                                                        class="form-control form-control-sm text-center"
                                                                        style="width: 40px; font-size: 11px;"
                                                                        min="0"
                                                                        value="0"
                                                                        id="cantidad-<?php echo $producto['id_producto']; ?>"
                                                                        data-precio="<?php echo $producto['precio']; ?>"
                                                                        data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                                                        onchange="actualizarPedido()">

                                                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                                                            onclick="cambiarCantidad('<?php echo $producto['id_producto']; ?>', 1)">+</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif;
                                    }
                                    ?>

                                    <!-- Contenido de las pestañas -->
                                    <div class="tab-content" id="productTabsContentPedido">
                                        <!-- Pestaña Todos -->
                                        <div class="tab-pane fade show active" id="todos-pedido" role="tabpanel" aria-labelledby="todos-pedido-tab">
                                            <div class="productos-container" style="max-height: 450px; overflow-y: auto;">
                                                <div class="mb-2">
                                                    <div class="alert alert-primary py-2">
                                                        <small>📊 <strong>Total de productos:</strong> <?php echo count($productos); ?></small>
                                                    </div>
                                                </div>
                                                <?php mostrarProductosPedido($productos, 'todos'); ?>
                                            </div>
                                        </div>

                                        <!-- Pestaña Comida -->
                                        <div class="tab-pane fade" id="comida-pedido" role="tabpanel" aria-labelledby="comida-pedido-tab">
                                            <div class="productos-container" style="max-height: 450px; overflow-y: auto;">
                                                <div class="mb-2">
                                                    <div class="alert alert-success py-2">
                                                        <small>🍽️ <strong>Productos de Comida:</strong> <?php echo count($productosPorTipoPedido['comida'] ?? []); ?></small>
                                                    </div>
                                                </div>
                                                <?php mostrarProductosPedido($productosPorTipoPedido['comida'] ?? [], 'comida'); ?>
                                            </div>
                                        </div>

                                        <!-- Pestaña Cócteles -->
                                        <div class="tab-pane fade" id="coctel-pedido" role="tabpanel" aria-labelledby="coctel-pedido-tab">
                                            <div class="productos-container" style="max-height: 450px; overflow-y: auto;">
                                                <div class="mb-2">
                                                    <div class="alert alert-info py-2">
                                                        <small>🍹 <strong>Cócteles disponibles:</strong> <?php echo count($productosPorTipoPedido['coctel'] ?? []); ?></small>
                                                    </div>
                                                </div>
                                                <?php mostrarProductosPedido($productosPorTipoPedido['coctel'] ?? [], 'coctel'); ?>
                                            </div>
                                        </div>

                                        <!-- Pestaña Licores -->
                                        <div class="tab-pane fade" id="licor-pedido" role="tabpanel" aria-labelledby="licor-pedido-tab">
                                            <div class="productos-container" style="max-height: 450px; overflow-y: auto;">
                                                <div class="mb-2">
                                                    <div class="alert alert-warning py-2">
                                                        <small>🥃 <strong>Licores en stock:</strong> <?php echo count($productosPorTipoPedido['licor'] ?? []); ?></small>
                                                    </div>
                                                </div>
                                                <?php mostrarProductosPedido($productosPorTipoPedido['licor'] ?? [], 'licor'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">🛒 Resumen del Pedido</h5>
                                </div>
                                <div class="card-body">
                                    <!-- Resumen por categorías -->
                                    <div class="mb-3">
                                        <div class="card card-sm">
                                            <div class="card-header bg-success text-white py-1">
                                                <small>🍽️ Cocina</small>
                                            </div>
                                            <div class="card-body py-2">
                                                <div id="resumenCocina">
                                                    <small class="text-muted">Sin productos</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="card card-sm">
                                            <div class="card-header bg-info text-white py-1">
                                                <small>🍹 Barra</small>
                                            </div>
                                            <div class="card-body py-2">
                                                <div id="resumenBarra">
                                                    <small class="text-muted">Sin productos</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="card card-sm">
                                            <div class="card-header bg-warning text-white py-1">
                                                <small>🥃 Licores</small>
                                            </div>
                                            <div class="card-body py-2">
                                                <div id="resumenLicor">
                                                    <small class="text-muted">Sin productos</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Total General: $<span id="totalPedido">0</span></strong>
                                    </div>
                                    
                                    <form id="formPedido" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                        <input type="hidden" name="accion" value="crear_pedido">
                                        <input type="hidden" id="mesaSeleccionada" name="mesa_id" value="">
                                        <input type="hidden" id="meseroSeleccionado" name="mesero_id" value="<?php echo htmlspecialchars($id_mesero); ?>">
                                        <input type="hidden" id="productosCocina" name="productos_cocina" value="">
                                        <input type="hidden" id="productosBarra" name="productos_barra" value="">
                                        <input type="hidden" id="productosLicor" name="productos_licor" value="">
                                        
                                        <div class="d-grid">
                                            <button type="button" class="btn btn-success" id="btnGuardarPedido" onclick="confirmarGuardarPedido()">
                                                💾 Guardar Pedido
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación -->
    <div class="modal fade" id="confirmarPedidoModal" tabindex="-1" aria-labelledby="confirmarPedidoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmarPedidoModalLabel">✅ Confirmar Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h6>📋 Resumen del pedido:</h6>
                        <div id="resumenConfirmacion"></div>
                    </div>
                    <p>¿Está seguro de que desea guardar este pedido?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnConfirmarPedido" onclick="guardarPedidoConfirmado()">
                        💾 Confirmar y Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Pedidos Activos -->
    <div class="modal fade" id="pedidosActivosModal" tabindex="-1" aria-labelledby="pedidosActivosModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="pedidosActivosModalLabel">📋 Pedidos Activos</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">📋 Gestión de Pedidos</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Pedido</th>
                                            <th>Mesa</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($pedidos as $pedido): ?>
                                            <?php if ($pedido['estado_general'] !== 'pagado'): ?>
                                                <tr>
                                                    <td>
                                                        <strong>#<?php echo $pedido['id']; ?></strong>
                                                        <small class="d-block text-muted">
                                                            <?php echo htmlspecialchars(substr($pedido['fecha_hora'], 0, 16)); ?>
                                                        </small>
                                                    </td>
                                                    <td>#<?php echo $pedido['mesa_numero']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getClaseEstado($pedido['estado_general']); ?>">
                                                            <?php echo getEstadoConIcono($pedido['estado_general']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($pedido['estado_general'] === 'pendiente'): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="accion" value="cambiar_estado_pedido">
                                                                    <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                                                    <input type="hidden" name="nuevo_estado" value="servir">
                                                                    <button type="submit" class="btn btn-outline-success btn-sm">
                                                                        🍽️ Listo
                                                                    </button>
                                                                </form>
                                                            <?php elseif ($pedido['estado_general'] === 'servir'): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="accion" value="cambiar_estado_pedido">
                                                                    <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                                                    <input type="hidden" name="nuevo_estado" value="entregado">
                                                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                                                        ✅ Entregar
                                                                    </button>
                                                                </form>
                                                            <?php elseif ($pedido['estado_general'] === 'entregado'): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="accion" value="cambiar_estado_pedido">
                                                                    <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                                                    <input type="hidden" name="nuevo_estado" value="pagado">
                                                                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                                        💲 Cobrar
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        
                                        <?php if (count(array_filter($pedidos, function($p) { return $p['estado_general'] !== 'pagado'; })) === 0): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">
                                                    No hay pedidos activos
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Notificaciones -->
    <div class="modal fade" id="notificacionesModal" tabindex="-1" aria-labelledby="notificacionesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="notificacionesModalLabel">🔔 Historial de Notificaciones</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($notificacionesTickets)): ?>
                        <div class="text-center">
                            <div class="alert alert-info">
                                <h4>📋 Sin notificaciones</h4>
                                <p>No tienes notificaciones de tickets listos en este momento.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="tablaNotificaciones">
                                <thead>
                                    <tr>
                                        <th>ID Pedido</th>
                                        <th>Mesa</th>
                                        <th>Fecha y Hora</th>
                                        <th>Dependencia</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($notificacionesTickets as $notificacion): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo htmlspecialchars($notificacion['pedido_id']); ?></strong>
                                        </td>
                                        <td>
                                            <strong>🏠 Mesa <?php echo htmlspecialchars($notificacion['mesa_numero']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($notificacion['fecha_hora']))); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $notificacion['dependencia'] === 'barra' ? 'info' : ($notificacion['dependencia'] === 'cocina' ? 'success' : 'warning'); ?>">
                                                <?php echo ucfirst(htmlspecialchars($notificacion['dependencia'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $notificacion['estado_ticket'] === 'listo' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($notificacion['estado_ticket'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="accion" value="entregar_ticket">
                                                <input type="hidden" name="pedido_id" value="<?php echo $notificacion['pedido_id']; ?>">
                                                <input type="hidden" name="dependencia" value="<?php echo $notificacion['dependencia']; ?>">
                                                <button class="btn btn-sm btn-success" type="submit">
                                                    🎉 Entregar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="location.reload()">
                        🔄 Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>

        // Función para filtrar productos en tiempo real
        function filtrarProductos(termino) {
            const cards = document.querySelectorAll('.producto-card');
            const terminoLower = termino.toLowerCase().trim();
            const contadorDiv = document.getElementById('contadorResultados');
            const textoContador = document.getElementById('textoContador');
            
            let productosVisibles = 0;
            let totalProductos = cards.length;
            
            // Si no hay término de búsqueda, mostrar todos
            if (terminoLower === '') {
                cards.forEach(card => {
                    card.closest('.col-lg-4').style.display = 'block';
                });
                contadorDiv.style.display = 'none';
                return;
            }
            
            // Filtrar productos
            cards.forEach(card => {
                const nombre = card.querySelector('.card-title').textContent.toLowerCase();
                const codigo = card.querySelector('.fw-bold').textContent.toLowerCase();
                const categoria = card.querySelector('.badge').textContent.toLowerCase();
                
                // Buscar en nombre, código y categoría
                const coincide = nombre.includes(terminoLower) || 
                                codigo.includes(terminoLower) || 
                                categoria.includes(terminoLower);
                
                if (coincide) {
                    card.closest('.col-lg-4').style.display = 'block';
                    productosVisibles++;
                    
                    // Resaltar el término encontrado
                    resaltarTermino(card, terminoLower);
                } else {
                    card.closest('.col-lg-4').style.display = 'none';
                }
            });
            
            // Mostrar contador de resultados
            contadorDiv.style.display = 'block';
            if (productosVisibles === 0) {
                textoContador.innerHTML = `❌ No se encontraron productos que coincidan con "<strong>${termino}</strong>"`;
                contadorDiv.className = 'alert alert-warning border';
            } else {
                textoContador.innerHTML = `✅ Mostrando <strong>${productosVisibles}</strong> de <strong>${totalProductos}</strong> productos para "<strong>${termino}</strong>"`;
                contadorDiv.className = 'alert alert-success border';
            }
        }

        // Función para resaltar el término buscado
        function resaltarTermino(card, termino) {
            const elementos = card.querySelectorAll('.card-title, .fw-bold, .badge');
            
            elementos.forEach(elemento => {
                const textoOriginal = elemento.textContent;
                const regex = new RegExp(`(${termino})`, 'gi');
                
                if (regex.test(textoOriginal)) {
                    elemento.innerHTML = textoOriginal.replace(regex, '<mark>$1</mark>');
                }
            });
        }

        // Función para limpiar la búsqueda
        function limpiarBusqueda() {
            const input = document.getElementById('buscarProducto');
            input.value = '';
            filtrarProductos('');
            
            // Limpiar cualquier resaltado
            const marks = document.querySelectorAll('.producto-card mark');
            marks.forEach(mark => {
                const texto = mark.textContent;
                mark.parentNode.replaceChild(document.createTextNode(texto), mark);
            });
        }

        // Función para ejecutar búsqueda avanzada
        function ejecutarBusquedaAvanzada() {
            const nombre = document.getElementById('busq_nombre').value.toLowerCase();
            const codigo = document.getElementById('busq_codigo').value.toLowerCase();
            const categoria = document.getElementById('busq_categoria').value.toLowerCase();
            const precioMin = parseFloat(document.getElementById('busq_precio_min').value) || 0;
            const precioMax = parseFloat(document.getElementById('busq_precio_max').value) || Infinity;
            
            const cards = document.querySelectorAll('.producto-card');
            let productosVisibles = 0;
            
            cards.forEach(card => {
                const nombreProducto = card.querySelector('.card-title').textContent.toLowerCase();
                const codigoProducto = card.querySelector('.fw-bold').textContent.toLowerCase();
                const categoriaProducto = card.querySelector('.badge').textContent.toLowerCase();
                const precioTexto = card.querySelector('.fs-4').textContent.replace(/[^\d]/g, '');
                const precioProducto = parseFloat(precioTexto) || 0;
                
                const coincideNombre = !nombre || nombreProducto.includes(nombre);
                const coincideCodigo = !codigo || codigoProducto.includes(codigo);
                const coincideCategoria = !categoria || categoriaProducto.includes(categoria);
                const coincidePrecio = precioProducto >= precioMin && precioProducto <= precioMax;
                
                if (coincideNombre && coincideCodigo && coincideCategoria && coincidePrecio) {
                    card.closest('.col-lg-4').style.display = 'block';
                    productosVisibles++;
                } else {
                    card.closest('.col-lg-4').style.display = 'none';
                }
            });
            
            // Mostrar resultado
            const contadorDiv = document.getElementById('contadorResultados');
            const textoContador = document.getElementById('textoContador');
            contadorDiv.style.display = 'block';
            
            if (productosVisibles === 0) {
                textoContador.innerHTML = '❌ No se encontraron productos con los criterios especificados';
                contadorDiv.className = 'alert alert-warning border';
            } else {
                textoContador.innerHTML = `✅ Encontrados <strong>${productosVisibles}</strong> productos con los criterios especificados`;
                contadorDiv.className = 'alert alert-success border';
            }
            
            // Cerrar modal de búsqueda avanzada
            const busquedaModal = bootstrap.Modal.getInstance(document.getElementById('busquedaAvanzadaModal'));
            busquedaModal.hide();
        }

        // Función para búsqueda rápida por categoría
        function busquedaRapidaPorCategoria(categoria) {
            const input = document.getElementById('buscarProducto');
            input.value = categoria;
            filtrarProductos(categoria);
    }



        // Función para cambiar estado de mesa
        function cambiarEstado(idMesa, nuevoEstado) {
            let mensaje = '';
            if (nuevoEstado === 'ATENDIENDO') {
                mensaje = '¿Comenzar a atender la Mesa #' + idMesa + '?';
            } else {
                mensaje = '¿Estás seguro de cambiar el estado de la Mesa #' + idMesa + '?';
            }
            
            if (confirm(mensaje)) {
                // Crear formulario para enviar datos
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                // Crear campos del formulario
                const accionField = document.createElement('input');
                accionField.type = 'hidden';
                accionField.name = 'accion';
                accionField.value = 'actualizar_estado';
                
                const mesaField = document.createElement('input');
                mesaField.type = 'hidden';
                mesaField.name = 'id_mesa';
                mesaField.value = idMesa;
                
                const estadoField = document.createElement('input');
                estadoField.type = 'hidden';
                estadoField.name = 'nuevo_estado';
                estadoField.value = nuevoEstado;
                
                // Agregar campos al formulario
                form.appendChild(accionField);
                form.appendChild(mesaField);
                form.appendChild(estadoField);
                
                // Agregar formulario al documento y enviarlo
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Filtros de búsqueda
        document.getElementById('buscarMesa')?.addEventListener('input', function() {
            const busqueda = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tablaMesas tbody tr');
            
            filas.forEach(fila => {
                const mesa = fila.querySelector('td:first-child').textContent.toLowerCase();
                if (mesa.includes(busqueda)) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        });

        document.getElementById('filtroEstado')?.addEventListener('change', function() {
            const filtro = this.value;
            const filas = document.querySelectorAll('#tablaMesas tbody tr');
            
            filas.forEach(fila => {
                if (filtro === '') {
                    fila.style.display = '';
                } else {
                    const badge = fila.querySelector('.badge');
                    const estado = badge.textContent.trim();
                    
                    if (estado.includes('Sin atender') && filtro === 'OCUPADA' ||
                        estado.includes('Atendiendo') && filtro === 'ATENDIENDO') {
                        fila.style.display = '';
                    } else {
                        fila.style.display = 'none';
                    }
                }
            });
        });


        // Filtrar productos en el modal de pedidos
        document.getElementById('formPedido')?.addEventListener('submit', function(e) {
            const productos = Array.from(this.querySelectorAll('input[name="productos[]"]:checked')).map(el => el.value);
            if (productos.length === 0) {
                e.preventDefault();
                alert('Debe seleccionar al menos un producto');
            }
        });

        // Búsqueda rápida de productos en el modal de pedidos
        document.getElementById('buscarProductoPedido')?.addEventListener('input', function() {
            const termino = this.value.toLowerCase();
            const checkboxes = document.querySelectorAll('.producto-checkbox');
            
            checkboxes.forEach(checkbox => {
                const label = checkbox.nextElementSibling;
                const texto = label.textContent.toLowerCase();
                
                if (texto.includes(termino)) {
                    checkbox.closest('.form-check').style.display = 'block';
                } else {
                    checkbox.closest('.form-check').style.display = 'none';
                }
            });
        });


        // Funciones para manejar el pedido
        
        // Variables globales para el pedido
        let pedidoActual = {
            cocina: {},
            barra: {},
            licor: {}
        };
        let totalPedido = 0;

        // Función para cambiar cantidad
        function cambiarCantidad(idProducto, cambio) {
            const input = document.getElementById('cantidad-' + idProducto);
            let cantidad = parseInt(input.value) || 0;
            cantidad += cambio;
            if (cantidad < 0) cantidad = 0;
            input.value = cantidad;
            actualizarPedido();
        }

        // Función para actualizar el resumen del pedido
        function actualizarPedido() {
            // Mapeo de categorías
            const mapaCategorias = {
                'comida': 'cocina',
                'coctel': 'barra',
                'licor': 'licor'
            };

            // Reiniciar pedido
            pedidoActual = {
                cocina: {},
                barra: {},
                licor: {}
            };
            totalPedido = 0;
            
            // Obtener todos los inputs de cantidad
            const inputs = document.querySelectorAll('input[id^="cantidad-"]');
            
            inputs.forEach(input => {
                const cantidad = parseInt(input.value) || 0;
                if (cantidad > 0) {
                    const idProducto = input.id.replace('cantidad-', '');
                    const precio = parseFloat(input.dataset.precio);
                    const nombre = input.dataset.nombre;
                    
                    // Obtener categoría
                    const productoCard = input.closest('.producto-pedido-card');
                    const categoriaProducto = productoCard.dataset.categoria;
                    const categoria = mapaCategorias[categoriaProducto] || categoriaProducto;
                    
                    // Obtener detalle si existe
                    const detalleInput = document.getElementById(`detalle-input-${categoria}-${idProducto}`);
                    const detalle = detalleInput ? detalleInput.value : '';
                    
                    const subtotal = cantidad * precio;
                    
                    // Agregar al pedido según categoría
                    if (pedidoActual[categoria]) {
                        pedidoActual[categoria][idProducto] = {
                            cod: idProducto,
                            cant: cantidad,
                            detalle: detalle,
                            precio: precio,
                            nombre: nombre,
                            subtotal: subtotal
                        };
                    }
                    
                    totalPedido += subtotal;
                }
            });
            
            // Actualizar resúmenes por categoría
            actualizarResumenCategoria('cocina', 'resumenCocina');
            actualizarResumenCategoria('barra', 'resumenBarra');
            actualizarResumenCategoria('licor', 'resumenLicor');
            
            // Actualizar total
            document.getElementById('totalPedido').textContent = totalPedido.toLocaleString();
            
            // Actualizar campos ocultos
            actualizarCamposOcultos();
            
            // Validar formulario
            validarFormulario();
        }

        // Agregar evento al formulario para actualizar campos antes de enviar
        document.getElementById('formPedido').addEventListener('submit', function(e) {
            // Prevenir envío automático
            e.preventDefault();
            
            // Actualizar los campos ocultos con los datos más recientes
            actualizarPedido();
            
            // Continuar con el envío
            this.submit();
        });

        // Función para actualizar el resumen por categoría
        function actualizarResumenCategoria(categoria, elementoId) {
            const elemento = document.getElementById(elementoId);
            let resumenHTML = '';
            
            if (Object.keys(pedidoActual[categoria]).length === 0) {
                resumenHTML = '<small class="text-muted">Sin productos</small>';
            } else {
                for (const [id, producto] of Object.entries(pedidoActual[categoria])) {
                    resumenHTML += `
                        <div class="d-flex justify-content-between mb-1">
                            <div>
                                <small><strong>${producto.nombre}</strong> (${producto.cant})</small>
                                <small class="text-muted">$${producto.subtotal.toLocaleString()}</small>
                            </div>
                            <button class="btn btn-sm btn-link p-0" 
                                    onclick="toggleDetalle('${categoria}', '${id}')">
                                ✏️
                            </button>
                        </div>
                        <div class="detalle-container mb-2" id="detalle-container-${categoria}-${id}" style="display: none;">
                            <input type="text" 
                                class="form-control form-control-sm" 
                                placeholder="Detalle (ej: sin cebolla)"
                                id="detalle-input-${categoria}-${id}"
                                value="${producto.detalle || ''}"
                                onchange="actualizarDetalle('${categoria}', '${id}', this.value)">
                        </div>
                    `;
                }
            }
            
            elemento.innerHTML = resumenHTML;
        }

        // Función para mostrar/ocultar el campo de detalle
        function toggleDetalle(categoria, idProducto) {
            const container = document.getElementById(`detalle-container-${categoria}-${idProducto}`);
            container.style.display = container.style.display === 'none' ? 'block' : 'none';
        }

        // Función para actualizar el detalle de un producto
        function actualizarDetalle(categoria, idProducto, detalle) {
            if (pedidoActual[categoria] && pedidoActual[categoria][idProducto]) {
                pedidoActual[categoria][idProducto].detalle = detalle;
                actualizarCamposOcultos();
            }
        }

        // Función para actualizar campos ocultos antes de enviar
        function actualizarCamposOcultos() {
            document.getElementById('productosCocina').value = JSON.stringify(pedidoActual.cocina);
            document.getElementById('productosBarra').value = JSON.stringify(pedidoActual.barra);
            document.getElementById('productosLicor').value = JSON.stringify(pedidoActual.licor);
            document.getElementById('mesaSeleccionada').value = document.getElementById('mesaPedido').value;
        }


        // Función para validar el formulario
        function validarFormulario() {
            const mesa = document.getElementById('mesaPedido').value;
            //const mesero = document.getElementById('meseroAsignado').value;
            const tieneProductos = Object.keys(pedidoActual.cocina).length > 0 || 
                                Object.keys(pedidoActual.barra).length > 0 || 
                                Object.keys(pedidoActual.licor).length > 0;
            const btnGuardar = document.getElementById('btnGuardarPedido');
            
            //if (mesa && mesero && tieneProductos) {
            if (mesa && tieneProductos) {
                btnGuardar.disabled = false;
            } else {
                btnGuardar.disabled = true;
            }
        }

        // Función para confirmar el pedido
        function confirmarGuardarPedido() {
            //actualizarPedido();
            let resumenConfirmacion = '';
            const mesa = document.getElementById('mesaPedido').options[document.getElementById('mesaPedido').selectedIndex].text;
            // const mesero = document.getElementById('meseroAsignado').options[document.getElementById('meseroAsignado').selectedIndex].text;
            
            resumenConfirmacion += `<strong>Mesa:</strong> ${mesa}<br>`;
            //resumenConfirmacion += `<strong>Mesero:</strong> ${mesero}<br><br>`;
            
            // Mostrar productos por categoría
            const categorias = [
                { nombre: 'Cocina', key: 'cocina', icon: '🍽️' },
                { nombre: 'Barra', key: 'barra', icon: '🍹' },
                { nombre: 'Licores', key: 'licor', icon: '🥃' }
            ];
            
            categorias.forEach(cat => {
                if (Object.keys(pedidoActual[cat.key]).length > 0) {
                    resumenConfirmacion += `<strong>${cat.icon} ${cat.nombre}:</strong><br>`;
                    for (const [id, producto] of Object.entries(pedidoActual[cat.key])) {
                        resumenConfirmacion += `• ${producto.nombre} (${producto.cant}) - $${producto.subtotal.toLocaleString()}`;
                        if (producto.detalle) {
                            resumenConfirmacion += ` - ${producto.detalle}`;
                        }
                        resumenConfirmacion += '<br>';
                    }
                    resumenConfirmacion += '<br>';
                }
            });
            
            resumenConfirmacion += `<strong>Total: $${totalPedido.toLocaleString()}</strong>`;
            
            document.getElementById('resumenConfirmacion').innerHTML = resumenConfirmacion;
            
            const modalConfirmacion = new bootstrap.Modal(document.getElementById('confirmarPedidoModal'));
            modalConfirmacion.show();
            actualizarPedido();
        }

        // Función para guardar el pedido confirmado
        function guardarPedidoConfirmado() {
            // Obtener el formulario
            const form = document.getElementById('formPedido');
            
            // Deshabilitar el botón para evitar múltiples clics
            const btnConfirmar = document.getElementById('btnConfirmarPedido');
            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
            
            try {
                // Enviar el formulario
                form.submit();
            } catch (e) {
                console.error('Error al enviar el formulario:', e);
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = '💾 Confirmar y Guardar';
                alert('Error al enviar el pedido: ' + e.message);
            }
        }

        // Función para filtrar productos en el pedido
        function filtrarProductosPedido(texto) {
            const productos = document.querySelectorAll('.producto-pedido-card');
            const contador = document.getElementById('contadorResultadosPedido');
            const textoContador = document.getElementById('textoContadorPedido');
            let productosVisibles = 0;
            
            if (texto.trim() === '') {
                productos.forEach(producto => {
                    producto.style.display = 'block';
                    productosVisibles++;
                });
                contador.style.display = 'none';
            } else {
                const textoBusqueda = texto.toLowerCase();
                
                productos.forEach(producto => {
                    const nombre = producto.dataset.nombre;
                    const categoria = producto.dataset.categoria;
                    const codigo = producto.dataset.codigo;
                    
                    if (nombre.includes(textoBusqueda) || 
                        categoria.includes(textoBusqueda) || 
                        codigo.includes(textoBusqueda)) {
                        producto.style.display = 'block';
                        productosVisibles++;
                    } else {
                        producto.style.display = 'none';
                    }
                });
                
                contador.style.display = 'block';
                textoContador.textContent = `Se encontraron ${productosVisibles} productos`;
            }
        }

        // Función para limpiar búsqueda del pedido
        function limpiarBusquedaPedido() {
            document.getElementById('buscarProductoPedido').value = '';
            filtrarProductosPedido('');
        }

        // Función para resetear el modal cuando se cierre
        document.getElementById('nuevoPedidoModal').addEventListener('hidden.bs.modal', function () {
            // Limpiar formulario
            document.getElementById('mesaPedido').value = '';
            document.getElementById('detallePedido').value = '';
            
            // Limpiar cantidades
            const inputs = document.querySelectorAll('input[id^="cantidad-"]');
            inputs.forEach(input => {
                input.value = 0;
            });
            
            // Limpiar búsqueda
            limpiarBusquedaPedido();
            
            // Resetear variables
            pedidoActual = {};
            totalPedido = 0;
            
            // Actualizar resumen
            actualizarPedido();
            
            // Volver a la pestaña "Todos"
            document.getElementById('todos-pedido-tab').click();
        });

        // Listeners para actualizar el pedido
        document.getElementById('mesaPedido').addEventListener('change', validarFormulario);
        document.getElementById('detallePedido').addEventListener('input', function() {
            document.getElementById('detalleHidden').value = this.value;
        });

        // Auto-refresh cada 30 segundos para mantener la información actualizada
        setInterval(function() {
            // Solo hacer refresh si no hay modales abiertos
            if (!document.querySelector('.modal.show')) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>