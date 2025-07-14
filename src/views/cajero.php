<?php
// Conexión a la base de datos
include_once 'conexion.php';

// Iniciar la sesión, permitiendo el uso de variables de sesión
session_start();

$con = conectar();

// Función para pedidos por estado específico
function obtenerPedidosPorEstado($estado) {
    global $con;
    
    $sql = "SELECT pg.id, pg.fecha_hora, pg.id_mesa, pg.total, pg.estado_general, m.nombre AS mesero_nombre
            FROM pedido_general pg
            JOIN empleado m ON pg.id_mesero = m.identificación
            WHERE pg.estado_general = ?
            ORDER BY pg.fecha_hora ASC";
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $estado);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }
    return $pedidos;
}

// Función para obtener meseros disponibles
function obtenerMeseros() {
    global $con;
    
    $sql = "SELECT identificación, nombre FROM empleado WHERE rol = 'mesero' ORDER BY nombre ASC";
    $result = $con->query($sql);
    
    $meseros = [];
    while ($row = $result->fetch_assoc()) {
        $meseros[] = $row;
    }
    return $meseros;
}

// Función para obtener información de mesas
function obtenerMesas() {
    global $con;
    
    $sql = "SELECT m.id, m.estado, m.tipo, m.mesero, m.fecha_asignacion, 
                    e.nombre AS nombre_mesero
                    FROM mesa m
                    LEFT JOIN empleado e ON m.mesero = e.identificación
                    ORDER BY m.id ASC";
    
    $result = $con->query($sql);
    
    $mesas = [];
    while ($row = $result->fetch_assoc()) {
        $mesas[] = $row;
    }
    return $mesas;
}

// Función para liberar mesa
function liberarMesa($id_mesa) {
    global $con;
    
    $con->begin_transaction();
    
    try {
        // Actualizar estado de la mesa - ahora solo se actualiza 'mesero'
        $sql = "UPDATE mesa SET estado = 'DISPONIBLE', mesero = NULL, fecha_asignacion = NULL WHERE id = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $id_mesa);
        $stmt->execute();
        
        // Actualizar estado del pedido a terminado
        $sql = "UPDATE pedido_general SET estado_general = 'terminado' WHERE id_mesa = ? AND estado_general = 'pagado'";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $id_mesa);
        $stmt->execute();
        
        $con->commit();
        return true;
    } catch (Exception $e) {
        $con->rollback();
        return false;
    }
}

// Función para asignar mesa a mesero
function asignarMesa($id_mesa, $id_mesero) {
    global $con;

    $con->begin_transaction();
    try {
        // Obtener nombre del mesero
        $sql = "SELECT nombre FROM empleado WHERE identificación = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("s", $id_mesero);
        $stmt->execute();
        $result = $stmt->get_result();
        $mesero = $result->fetch_assoc();

        if ($mesero) {
            $sql = "UPDATE mesa SET estado = 'OCUPADA', mesero = ?, fecha_asignacion = NOW() WHERE id = ?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("si", $id_mesero, $id_mesa);
            $stmt->execute();

            // Marcar el pedido anterior como terminado
            $sql = "UPDATE pedido_general SET estado_general = 'terminado' WHERE id_mesa = ? AND estado_general = 'PAGADO'";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("i", $id_mesa);
            $stmt->execute();

            $con->commit();
            return true;
        }
        $con->rollback();
        return false;
    } catch (Exception $e) {
        $con->rollback();
        return false;
    }
}

// Procesar acciones del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'liberar':
                if (isset($_POST['id_mesa'])) {
                    $resultado = liberarMesa($_POST['id_mesa']);
                    $mensaje = $resultado ? "Mesa liberada exitosamente" : "Error al liberar la mesa";
                }
                break;
            
            case 'asignar':
                if (isset($_POST['id_mesa']) && isset($_POST['mesero'])) {
                    $resultado = asignarMesa($_POST['id_mesa'], $_POST['mesero']);
                    $mensaje = $resultado ? "Mesa asignada exitosamente" : "Error al asignar la mesa";
                }
                break;
        }
    }
}

// Obtener datos para mostrar
$pedidosPagados = obtenerPedidosPorEstado('pagado');
$pedidosPendientes = obtenerPedidosPorEstado('pendiente');
$pedidosTerminados = obtenerPedidosPorEstado('terminado');
$meseros = obtenerMeseros();
$mesas = obtenerMesas();

