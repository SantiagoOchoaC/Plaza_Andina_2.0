<?php
// Conexión a la base de datos
include_once 'conexion.php';
// Iniciar la sesión
session_start();

// Procesar asignación de mesa individual
if (isset($_POST['accion']) && $_POST['accion'] == 'asignar' && isset($_POST['id_mesa']) && isset($_POST['mesero'])) {
    $id_mesa = intval($_POST['id_mesa']);
    $id_mesero = intval($_POST['mesero']);
    $fecha_asignacion = date('Y-m-d H:i:s');
    
    $con = conectar();
    
    // Verificar que la mesa esté disponible
    $check_query = "SELECT estado FROM mesa WHERE id = ? AND estado = 'DISPONIBLE'";
    $check_stmt = mysqli_prepare($con, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $id_mesa);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Asignar mesa
        $query = "UPDATE mesa SET estado = 'OCUPADA', mesero = ?, fecha_asignacion = ? WHERE id = ?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "isi", $id_mesero, $fecha_asignacion, $id_mesa);
        
        if (mysqli_stmt_execute($stmt)) {
            // Crear notificación para el mesero
            $mensaje_notif = "Se te ha asignado la Mesa #" . $id_mesa . " - " . date('H:i');
            $notif_query = "INSERT INTO notificaciones (id_empleado, mensaje, fecha_creacion) VALUES (?, ?, NOW())";
            $notif_stmt = mysqli_prepare($con, $notif_query);
            mysqli_stmt_bind_param($notif_stmt, "is", $id_mesero, $mensaje_notif);
            mysqli_stmt_execute($notif_stmt);
            
            $mensaje = "Mesa #$id_mesa asignada correctamente";
            $tipo_mensaje = "success";
            
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=asignada&mesa=$id_mesa");
            exit();
        } else {
            $mensaje = "Error al asignar mesa";
            $tipo_mensaje = "danger";
        }
        mysqli_stmt_close($stmt);
    } else {
        $mensaje = "La mesa no está disponible";
        $tipo_mensaje = "warning";
    }
    
    mysqli_stmt_close($check_stmt);
    mysqli_close($con);
}

// Procesar liberación de mesa
if (isset($_POST['accion']) && $_POST['accion'] == 'liberar' && isset($_POST['id_mesa'])) {
    $id_mesa = intval($_POST['id_mesa']);
    
    $con = conectar();
    
    // Obtener información del mesero antes de liberar
    $mesero_query = "SELECT mesero FROM mesa WHERE id = ? AND estado = 'OCUPADA' || estado = 'ATENDIENDO'";
    $mesero_stmt = mysqli_prepare($con, $mesero_query);
    mysqli_stmt_bind_param($mesero_stmt, "i", $id_mesa);
    mysqli_stmt_execute($mesero_stmt);
    $mesero_result = mysqli_stmt_get_result($mesero_stmt);
    
    if (mysqli_num_rows($mesero_result) > 0) {
        $mesero_row = mysqli_fetch_assoc($mesero_result);
        $id_mesero = $mesero_row['mesero'];
        
        // Liberar mesa
        $query = "UPDATE mesa SET estado = 'DISPONIBLE', mesero = NULL, fecha_asignacion = NULL WHERE id = ?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "i", $id_mesa);
        
        if (mysqli_stmt_execute($stmt)) {
            // Notificar al mesero sobre la liberación
            if ($id_mesero) {
                $mensaje_notif = "La Mesa #" . $id_mesa . " ha sido liberada - " . date('H:i');
                $notif_query = "INSERT INTO notificaciones (id_empleado, mensaje, fecha_creacion) VALUES (?, ?, NOW())";
                $notif_stmt = mysqli_prepare($con, $notif_query);
                mysqli_stmt_bind_param($notif_stmt, "is", $id_mesero, $mensaje_notif);
                mysqli_stmt_execute($notif_stmt);
            }
            
            $mensaje = "Mesa #$id_mesa liberada correctamente";
            $tipo_mensaje = "success";
            
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=liberada&mesa=$id_mesa");
            exit();
        } else {
            $mensaje = "Error al liberar mesa";
            $tipo_mensaje = "danger";
        }
        mysqli_stmt_close($stmt);
    } else {
        $mensaje = "La mesa no está ocupada";
        $tipo_mensaje = "warning";
    }
    
    mysqli_stmt_close($mesero_stmt);
    mysqli_close($con);
}

