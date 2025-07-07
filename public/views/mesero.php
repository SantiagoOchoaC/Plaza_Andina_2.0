<?php
include_once 'conexion.php';

// Iniciar sesión
session_start();

$id_mesero = $_SESSION['mesero_id'];
$con = conectar();

// Procesar creación de pedido y cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'crear_pedido') {
        $mesa_id = intval($_POST['mesa_id']);
        $productos = $_POST['productos'] ?? [];
        $detalle = trim($_POST['detalle'] ?? '');
        
        // Validar que la mesa pertenece al mesero actual
        $stmt = $con->prepare("SELECT id FROM mesa WHERE id = ? AND mesero = ?");
        $stmt->bind_param("ii", $mesa_id, $id_mesero);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0 && !empty($productos)) {
            // Convertir array de productos a string separado por comas
            $productos_str = implode(',', array_map('intval', $productos));
            
            // Insertar nuevo pedido
            $stmt_insert = $con->prepare("INSERT INTO pedido (productos, estado, mesero_id, mesa_id, detalle) VALUES (?, 'pendiente', ?, ?, ?)");
            $stmt_insert->bind_param("siis", $productos_str, $id_mesero, $mesa_id, $detalle);
            
            if ($stmt_insert->execute()) {
                $_SESSION['mensaje'] = "Pedido creado correctamente para la Mesa #$mesa_id";
                $_SESSION['tipo_mensaje'] = "success";
                
                // Actualizar estado de la mesa a "ATENDIENDO"
                $stmt_update = $con->prepare("UPDATE mesa SET estado = 'ATENDIENDO' WHERE id = ?");
                $stmt_update->bind_param("i", $mesa_id);
                $stmt_update->execute();
                $stmt_update->close();
            } else {
                $_SESSION['mensaje'] = "Error al crear el pedido";
                $_SESSION['tipo_mensaje'] = "danger";
            }
            $stmt_insert->close();
        } else {
            $_SESSION['mensaje'] = "Datos inválidos o mesa no asignada";
            $_SESSION['tipo_mensaje'] = "warning";
        }
        $stmt->close();
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Procesar cambio de estado de pedido
    if ($_POST['accion'] === 'cambiar_estado_pedido') {
        $pedido_id = intval($_POST['pedido_id']);
        $nuevo_estado = $_POST['nuevo_estado'];
        
        // Validar que el pedido pertenece al mesero actual
        $stmt = $con->prepare("SELECT id_pedido FROM pedido WHERE id_pedido = ? AND mesero_id = ?");
        $stmt->bind_param("ii", $pedido_id, $id_mesero);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            // Actualizar estado del pedido
            $stmt_update = $con->prepare("UPDATE pedido SET estado = ? WHERE id_pedido = ?");
            $stmt_update->bind_param("si", $nuevo_estado, $pedido_id);
            
            if ($stmt_update->execute()) {
                $_SESSION['mensaje'] = "Estado del pedido actualizado correctamente";
                $_SESSION['tipo_mensaje'] = "success";
                
                // Si el estado es "pagado", liberar la mesa
                if ($nuevo_estado === 'pagado') {
                    $stmt_mesa = $con->prepare("SELECT mesa_id FROM pedido WHERE id_pedido = ?");
                    $stmt_mesa->bind_param("i", $pedido_id);
                    $stmt_mesa->execute();
                    $mesa_result = $stmt_mesa->get_result();
                    
                    if ($mesa_result->num_rows > 0) {
                        $mesa_data = $mesa_result->fetch_assoc();
                        $mesa_id = $mesa_data['mesa_id'];
                        
                        $stmt_liberar = $con->prepare("UPDATE mesa SET estado = 'DISPONIBLE', mesero = NULL, fecha_asignacion = NULL WHERE id = ?");
                        $stmt_liberar->bind_param("i", $mesa_id);
                        $stmt_liberar->execute();
                        $stmt_liberar->close();
                    }
                    $stmt_mesa->close();
                }
            } else {
                $_SESSION['mensaje'] = "Error al actualizar el estado del pedido";
                $_SESSION['tipo_mensaje'] = "danger";
            }
            $stmt_update->close();
        } else {
            $_SESSION['mensaje'] = "No tienes permisos para modificar este pedido";
            $_SESSION['tipo_mensaje'] = "warning";
        }
        $stmt->close();
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Función para obtener los pedidos del mesero actual
function obtenerPedidos($id_mesero) {
    $con = conectar();
    
    $query = "SELECT p.*, 
              GROUP_CONCAT(pr.nombre SEPARATOR ', ') as nombres_productos,
              m.id as mesa_numero
              FROM pedido p
              LEFT JOIN producto pr ON FIND_IN_SET(pr.id_producto, p.productos)
              LEFT JOIN mesa m ON p.mesa_id = m.id
              WHERE p.mesero_id = ?
              GROUP BY p.id_pedido
              ORDER BY 
                CASE p.estado
                    WHEN 'pendiente' THEN 1
                    WHEN 'servir' THEN 2
                    WHEN 'entregado' THEN 3
                    WHEN 'pagado' THEN 4
                    ELSE 5
                END,
                p.id_pedido DESC";
    
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

// Función auxiliar para formatear precio (opcional)
function formatearPrecio($precio) {
    return '$' . number_format($precio, 0, ',', '.');
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

                    <div class="feature-card" data-bs-toggle="modal" data-bs-target="#pedidoModal">
                        <div class="feature-icon">📦</div>
                        <h3 class="feature-title">Mis Pedidos</h3>
                        <p class="feature-description">Ver y gestionar mis pedidos activos</p>
                        <button class="btn-dashboard">Ver/Tomar Pedidos</button>
                    
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
                        <!-- Search and Filter Section -->
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

                        <!-- Table Section -->
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
                        // Asumiendo que $dataProductos contiene los productos obtenidos de la BD
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


    <!-- Modal para Toma de Pedidos -->
    <div class="modal fade" id="pedidoModal" tabindex="-1" aria-labelledby="pedidoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="pedidoModalLabel">📝 Toma de Pedidos</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">🍽️ Nuevo Pedido</h5>
                                </div>
                                <div class="card-body">
                                    <form id="formPedido" method="POST">
                                        <input type="hidden" name="accion" value="crear_pedido">
                                        
                                        <div class="mb-3">
                                            <label for="mesaPedido" class="form-label">Mesa</label>
                                            <select class="form-select" id="mesaPedido" name="mesa_id" required>
                                                <option value="">Seleccionar mesa...</option>
                                                <?php foreach($dataMesas['mesa'] as $mesa): ?>
                                                    <?php if ($mesa['estado'] === 'OCUPADA' || $mesa['estado'] === 'ATENDIENDO'): ?>
                                                        <option value="<?php echo $mesa['id']; ?>">
                                                            Mesa #<?php echo $mesa['id']; ?> (<?php echo $mesa['estado'] === 'OCUPADA' ? 'Sin atender' : 'Atendiendo'; ?>)
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Productos</label>
                                            <div class="productos-container border p-2" style="max-height: 200px; overflow-y: auto;">
                                                <?php foreach($dataProductos['producto'] as $producto): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input producto-checkbox" type="checkbox" 
                                                            name="productos[]" value="<?php echo $producto['id_producto']; ?>" 
                                                            id="prod-<?php echo $producto['id_producto']; ?>">
                                                        <label class="form-check-label" for="prod-<?php echo $producto['id_producto']; ?>">
                                                            <?php echo htmlspecialchars($producto['nombre']); ?> 
                                                            <span class="text-muted">($<?php echo number_format($producto['precio'], 0, ',', '.'); ?>)</span>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <small class="text-muted">Seleccione los productos del pedido</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="detallePedido" class="form-label">Detalles adicionales</label>
                                            <textarea class="form-control" id="detallePedido" name="detalle" rows="3" 
                                                    placeholder="Ej: Sin sal, bien cocido, sin hielo, etc."></textarea>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success">
                                                💾 Guardar Pedido
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">📋 Pedidos Activos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
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
                                                    <?php if ($pedido['estado'] !== 'pagado'): ?>
                                                        <tr>
                                                            <td>
                                                                <strong>#<?php echo $pedido['id_pedido']; ?></strong>
                                                                <small class="d-block text-muted">
                                                                    <?php echo htmlspecialchars(substr($pedido['nombres_productos'], 0, 30)); ?>...
                                                                </small>
                                                            </td>
                                                            <td>#<?php echo $pedido['mesa_numero']; ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo getClaseEstado($pedido['estado']); ?>">
                                                                    <?php echo getEstadoConIcono($pedido['estado']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    <?php if ($pedido['estado'] === 'pendiente'): ?>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="accion" value="cambiar_estado_pedido">
                                                                            <input type="hidden" name="pedido_id" value="<?php echo $pedido['id_pedido']; ?>">
                                                                            <input type="hidden" name="nuevo_estado" value="servir">
                                                                            <button type="submit" class="btn btn-outline-success btn-sm">
                                                                                🍽️ Listo
                                                                            </button>
                                                                        </form>
                                                                    <?php elseif ($pedido['estado'] === 'servir'): ?>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="accion" value="cambiar_estado_pedido">
                                                                            <input type="hidden" name="pedido_id" value="<?php echo $pedido['id_pedido']; ?>">
                                                                            <input type="hidden" name="nuevo_estado" value="entregado">
                                                                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                                                                ✅ Entregar
                                                                            </button>
                                                                        </form>
                                                                    <?php elseif ($pedido['estado'] === 'entregado'): ?>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="accion" value="cambiar_estado_pedido">
                                                                            <input type="hidden" name="pedido_id" value="<?php echo $pedido['id_pedido']; ?>">
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
                                                
                                                <?php if (empty(array_filter($pedidos, function($p) { return $p['estado'] !== 'pagado'; }))): ?>
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
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para agregar producto al pedido

        function agregarAlPedido(idProducto) {
            // Implementar lógica para agregar producto al pedido
            console.log('Agregando producto al pedido:', idProducto);
            
            // Mostrar confirmación temporal
            Swal.fire({
                title: '✅ Producto agregado',
                text: 'El producto se ha agregado al pedido actual',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            
            // Aquí puedes llamar a tu función PHP o hacer una petición AJAX
            // para agregar el producto al pedido actual
        }

        function verDetalleProducto(idProducto) {
            // Implementar lógica para mostrar detalles del producto
            console.log('Ver detalles del producto:', idProducto);
            
            // Aquí puedes abrir otro modal con detalles del producto
            // o redirigir a una página de detalles
        }

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


        // Función para ver detalle de pedido
        function verDetallePedido(pedidoId) {
            fetch(`obtener_detalle_pedido.php?id=${pedidoId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detallePedidoContent').innerHTML = html;
                    document.getElementById('pedidoNumero').textContent = `#${pedidoId}`;
                    new bootstrap.Modal(document.getElementById('detallePedidoModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar el detalle del pedido');
                });
        }

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