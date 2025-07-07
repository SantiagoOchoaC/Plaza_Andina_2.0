<?php
include_once 'conexion.php';

// Iniciar sesión
session_start();

// Función para obtener datos de mesas desde la tabla mesa
function obtenerEstadoMesas() {
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
        'madera_ocupadas' => 0,
        'porcentaje_ocupacion' => 0
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
    
    // Calcular porcentaje de ocupación
    if ($stats['total'] > 0) {
        $stats['porcentaje_ocupacion'] = round(($stats['ocupadas'] / $stats['total']) * 100, 1);
    }
    
    mysqli_close($con);
    
    return [
        'mesas' => $mesas,
        'stats' => $stats
    ];
}

$estadoMesas = obtenerEstadoMesas();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../css/styles1.css">
    <title>Staff - Plaza Andina</title>
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
                    <div class="user-avatar">👮‍♂️</div>
                    <div>
                        <div style="font-weight: 600;">Staff <?php echo htmlspecialchars($_SESSION["staff_name"] ?? 'Usuario'); ?></div>
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
            <!-- Alerta de No Disponibilidad -->
            <?php if ($estadoMesas['stats']['disponibles'] == 0): ?>
                <div class="alert alert-danger no-availability-alert mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="me-3" style="font-size: 2rem;">🚨</div>
                        <div>
                            <h4 class="alert-heading mb-1">¡Sin Disponibilidad!</h4>
                            <p class="mb-0">
                                <strong>Todas las mesas están ocupadas.</strong> 
                                Ocupación: <?php echo $estadoMesas['stats']['porcentaje_ocupacion']; ?>% 
                                (<?php echo $estadoMesas['stats']['ocupadas']; ?>/<?php echo $estadoMesas['stats']['total']; ?> mesas)
                            </p>
                        </div>
                    </div>
                </div>
            <?php elseif ($estadoMesas['stats']['disponibles'] <= 3): ?>
                <div class="alert alert-warning mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="me-3" style="font-size: 2rem;">⚠️</div>
                        <div>
                            <h5 class="alert-heading mb-1">¡Pocas Mesas Disponibles!</h5>
                            <p class="mb-0">
                                Solo quedan <strong><?php echo $estadoMesas['stats']['disponibles']; ?> mesas disponibles</strong>. 
                                Ocupación: <?php echo $estadoMesas['stats']['porcentaje_ocupacion']; ?>%
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="dashboard-card">
                <div class="role-header">
                    <h1 class="role-title">👮‍♂️ Staff</h1>
                    <p class="role-subtitle">Panel de Administración General</p>
                </div>

                <div class="quick-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $estadoMesas['stats']['total']; ?></div>
                        <div class="stat-label">Aforo Total</div>
                    </div>
                    <div class="stat-card <?php echo $estadoMesas['stats']['disponibles'] == 0 ? 'critical-status' : ($estadoMesas['stats']['disponibles'] <= 3 ? 'warning-status' : ''); ?>">
                        <div class="stat-number"><?php echo $estadoMesas['stats']['disponibles']; ?></div>
                        <div class="stat-label">Mesas Disponibles</div>
                    </div>
                    <div class="stat-card <?php echo $estadoMesas['stats']['porcentaje_ocupacion'] >= 100 ? 'critical-status' : ($estadoMesas['stats']['porcentaje_ocupacion'] >= 85 ? 'warning-status' : ''); ?>">
                        <div class="stat-number"><?php echo $estadoMesas['stats']['porcentaje_ocupacion']; ?>%</div>
                        <div class="stat-label">Ocupación</div>
                    </div>
                </div>

                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🪑</div>
                        <h3 class="feature-title">Consultar Disponibilidad Mesas</h3>
                        <p class="feature-description">Estado de aforo actual</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#modalMesas">Ver</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Mesas -->
    <div class="modal fade" id="modalMesas" tabindex="-1" aria-labelledby="modalMesasLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="modalMesasLabel">🪑 Disponibilidad de Mesas</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Alerta dentro del modal si no hay disponibilidad -->
                    <?php if ($estadoMesas['stats']['disponibles'] == 0): ?>
                        <div class="alert alert-danger" role="alert">
                            <div class="d-flex align-items-center">
                                <span style="font-size: 1.5rem; margin-right: 10px;">🚫</span>
                                <div>
                                    <strong>¡Restaurante lleno!</strong> 
                                    No hay mesas disponibles en este momento.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Resumen por Tipo -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">💺 Mesas Normales</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="badge bg-success fs-6 p-2 w-100">
                                                Disponibles<br><?php echo $estadoMesas['stats']['normales_disponibles']; ?>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="badge bg-danger fs-6 p-2 w-100">
                                                Ocupadas<br><?php echo $estadoMesas['stats']['normales_ocupadas']; ?>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="badge bg-info fs-6 p-2 w-100">
                                                Total<br><?php echo ($estadoMesas['stats']['normales_disponibles'] + $estadoMesas['stats']['normales_ocupadas']); ?>
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
                                        <div class="col-4">
                                            <div class="badge bg-success fs-6 p-2 w-100">
                                                Disponibles<br><?php echo $estadoMesas['stats']['madera_disponibles']; ?>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="badge bg-danger fs-6 p-2 w-100">
                                                Ocupadas<br><?php echo $estadoMesas['stats']['madera_ocupadas']; ?>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="badge bg-info fs-6 p-2 w-100">
                                                Total<br><?php echo ($estadoMesas['stats']['madera_disponibles'] + $estadoMesas['stats']['madera_ocupadas']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estado General -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert <?php echo $estadoMesas['stats']['disponibles'] == 0 ? 'alert-danger' : ($estadoMesas['stats']['disponibles'] <= 3 ? 'alert-warning' : 'alert-info'); ?>">
                                <strong>📈 Estado General:</strong> 
                                <?php echo $estadoMesas['stats']['ocupadas']; ?> de <?php echo $estadoMesas['stats']['total']; ?> mesas ocupadas 
                                (<?php echo $estadoMesas['stats']['porcentaje_ocupacion']; ?>% de ocupación)
                                <?php if ($estadoMesas['stats']['disponibles'] == 0): ?>
                                    <br><strong>⚠️ Sin disponibilidad</strong>
                                <?php elseif ($estadoMesas['stats']['disponibles'] <= 3): ?>
                                    <br><strong>⚠️ Disponibilidad limitada</strong>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle de Mesas -->
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-success">✅ Mesas Disponibles</h5>
                            <?php if ($estadoMesas['stats']['disponibles'] == 0): ?>
                                <div class="alert alert-secondary text-center">
                                    <i class="display-4">🚫</i>
                                    <p class="mb-0 mt-2">No hay mesas disponibles</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-sm table-striped">
                                        <thead class="table-success">
                                            <tr>
                                                <th>Mesa</th>
                                                <th>Tipo</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($estadoMesas['mesas'] as $mesa): ?>
                                                <?php if ($mesa['estado'] == 'DISPONIBLE'): ?>
                                                    <tr>
                                                        <td><strong>#<?php echo $mesa['id']; ?></strong></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $mesa['tipo'] == 'NORMAL' ? 'primary' : 'warning'; ?>">
                                                                <?php echo $mesa['tipo']; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success">DISPONIBLE</span>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <h5 class="text-danger">🔴 Mesas Ocupadas</h5>
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-striped">
                                    <thead class="table-danger">
                                        <tr>
                                            <th>Mesa</th>
                                            <th>Tipo</th>
                                            <th>Mesero</th>
                                            <th>Asignada</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($estadoMesas['mesas'] as $mesa): ?>
                                            <?php if ($mesa['estado'] == 'OCUPADA'): ?>
                                                <tr>
                                                    <td><strong>#<?php echo $mesa['id']; ?></strong></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $mesa['tipo'] == 'NORMAL' ? 'primary' : 'warning'; ?>">
                                                            <?php echo $mesa['tipo']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small><?php echo $mesa['nombre_mesero'] ? htmlspecialchars($mesa['nombre_mesero']) : 'Sin asignar'; ?></small>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            <?php 
                                                            if ($mesa['fecha_asignacion']) {
                                                                echo date('H:i', strtotime($mesa['fecha_asignacion']));
                                                            } else {
                                                                echo '-';
                                                            }
                                                            ?>
                                                        </small>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="location.reload()">Actualizar Estado</button>
                    <?php if ($estadoMesas['stats']['disponibles'] == 0): ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para mostrar notificaciones automáticas
        function mostrarNotificacionDisponibilidad() {
            const disponibles = <?php echo $estadoMesas['stats']['disponibles']; ?>;
            const total = <?php echo $estadoMesas['stats']['total']; ?>;
            const porcentaje = <?php echo $estadoMesas['stats']['porcentaje_ocupacion']; ?>;
            
            if (disponibles === 0) {
                // Notificación crítica
                if (Notification.permission === "granted") {
                    new Notification("🚨 Plaza Andina - Sin Disponibilidad", {
                        body: `Todas las mesas están ocupadas (${porcentaje}% ocupación)`,
                        icon: "🚨"
                    });
                }
            } else if (disponibles <= 3) {
                // Notificación de advertencia
                if (Notification.permission === "granted") {
                    new Notification("⚠️ Plaza Andina - Pocas Mesas", {
                        body: `Solo ${disponibles} mesas disponibles (${porcentaje}% ocupación)`,
                        icon: "⚠️"
                    });
                }
            }
        }

        // Solicitar permisos de notificación y mostrar si es necesario
        document.addEventListener('DOMContentLoaded', function() {
            if ("Notification" in window) {
                if (Notification.permission === "default") {
                    Notification.requestPermission().then(function(permission) {
                        if (permission === "granted") {
                            mostrarNotificacionDisponibilidad();
                        }
                    });
                } else if (Notification.permission === "granted") {
                    mostrarNotificacionDisponibilidad();
                }
            }
        });

        // Auto-refresh cada 15 segundos para mantener actualizado el estado
        setInterval(function() {
            location.reload();
        }, 30000); // 10000 ms = 10 segundos
    </script>
</body>
</html>