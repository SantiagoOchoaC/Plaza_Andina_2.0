<?php
// Conexón a la base de datos
require 'conexion.php';

// Iniciar la sesión, permitiendo el uso de variables de sesión
session_start();

// La conexión a la base de datos
$conn = conectar();

// Función para obtener pedidos por estado específico
function obtenerPedidosPorEstado($estado) {
    global $conn;
    
    $sql = "SELECT 
                pg.id,
                pg.fecha_hora,
                pg.id_mesa,
                pg.estado_barra,
                pg.total,
                e.nombre as nombre_mesero
            FROM pedido_general pg
            INNER JOIN empleado e ON pg.id_mesero = e.identificación
            WHERE pg.estado_barra = ? AND EXISTS (
                SELECT 1 FROM ticket_barra tb WHERE tb.pedido_id = pg.id
            )
            ORDER BY pg.fecha_hora ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $estado);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }
    
    return $pedidos;
}

// Función para obtener detalles de productos de un pedido
function obtenerDetallesPedido($pedido_id) {
    global $conn;
    
    $sql = "SELECT 
                tb.cod,
                tb.cant,
                tb.detalle,
                p.nombre as nombre_producto,
                p.precio
            FROM ticket_barra tb
            INNER JOIN producto p ON tb.cod = p.id_producto
            WHERE tb.pedido_id = ?
            ORDER BY p.nombre";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $detalles = [];
    while ($row = $result->fetch_assoc()) {
        $detalles[] = $row;
    }
    
    return $detalles;
}

// Función para mostrar pedidos pendientes
function mostrarPedidosPendientes() {
    $pedidos = obtenerPedidosPorEstado('pendiente');
    
    if (empty($pedidos)) {
        echo '<div class="text-center">
                <div class="alert alert-info">
                    <h5>⏳ No hay tickets pendientes</h5>
                    <p>Todos los tickets están siendo atendidos.</p>
                </div>
              </div>';
        return;
    }
    
    foreach ($pedidos as $pedido) {
        $tiempo_transcurrido = calcularTiempoTranscurrido($pedido['fecha_hora']);
        $clase_urgencia = obtenerClaseUrgencia($tiempo_transcurrido);
        $detalles = obtenerDetallesPedido($pedido['id']);
        
        echo '<div class="col-md-6 mb-3">
                <div class="card border-warning ' . $clase_urgencia . '">
                    <div class="card-header bg-warning text-dark">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-0">🍽️ Mesa ' . $pedido['id_mesa'] . '</h6>
                            <span class="badge bg-dark">' . date('H:i', strtotime($pedido['fecha_hora'])) . '</span>
                        </div>
                        <small>Mesero: ' . htmlspecialchars($pedido['nombre_mesero']) . ' - ' . $tiempo_transcurrido . '</small>
                    </div>
                    <div class="card-body">
                        <div class="productos-detalle mb-3">
                            <h6>Productos:</h6>';
        
        foreach ($detalles as $detalle) {
            echo '<div class="producto-item border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>' . $detalle['cant'] . 'x ' . htmlspecialchars($detalle['nombre_producto']) . '</strong>';
            
            if (!empty($detalle['detalle'])) {
                echo '<br><small class="text-muted">Detalle: ' . htmlspecialchars($detalle['detalle']) . '</small>';
            }
            
            echo '      </div>
                    </div>
                  </div>';
        }
        
        echo '      </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <button class="btn btn-warning" onclick="cambiarEstadoPedido(' . $pedido['id'] . ', \'preparando\')">
                            ⏳ Iniciar Preparación
                        </button>
                    </div>
                </div>
              </div>
            </div>';
    }
}