// Manejar mensajes desde GET
if (isset($_GET['msg'])) {
    $mesa_num = isset($_GET['mesa']) ? $_GET['mesa'] : '';
    switch($_GET['msg']) {
        case 'asignada':
            $mensaje = "Mesa #$mesa_num asignada correctamente";
            $tipo_mensaje = "success";
            break;
        case 'liberada':
            $mensaje = "Mesa #$mesa_num liberada correctamente";
            $tipo_mensaje = "success";
            break;
    }
}

// Función para obtener todas las mesas
function obtenerMesas() {
    $con = conectar();
    $query = "SELECT m.*, e.nombre as nombre_mesero 
                FROM mesa m 
                LEFT JOIN empleado e ON m.mesero = e.identificación 
                ORDER BY m.id";
    $result = mysqli_query($con, $query);
    
    $mesas = [];
    $stats = [
        'total' => 0,
        'disponibles' => 0,
        'ocupadas' => 0,
        'normales_disponibles' => 0,
        'normales_ocupadas' => 0,
        'madera_disponibles' => 0,
        'madera_ocupadas' => 0
    ];
    
    while($row = mysqli_fetch_assoc($result)) {
        $mesas[] = $row;
        $stats['total']++;
        
        if ($row['estado'] == 'DISPONIBLE') {
            $stats['disponibles']++;
            if ($row['tipo'] == 'NORMAL') {
                $stats['normales_disponibles']++;
            } else {
                $stats['madera_disponibles']++;
            }
        } else {
            $stats['ocupadas']++;
            if ($row['tipo'] == 'NORMAL') {
                $stats['normales_ocupadas']++;
            } else {
                $stats['madera_ocupadas']++;
            }
        }
    }
    
    mysqli_close($con);
    
    return ['mesas' => $mesas, 'stats' => $stats];
}

// Función para obtener meseros disponibles
function obtenerMeseros() {
    $con = conectar();
    $query = "SELECT e.identificación, e.nombre, 
                COUNT(m.id) as mesas_asignadas
                FROM empleado e
                LEFT JOIN mesa m ON e.identificación = m.mesero AND (m.estado = 'OCUPADA' OR m.estado = 'ATENDIENDO')
                WHERE e.rol = 'mesero'
                GROUP BY e.identificación, e.nombre
                ORDER BY mesas_asignadas ASC, e.nombre ASC";
    $result = mysqli_query($con, $query);
    
    $meseros = [];
    while($row = mysqli_fetch_assoc($result)) {
        $meseros[] = $row;
    }
    
    mysqli_close($con);
    return $meseros;
}

$dataMesas = obtenerMesas();
$meseros = obtenerMeseros();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../css/styles1.css">
    <title>Jefe de Meseros - Plaza Andina</title>
