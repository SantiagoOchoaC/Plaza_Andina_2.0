<?php
// Conexón a la base de datos
include_once 'conexion.php';

// Iniciar la sesión, permitiendo el uso de variables de sesión
session_start();

// Desarrollar la lógica para el rol de cajero a partir de aquí


?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                        <div class="stat-number">24</div>
                        <div class="stat-label">Aforo total</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">19</div>
                        <div class="stat-label">Mesas ocupadas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">5</div>
                        <div class="stat-label">Mesas disponibles</div>
                    </div>
                </div>

                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🧾</div>
                        <h3 class="feature-title">Pedidos Pagados</h3>
                        <p class="feature-description">Ver lista de pedidos que ya han sido pagados</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#modalPagados">Ver Pedidos</a>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🪑</div>
                        <h3 class="feature-title">Mesas Disponibles</h3>
                        <p class="feature-description">Ver y liberar mesas disponibles</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#modalMesas">Ver Mesas</a>
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
                    <!-- Simulación de tabla de pedidos pagados -->
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th># Pedido</th>
                                <th>Mesa</th>
                                <th>Mesero</th>
                                <th>Monto</th>
                                <th>Fecha/Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1023</td>
                                <td>Mesa 4</td>
                                <td>Juan Pérez</td>
                                <td>$45.000</td>
                                <td>2025-07-04 13:22</td>
                            </tr>
                            <tr>
                                <td>1024</td>
                                <td>Mesa 7</td>
                                <td>María López</td>
                                <td>$32.000</td>
                                <td>2025-07-04 13:40</td>
                            </tr>
                            <tr>
                                <td>1025</td>
                                <td>Mesa 10</td>
                                <td>Carlos Ruiz</td>
                                <td>$28.500</td>
                                <td>2025-07-04 14:05</td>
                            </tr>
                            <!-- ...más filas según datos reales... -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="location.reload()">Actualizar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Mesas Disponibles -->
    <div class="modal fade" id="modalMesas" tabindex="-1" aria-labelledby="modalMesasLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="modalMesasLabel">🪑 Mesas Disponibles para Liberar</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="card mb-3">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">Mesas Disponibles</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-success fs-6 p-2">Mesa 1 <button class="btn btn-sm btn-outline-light ms-2">Liberar</button></span>
                                        <span class="badge bg-success fs-6 p-2">Mesa 3 <button class="btn btn-sm btn-outline-light ms-2">Liberar</button></span>
                                        <span class="badge bg-success fs-6 p-2">Mesa 7 <button class="btn btn-sm btn-outline-light ms-2">Liberar</button></span>
                                        <span class="badge bg-success fs-6 p-2">Mesa 12 <button class="btn btn-sm btn-outline-light ms-2">Liberar</button></span>
                                        <span class="badge bg-success fs-6 p-2">Mesa 15 <button class="btn btn-sm btn-outline-light ms-2">Liberar</button></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <strong>📈 Estado Actual:</strong> 5 de 24 mesas disponibles (21% de disponibilidad)
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="location.reload()">Actualizar Estado</button>
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