// Función para mostrar pedidos en preparación
function mostrarPedidosEnPreparacion() {
    $pedidos = obtenerPedidosPorEstado('preparando');
    
    if (empty($pedidos)) {
        echo '<div class="text-center">
                <div class="alert alert-info">
                    <h5>🔄 No hay tickets en preparación</h5>
                    <p>No hay tickets siendo preparados en este momento.</p>
                </div>
              </div>';
        return;
    }
    
    foreach ($pedidos as $pedido) {
        $tiempo_transcurrido = calcularTiempoTranscurrido($pedido['fecha_hora']);
        $clase_urgencia = obtenerClaseUrgencia($tiempo_transcurrido);
        $detalles = obtenerDetallesPedido($pedido['id']);
        
        echo '<div class="col-md-6 mb-3">
                <div class="card border-primary ' . $clase_urgencia . '">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-0">🍽️ Mesa ' . $pedido['id_mesa'] . '</h6>
                            <span class="badge bg-light text-dark">' . date('H:i', strtotime($pedido['fecha_hora'])) . '</span>
                        </div>
                        <small>Mesero: ' . htmlspecialchars($pedido['nombre_mesero']) . ' - ' . $tiempo_transcurrido . '</small>
                    </div>
                    <div class="card-body">
                        <div class="productos-detalle mb-3">
                            <h6>Productos en preparación:</h6>';
        
        foreach ($detalles as $detalle) {
            echo '<div class="producto-item border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>' . $detalle['cant'] . 'x ' . htmlspecialchars($detalle['nombre_producto']) . '</strong>';
            
            if (!empty($detalle['detalle'])) {
                echo '<br><small class="text-muted">Detalle: ' . htmlspecialchars($detalle['detalle']) . '</small>';
            }
            
            echo '      </div>
                    </div>
                  </div>';
        }
        
        echo '      </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <button class="btn btn-success" onclick="cambiarEstadoPedido(' . $pedido['id'] . ', \'listo\')">
                            ✅ Marcar como Listo
                        </button>
                    </div>
                </div>
              </div>
            </div>';
    }
}

// Función para mostrar historial de pedidos listos
function mostrarHistorialPedidos() {
    $pedidos = obtenerPedidosPorEstado('listo');
    
    if (empty($pedidos)) {
        echo '<div class="text-center">
                <div class="alert alert-info">
                    <h5>✅ No hay pedidos listos</h5>
                    <p>No hay pedidos esperando ser entregados.</p>
                </div>
              </div>';
        return;
    }
    
    foreach ($pedidos as $pedido) {
        $tiempo_transcurrido = calcularTiempoTranscurrido($pedido['fecha_hora']);
        $detalles = obtenerDetallesPedido($pedido['id']);
        
        echo '<div class="col-md-6 mb-3">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-0">🍽️ Mesa ' . $pedido['id_mesa'] . ' - LISTO</h6>
                            <span class="badge bg-light text-dark">' . date('H:i', strtotime($pedido['fecha_hora'])) . '</span>
                        </div>
                        <small>Mesero: ' . htmlspecialchars($pedido['nombre_mesero']) . ' - ' . $tiempo_transcurrido . '</small>
                    </div>
                    <div class="card-body">
                        <div class="productos-detalle mb-3">
                            <h6>Productos listos para entregar:</h6>';
        
        foreach ($detalles as $detalle) {
            echo '<div class="producto-item border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>' . $detalle['cant'] . 'x ' . htmlspecialchars($detalle['nombre_producto']) . '</strong>';
            
            if (!empty($detalle['detalle'])) {
                echo '<br><small class="text-muted">Detalle: ' . htmlspecialchars($detalle['detalle']) . '</small>';
            }
            
            echo '      </div>
                    </div>
                  </div>';
        }
        
        echo '      </div>
                </div>
              </div>
            </div>';
    }
}

// Función para calcular tiempo transcurrido
function calcularTiempoTranscurrido($fecha_hora) {
    $tiempo_pedido = strtotime($fecha_hora);
    $tiempo_actual = time();
    $diferencia = $tiempo_actual - $tiempo_pedido;
    
    if ($diferencia < 60) {
        return 'Hace ' . $diferencia . ' seg';
    } elseif ($diferencia < 3600) {
        return 'Hace ' . floor($diferencia / 60) . ' min';
    } else {
        return 'Hace ' . floor($diferencia / 3600) . ' h';
    }
}