// Separar mesas por estado
$mesasDisponibles = array_filter($mesas, function($mesa) {
    return $mesa['estado'] === 'DISPONIBLE';
});

$mesasOcupadas = array_filter($mesas, function($mesa) {
    return $mesa['estado'] === 'OCUPADA' || $mesa['estado'] === 'ATENDIENDO';
});

// Calcular estadísticas
$totalPedidosPendientes = count($pedidosPendientes);
$totalMesasLiberar = count($pedidosPagados);
$totalSaldo = array_sum(array_column(array_merge($pedidosPagados, $pedidosTerminados), 'total'));
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
    <title>Cajero - Plaza Andina</title>
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
                    <div class="user-avatar">💵</div>
                    <div>
                        <div style="font-weight: 600;">Cajer@ <?php echo htmlspecialchars($_SESSION["cajero_name"] ?? 'Usuario'); ?></div>
                        <div style="font-size: 0.8rem; opacity: 0.8;">Sesión Activa</div>
                    </div>
                </div>
                <a href="../index.php" class="logout-btn">
                    🚪 Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Mensaje de notificación -->
    <?php if (isset($mensaje)): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?php echo $mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Dashboard Content -->
    <div class="dashboard-container">
        <div class="container">
            <div class="dashboard-card">
                <div class="role-header">
                    <h1 class="role-title">💵 Cajero</h1>
                    <p class="role-subtitle">Gestión de Pagos y Mesas</p>
                </div>

                <div class="quick-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalPedidosPendientes; ?></div>
                        <div class="stat-label">Pedidos pendientes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalMesasLiberar; ?></div>
                        <div class="stat-label">Mesas a liberar</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">$<?php echo number_format($totalSaldo, 0, ',', '.'); ?></div>
                        <div class="stat-label">Saldo total</div>
                    </div>
                </div>

                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🧾</div>
                        <h3 class="feature-title">Pedidos Pagados</h3>
                        <p class="feature-description">Ver lista de pedidos que ya han sido pagados para liberar la mesa o asignar de nuevo al mesero</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#modalPagados">Ver Pedidos</a>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⏳</div>
                        <h3 class="feature-title">Pedidos Pendientes</h3>
                        <p class="feature-description">Ver estado actual de los pedidos no pagados</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#modalPendientes">Ver Pendientes</a>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🪑</div>
                        <h3 class="feature-title">Gestión de Mesas</h3>
                        <p class="feature-description">Administrar asignación y liberación de mesas</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#modalMesas">Ver Mesas</a>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📋</div>
                        <h3 class="feature-title">Historial</h3>
                        <p class="feature-description">Ver historial de pedidos terminados</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#modalHistorial">Ver Historial</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pedidos Pagados -->
    <div class="modal fade" id="modalPagados" tabindex="-1" aria-labelledby="modalPagadosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="modalPagadosLabel">🧾 Pedidos Pagados</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th># Pedido</th>
                                <th>Mesa</th>
                                <th>Mesero</th>
                                <th>Monto</th>
                                <th>Fecha/Hora</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidosPagados as $pedido): ?>
                            <tr>
                                <td><?php echo $pedido['id']; ?></td>
                                <td>Mesa <?php echo $pedido['id_mesa']; ?></td>
                                <td><?php echo htmlspecialchars($pedido['mesero_nombre']); ?></td>
                                <td>$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></td>
                                <td><?php echo $pedido['fecha_hora']; ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="accion" value="liberar">
                                            <input type="hidden" name="id_mesa" value="<?php echo $pedido['id_mesa']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Liberar mesa y terminar pedido?')">
                                                🔓 Liberar Mesa
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalAsignar<?php echo $pedido['id']; ?>">
                                            👤 Reasignar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="location.reload()">Actualizar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales para reasignar cada pedido -->
    <?php foreach ($pedidosPagados as $pedido): ?>
    <div class="modal fade" id="modalAsignar<?php echo $pedido['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reasignar Mesa <?php echo $pedido['id_mesa']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="accion" value="asignar">
                        <input type="hidden" name="id_mesa" value="<?php echo $pedido['id_mesa']; ?>">
                        <div class="mb-3">
                            <label for="mesero" class="form-label">Seleccionar Mesero:</label>
                            <select name="mesero" class="form-select" required>
                                <option value="">Seleccionar mesero...</option>
                                <?php foreach ($meseros as $mesero): ?>
                                <option value="<?php echo $mesero['identificación']; ?>">
                                    <?php echo htmlspecialchars($mesero['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Asignar Mesa</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Modal Pedidos Pendientes -->
    <div class="modal fade" id="modalPendientes" tabindex="-1" aria-labelledby="modalPendientesLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="modalPendientesLabel">⏳ Pedidos Pendientes</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th># Pedido</th>
                                <th>Mesa</th>
                                <th>Mesero</th>
                                <th>Monto</th>
                                <th>Fecha/Hora</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidosPendientes as $pedido): ?>
                            <tr>
                                <td><?php echo $pedido['id']; ?></td>
                                <td>Mesa <?php echo $pedido['id_mesa']; ?></td>
                                <td><?php echo htmlspecialchars($pedido['mesero_nombre']); ?></td>
                                <td>$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></td>
                                <td><?php echo $pedido['fecha_hora']; ?></td>
                                <td>
                                    <span class="badge bg-warning">
                                        <?php echo $pedido['estado_general']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="location.reload()">Actualizar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Gestión de Mesas -->
    <div class="modal fade" id="modalMesas" tabindex="-1" aria-labelledby="modalGestionMesasLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="modalGestionMesasLabel">🍽️ Gestión de Mesas</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Mesas Disponibles -->
                        <div class="col-md-6">
                            <h5 class="text-success">✅ Mesas Disponibles</h5>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-striped">
                                    <thead class="table-success">
                                        <tr>
                                            <th>Mesa</th>
                                            <th>Tipo</th>
                                            <th>Asignar a</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($mesasDisponibles as $mesa): ?>
                                        <tr>
                                            <td><strong>#<?php echo $mesa['id']; ?></strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo $mesa['tipo'] == 'NORMAL' ? 'primary' : 'warning'; ?>">
                                                    <?php echo $mesa['tipo']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="accion" value="asignar">
                                                    <input type="hidden" name="id_mesa" value="<?php echo $mesa['id']; ?>">
                                                    <select name="mesero" class="form-select form-select-sm" required>
                                                        <option value="">Seleccionar...</option>
                                                        <?php foreach($meseros as $mesero): ?>
                                                        <option value="<?php echo $mesero['identificación']; ?>">
                                                            <?php echo htmlspecialchars($mesero['nombre']); ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                            </td>
                                            <td>
                                                    <button type="submit" class="btn btn-success btn-sm">
                                                        ➕ Asignar
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Mesas Ocupadas -->
                        <div class="col-md-6">
                            <h5 class="text-danger">🔴 Mesas Ocupadas</h5>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-striped">
                                    <thead class="table-danger">
                                        <tr>
                                            <th>Mesa</th>
                                            <th>Tipo</th>
                                            <th>Mesero</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($mesasOcupadas as $mesa): ?>
                                        <tr>
                                            <td><strong>#<?php echo $mesa['id']; ?></strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo $mesa['tipo'] == 'NORMAL' ? 'primary' : 'warning'; ?>">
                                                    <?php echo $mesa['tipo']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo $mesa['nombre_mesero'] ? htmlspecialchars($mesa['nombre_mesero']) : 'Sin asignar'; ?></strong>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="accion" value="liberar">
                                                    <input type="hidden" name="id_mesa" value="<?php echo $mesa['id']; ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('¿Liberar esta mesa?')">
                                                        ➖ Liberar
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Historial -->
    <div class="modal fade" id="modalHistorial" tabindex="-1" aria-labelledby="modalHistorialLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="modalHistorialLabel">📋 Historial de Pedidos</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th># Pedido</th>
                                <th>Mesa</th>
                                <th>Mesero</th>
                                <th>Monto</th>
                                <th>Fecha/Hora</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidosTerminados as $pedido): ?>
                            <tr>
                                <td><?php echo $pedido['id']; ?></td>
                                <td>Mesa <?php echo $pedido['id_mesa']; ?></td>
                                <td><?php echo htmlspecialchars($pedido['mesero_nombre']); ?></td>
                                <td>$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></td>
                                <td><?php echo $pedido['fecha_hora']; ?></td>
                                <td>
                                    <span class="badge bg-success">
                                        <?php echo $pedido['estado_general']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="location.reload()">Actualizar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
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