</head>
<body>
    <!-- Header General -->
    <header class="main-header">
        <div class="header-content">
            <div class="header-left">
                <div class="logo-container">🏪</div>
                <div class="header-title">
                    <h1>Plaza Andina</h1>
                    <p>Sistema de Gestión Restaurante</p>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar">👨‍🍳</div>
                    <div>
                        <div style="font-weight: 600;">Jefe de Meseros <?php echo htmlspecialchars($_SESSION["jefemeseros_name"] ?? 'Usuario'); ?></div>
                        <div style="font-size: 0.8rem; opacity: 0.8;">Sesión Activa</div>
                    </div>
                </div>
                <a href="../index.php" class="logout-btn">🚪 Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <!-- Dashboard Content -->
    <div class="dashboard-container">
        <div class="container">
            <?php if (isset($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-card">
                <div class="role-header">
                    <h1 class="role-title">🎯 Jefe de Meseros</h1>
                    <p class="role-subtitle">Supervisión y Coordinación del Servicio</p>
                </div>

                <div class="quick-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $dataMesas['stats']['disponibles']; ?></div>
                        <div class="stat-label">Mesas Disponibles</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $dataMesas['stats']['ocupadas']; ?></div>
                        <div class="stat-label">Mesas Ocupadas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $dataMesas['stats']['total']; ?></div>
                        <div class="stat-label">Total de Mesas</div>
                    </div>
                </div>

                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🍽️</div>
                        <h3 class="feature-title">Gestión de Mesas</h3>
                        <p class="feature-description">Asignar meseros y liberar mesas</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#modalGestionMesas">Gestionar</a>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">⭐</div>
                        <h3 class="feature-title">Ver Disponibilidad</h3>
                        <p class="feature-description">Consultar estado y asignaciones</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#modalConsultaMesas">Consultar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Gestión de Mesas -->
    <div class="modal fade" id="modalGestionMesas" tabindex="-1" aria-labelledby="modalGestionMesasLabel" aria-hidden="true">
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
                                        <?php foreach($dataMesas['mesas'] as $mesa): ?>
                                            <?php if ($mesa['estado'] == 'DISPONIBLE'): ?>
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
                                                                    <?php foreach($meseros as $index => $mesero): ?>
                                                                        <option value="<?php echo $mesero['identificación']; ?>" <?php echo $index === 0 ? 'selected' : ''; ?>><?php echo htmlspecialchars($mesero['nombre']) . ' (' . $mesero['mesas_asignadas'] . ' mesas)'; ?></option>
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
                                            <?php endif; ?>
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
                                        <?php foreach($dataMesas['mesas'] as $mesa): ?>
                                            <?php if ($mesa['estado'] == 'OCUPADA' || $mesa['estado'] == 'ATENDIENDO'): ?>
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
                                                            <button type="submit" class="btn btn-warning btn-sm">
                                                                ➖ Liberar
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
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

    <!-- Modal Consulta de Mesas -->
    <div class="modal fade" id="modalConsultaMesas" tabindex="-1" aria-labelledby="modalConsultaMesasLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="modalConsultaMesasLabel">📋 Estado de Mesas</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Resumen General -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">💺 Mesas Normales</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <div class="badge bg-success fs-6 p-2 w-100">
                                                Disponibles<br><?php echo $dataMesas['stats']['normales_disponibles']; ?>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="badge bg-danger fs-6 p-2 w-100">
                                                Ocupadas<br><?php echo $dataMesas['stats']['normales_ocupadas']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">🪑 Mesas de Madera</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <div class="badge bg-success fs-6 p-2 w-100">
                                                Disponibles<br><?php echo $dataMesas['stats']['madera_disponibles']; ?>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="badge bg-danger fs-6 p-2 w-100">
                                                Ocupadas<br><?php echo $dataMesas['stats']['madera_ocupadas']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista Detallada -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Mesa</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Mesero Asignado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($dataMesas['mesas'] as $mesa): ?>
                                    <tr>
                                        <td><strong>#<?php echo $mesa['id']; ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $mesa['tipo'] == 'NORMAL' ? 'primary' : 'warning'; ?>">
                                                <?php echo $mesa['tipo']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $mesa['estado'] == 'DISPONIBLE' ? 'success' : 'danger'; ?>">
                                                <?php echo $mesa['estado']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $mesa['nombre_mesero'] ? htmlspecialchars($mesa['nombre_mesero']) : '<em>Sin asignar</em>'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="window.location.reload();">Actualizar</button>
                </div>
            </div>
        </div>
    </div>
</body>
    <script>
        // Auto-refresh cada 30 segundos para mantener la información actualizada
        setInterval(function() {
            // Solo hacer refresh si no hay modales abiertos
            if (!document.querySelector('.modal.show')) {
                location.reload();
            }
        }, 30000);
    </script>
</html>