// Función para obtener clase de urgencia según tiempo
function obtenerClaseUrgencia($tiempo_transcurrido) {
    if (strpos($tiempo_transcurrido, 'h') !== false) {
        return 'border-danger';
    } elseif (strpos($tiempo_transcurrido, 'min') !== false) {
        $minutos = (int)filter_var($tiempo_transcurrido, FILTER_SANITIZE_NUMBER_INT);
        if ($minutos > 15) {
            return 'border-danger';
        } elseif ($minutos > 10) {
            return 'border-warning';
        }
    }
    return '';
}

// Función AJAX para cambiar estado de pedido
function cambiarEstadoPedidoBarra() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pedido_id']) && isset($_POST['nuevo_estado'])) {
        $pedido_id = (int)$_POST['pedido_id'];
        $nuevo_estado = $_POST['nuevo_estado'];
        
        // Validar estados permitidos
        $estados_permitidos = ['pendiente', 'preparando', 'listo', 'entregado'];
        if (!in_array($nuevo_estado, $estados_permitidos)) {
            echo json_encode(['success' => false, 'message' => 'Estado no válido']);
            return;
        }
        
        try {
            $sql = "UPDATE pedido_general SET estado_barra = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $nuevo_estado, $pedido_id);
            
            if ($stmt->execute()) {
                // Si el pedido se marca como entregado, actualizar estado general si corresponde
                if (in_array($nuevo_estado, ['listo', 'entregado'])) {
                    actualizarEstadoGeneral($pedido_id);
                }

                
                echo json_encode(['success' => true, 'message' => 'Estado de ticket actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
        }
    }
}

// Función para actualizar estado general del pedido
function actualizarEstadoGeneral($pedido_id) {
    global $conn;

    $sql = "SELECT estado_cocina, estado_barra, estado_licor FROM pedido_general WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $estados = [];

        foreach (['estado_cocina', 'estado_barra', 'estado_licor'] as $campo) {
            if (!is_null($row[$campo])) {
                $estados[] = $row[$campo];
            }
        }

        $estados = array_filter($estados); // Quitar nulos/vacíos
        $unicos = array_unique($estados);

        if (count($unicos) === 1 && $unicos[0] === 'entregado') {
            $nuevo_estado = 'entregado';
        } elseif (count($unicos) === 1 && $unicos[0] === 'listo') {
            $nuevo_estado = 'listo';
        } elseif (!array_diff($unicos, ['listo', 'entregado'])) {
            $nuevo_estado = 'listo';
        } else {
            $nuevo_estado = 'pendiente';
        }

        $sql_update = "UPDATE pedido_general SET estado_general = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $nuevo_estado, $pedido_id);
        $stmt_update->execute();
        $stmt_update->close();
    }

    $stmt->close();
}

// Función para obtener estadísticas por estado
function obtenerEstadisticas($estado) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as total FROM pedido_general WHERE estado_barra = ? AND EXISTS (
                SELECT 1 FROM ticket_barra tb WHERE tb.pedido_id = pedido_general.id
            )";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $estado);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'];
}

// Manejar peticiones AJAX
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'cambiar_estado_barra':
            cambiarEstadoPedidoBarra();
            exit;
        case 'obtener_pendientes':
            ob_start();
            mostrarPedidosPendientes();
            $html = ob_get_clean();
            echo json_encode(['html' => $html]);
            exit;
        case 'obtener_preparacion':
            ob_start();
            mostrarPedidosEnPreparacion();
            $html = ob_get_clean();
            echo json_encode(['html' => $html]);
            exit;
        case 'obtener_listos':
            ob_start();
            mostrarHistorialPedidos();
            $html = ob_get_clean();
            echo json_encode(['html' => $html]);
            exit;
    }
}

// Obtener estadísticas para el dashboard
$stats_pendientes = obtenerEstadisticas('pendiente');
$stats_preparando = obtenerEstadisticas('preparando');
$stats_listos = obtenerEstadisticas('listo');
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
    <title>Coctelería - Plaza Andina</title>
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
                        <div style="font-weight: 600;">Cocteler@ <?php echo htmlspecialchars($_SESSION["coctelero_name"] ?? 'Usuario'); ?></div>
                        <div style="font-size: 0.8rem; opacity: 0.8;">Sesión Activa</div>
                    </div>
                </div>
                <a href="../index.php" class="logout-btn">
                    🚪 Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Dashboard Content -->
    <div class="dashboard-container">
        <div class="container">
            <div class="dashboard-card">
                <div class="role-header">
                    <h1 class="role-title">🍸 Cóctelero</h1>
                    <p class="role-subtitle">Servicio de mixes y cócteles</p>
                </div>

                <div class="quick-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats_pendientes; ?></div>
                        <div class="stat-label">Tickets Pendientes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats_preparando; ?></div>
                        <div class="stat-label">En Preparación</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats_listos; ?></div>
                        <div class="stat-label">Tickets Atendidos</div>
                    </div>
                </div>

                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">⏳</div>
                        <h3 class="feature-title">Orden de Atención</h3>
                        <p class="feature-description">Atender mis tickets pendientes</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#ordenAtencionModal">
                            Atender Tickets
                        </a>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🔄</div>
                        <h3 class="feature-title">Marcar tickets</h3>
                        <p class="feature-description">Tickets en preparación a listos</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#marcarPedidosModal">
                            Marcar Listos
                        </a>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">✅</div>
                        <h3 class="feature-title">Ver Historial</h3>
                        <p class="feature-description">Tickets con estado listo</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#historialModal">
                            Ver Listos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Orden de Atención (Pendientes) -->
    <div class="modal fade" id="ordenAtencionModal" tabindex="-1" aria-labelledby="ordenAtencionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h1 class="modal-title fs-4" id="ordenAtencionModalLabel">
                        ⏳ Tickets Pendientes - Orden de Atención
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row" id="contenedorPendientes">
                        <?php mostrarPedidosPendientes(); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" onclick="actualizarPendientes()">
                        🔄 Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Marcar Pedidos (En Preparación) -->
    <div class="modal fade" id="marcarPedidosModal" tabindex="-1" aria-labelledby="marcarPedidosModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h1 class="modal-title fs-4" id="marcarPedidosModalLabel">
                        🔄 Tickets en Preparación - Marcar como Listos
                    </h1>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row" id="contenedorPreparacion">
                        <?php mostrarPedidosEnPreparacion(); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="actualizarPreparacion()">
                        🔄 Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Historial (Listos) -->
    <div class="modal fade" id="historialModal" tabindex="-1" aria-labelledby="historialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h1 class="modal-title fs-4" id="historialModalLabel">
                        ✅ Historial de tickets Listos
                    </h1>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row" id="contenedorListos">
                        <?php mostrarHistorialPedidos(); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="actualizarListos()">
                        🔄 Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>
    <script>
    // Script principal para el sistema de barra
    // Debe incluirse antes del cierre del </body>

    // Función para cambiar el estado de un pedido
    function cambiarEstadoPedido(pedidoId, nuevoEstado) {
        // Mostrar indicador de carga
        const button = event.target;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        
        // Realizar petición AJAX
        fetch('?action=cambiar_estado_barra', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `pedido_id=${pedidoId}&nuevo_estado=${nuevoEstado}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                mostrarAlerta('success', data.message);
                
                // Actualizar las vistas según el estado
                setTimeout(() => {
                    actualizarTodasLasVistas();
                    actualizarEstadisticas();
                }, 500);
            } else {
                mostrarAlerta('error', data.message);
                // Restaurar botón
                button.disabled = false;
                button.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('error', 'Error de conexión. Intente nuevamente.');
            // Restaurar botón
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }

    // Función para actualizar pedidos pendientes
    function actualizarPendientes() {
        const contenedor = document.getElementById('contenedorPendientes');
        mostrarCargando(contenedor);
        
        fetch('?action=obtener_pendientes')
            .then(response => response.json())
            .then(data => {
                contenedor.innerHTML = data.html;
            })
            .catch(error => {
                console.error('Error:', error);
                contenedor.innerHTML = '<div class="alert alert-danger">Error al cargar los pedidos pendientes</div>';
            });
    }

    // Función para actualizar pedidos en preparación
    function actualizarPreparacion() {
        const contenedor = document.getElementById('contenedorPreparacion');
        mostrarCargando(contenedor);
        
        fetch('?action=obtener_preparacion')
            .then(response => response.json())
            .then(data => {
                contenedor.innerHTML = data.html;
            })
            .catch(error => {
                console.error('Error:', error);
                contenedor.innerHTML = '<div class="alert alert-danger">Error al cargar los tickets en preparación</div>';
            });
    }

    // Función para actualizar pedidos listos
    function actualizarListos() {
        const contenedor = document.getElementById('contenedorListos');
        mostrarCargando(contenedor);
        
        fetch('?action=obtener_listos')
            .then(response => response.json())
            .then(data => {
                contenedor.innerHTML = data.html;
            })
            .catch(error => {
                console.error('Error:', error);
                contenedor.innerHTML = '<div class="alert alert-danger">Error al cargar los pedidos listos</div>';
            });
    }

    // Función para actualizar todas las vistas
    function actualizarTodasLasVistas() {
        // Actualizar solo los modales que estén abiertos
        const modales = ['ordenAtencionModal', 'marcarPedidosModal', 'historialModal'];
        
        modales.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal && modal.classList.contains('show')) {
                switch(modalId) {
                    case 'ordenAtencionModal':
                        actualizarPendientes();
                        break;
                    case 'marcarPedidosModal':
                        actualizarPreparacion();
                        break;
                    case 'historialModal':
                        actualizarListos();
                        break;
                }
            }
        });
    }

    // Función para mostrar indicador de carga
    function mostrarCargando(contenedor) {
        contenedor.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Actualizando pedidos...</p>
            </div>
        `;
    }

    // Función para mostrar alertas
    function mostrarAlerta(tipo, mensaje) {
        const alertaClass = tipo === 'success' ? 'alert-success' : 'alert-danger';
        const icono = tipo === 'success' ? '✅' : '❌';
        
        const alerta = document.createElement('div');
        alerta.className = `alert ${alertaClass} alert-dismissible fade show position-fixed`;
        alerta.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alerta.innerHTML = `
            ${icono} ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.body.appendChild(alerta);
        
        // Auto-eliminar después de 5 segundos
        setTimeout(() => {
            if (alerta.parentNode) {
                alerta.remove();
            }
        }, 5000);
    }

    // Función para actualizar estadísticas en tiempo real
    function actualizarEstadisticas() {
        // Actualizar contadores en los botones del dashboard
        Promise.all([
            fetch('?action=obtener_pendientes'),
            fetch('?action=obtener_preparacion'),
            fetch('?action=obtener_listos')
        ])
        .then(responses => Promise.all(responses.map(r => r.json())))
        .then(data => {
            // Contar elementos en cada respuesta
            const pendientes = (data[0].html.match(/card border-warning/g) || []).length;
            const preparando = (data[1].html.match(/card border-primary/g) || []).length;
            const listos = (data[2].html.match(/card border-success/g) || []).length;
            
            // Actualizar contadores en el dashboard
            const statCards = document.querySelectorAll('.stat-number');
            if (statCards[0]) statCards[0].textContent = pendientes;
            if (statCards[1]) statCards[1].textContent = preparando;
            if (statCards[2]) statCards[2].textContent = listos;
        })
        .catch(error => {
            console.error('Error actualizando estadísticas:', error);
        });
    }

    // Función para actualización automática
    function iniciarActualizacionAutomatica() {
        // Actualizar cada 30 segundos
        setInterval(() => {
            actualizarTodasLasVistas();
            actualizarEstadisticas();
        }, 30000);
    }

    // Función para manejar confirmaciones
    function confirmarAccion(mensaje, callback) {
        if (confirm(mensaje)) {
            callback();
        }
    }

    // Función para formatear tiempo transcurrido
    function formatearTiempoTranscurrido(fechaHora) {
        const ahora = new Date();
        const fecha = new Date(fechaHora);
        const diferencia = Math.floor((ahora - fecha) / 1000);
        
        if (diferencia < 60) {
            return `Hace ${diferencia} seg`;
        } else if (diferencia < 3600) {
            return `Hace ${Math.floor(diferencia / 60)} min`;
        } else {
            return `Hace ${Math.floor(diferencia / 3600)} h`;
        }
    }

    // Función para manejo de errores de red
    function manejarErrorRed(error) {
        console.error('Error de red:', error);
        mostrarAlerta('error', 'Error de conexión. Verifique su conexión a internet.');
    }

    // Event listeners cuando el DOM esté cargado
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar actualización automática
        iniciarActualizacionAutomatica();
        
        // Agregar event listeners para los modales
        const modales = document.querySelectorAll('.modal');
        modales.forEach(modal => {
            modal.addEventListener('show.bs.modal', function() {
                // Actualizar contenido cuando se abre el modal
                const modalId = this.id;
                setTimeout(() => {
                    switch(modalId) {
                        case 'ordenAtencionModal':
                            actualizarPendientes();
                            break;
                        case 'marcarPedidosModal':
                            actualizarPreparacion();
                            break;
                        case 'historialModal':
                            actualizarListos();
                            break;
                    }
                }, 100);
            });
        });
        
        // Actualizar estadísticas iniciales
        actualizarEstadisticas();
        
        // Manejar teclas de acceso rápido
        document.addEventListener('keydown', function(event) {
            if (event.ctrlKey) {
                switch(event.key) {
                    case '1':
                        event.preventDefault();
                        document.querySelector('[data-bs-target="#ordenAtencionModal"]').click();
                        break;
                    case '2':
                        event.preventDefault();
                        document.querySelector('[data-bs-target="#marcarPedidosModal"]').click();
                        break;
                    case '3':
                        event.preventDefault();
                        document.querySelector('[data-bs-target="#historialModal"]').click();
                        break;
                    case 'r':
                        event.preventDefault();
                        actualizarTodasLasVistas();
                        break;
                }
            }
        });
    });

    // Función para validar estado del pedido antes de cambiar
    function validarCambioEstado(pedidoId, estadoActual, nuevoEstado) {
        const transicionesPermitidas = {
            'pendiente': ['preparando'],
            'preparando': ['listo'],
            'listo': ['entregado']
        };
        
        if (!transicionesPermitidas[estadoActual] || 
            !transicionesPermitidas[estadoActual].includes(nuevoEstado)) {
            mostrarAlerta('error', 'Transición de estado no permitida');
            return false;
        }
        
        return true;
    }

    // Función para exportar datos (opcional)
    function exportarDatos() {
        const datos = {
            timestamp: new Date().toISOString(),
            pedidos: {
                pendientes: document.getElementById('contenedorPendientes').innerHTML,
                preparacion: document.getElementById('contenedorPreparacion').innerHTML,
                listos: document.getElementById('contenedorListos').innerHTML
            }
        };
        
        const dataStr = JSON.stringify(datos, null, 2);
        const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
        
        const exportFileDefaultName = `barra_datos_${new Date().toISOString().split('T')[0]}.json`;
        
        const linkElement = document.createElement('a');
        linkElement.setAttribute('href', dataUri);
        linkElement.setAttribute('download', exportFileDefaultName);
        linkElement.click();
    }

    // Función para modo offline (detección de conectividad)
    function detectarConectividad() {
        if (!navigator.onLine) {
            mostrarAlerta('error', 'Sin conexión a internet. Algunas funciones pueden no estar disponibles.');
            return false;
        }
        return true;
    }

    // Event listeners para conectividad
    window.addEventListener('online', function() {
        mostrarAlerta('success', 'Conexión restaurada');
        actualizarTodasLasVistas();
    });

    window.addEventListener('offline', function() {
        mostrarAlerta('error', 'Sin conexión a internet');
    });

    // Función para limpiar cache (opcional)
    function limpiarCache() {
        if ('caches' in window) {
            caches.keys().then(function(names) {
                names.forEach(function(name) {
                    caches.delete(name);
                });
            });
        }
        location.reload();
    }


        // Auto-refresh cada 30 segundos para mantener la información actualizada
        setInterval(function() {
            // Solo hacer refresh si no hay modales abiertos
            if (!document.querySelector('.modal.show')) {
                location.reload();
            }
        }, 30000);
    </script>
</html>