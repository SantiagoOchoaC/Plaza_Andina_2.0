<?php
// Conexón a la base de datos
include_once 'conexion.php';

// Iniciar la sesión, permitiendo el uso de variables de sesión
session_start();

// Desarrollar la lógica para el rol de barra a partir de aquí


?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../css/styles1.css">
    <title>Barra - Plaza Andina</title>
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
                        <div style="font-weight: 600;">Jefe de Barra <?php echo htmlspecialchars($_SESSION["barra_name"] ?? 'Usuario'); ?></div>
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
                    <h1 class="role-title">🍺 Barra</h1>
                    <p class="role-subtitle">Servicio de Bebidas y Bar</p>
                </div>

                <div class="quick-stats">
                    <div class="stat-card">
                        <div class="stat-number">12</div>
                        <div class="stat-label">Bebidas Pendientes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">8</div>
                        <div class="stat-label">Clientes en Barra</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">24</div>
                        <div class="stat-label">Bebidas Servidas</div>
                    </div>
                </div>

                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🍹</div>
                        <h3 class="feature-title">Órdenes de Bebidas</h3>
                        <p class="feature-description">Gestionar pedidos de bebidas de meseros y clientes directos</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#modalOrdenes">Ver Órdenes</a>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🍾</div>
                        <h3 class="feature-title">Inventario de Licores</h3>
                        <p class="feature-description">Control de stock de bebidas alcohólicas y no alcohólicas</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#modalInventario">Ver Stock</a>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">📋</div>
                        <h3 class="feature-title">Carta de Bebidas</h3>
                        <p class="feature-description">Consultar menú de bebidas y precios actualizados</p>
                        <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#modalCarta">Ver Carta</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Órdenes de Bebidas -->
    <div class="modal fade" id="modalOrdenes" tabindex="-1" aria-labelledby="modalOrdenesLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="modalOrdenesLabel">🍹 Órdenes de Bebidas</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">⏳ Pendientes (12)</h6>
                                </div>
                                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                    <div class="mb-3 p-2 border rounded">
                                        <div class="d-flex justify-content-between">
                                            <strong>Mesa 5</strong>
                                            <span class="badge bg-warning">15:30</span>
                                        </div>
                                        <small>2x Cerveza Corona<br>1x Mojito<br>1x Coca Cola</small>
                                        <div class="mt-2">
                                            <button class="btn btn-sm btn-success">Completar</button>
                                        </div>
                                    </div>
                                    <div class="mb-3 p-2 border rounded">
                                        <div class="d-flex justify-content-between">
                                            <strong>Mesa 12</strong>
                                            <span class="badge bg-warning">15:35</span>
                                        </div>
                                        <small>1x Whisky Sour<br>2x Agua con Gas</small>
                                        <div class="mt-2">
                                            <button class="btn btn-sm btn-success">Completar</button>
                                        </div>
                                    </div>
                                    <div class="mb-3 p-2 border rounded">
                                        <div class="d-flex justify-content-between">
                                            <strong>Barra Directa</strong>
                                            <span class="badge bg-warning">15:40</span>
                                        </div>
                                        <small>1x Margarita<br>1x Cerveza Artesanal</small>
                                        <div class="mt-2">
                                            <button class="btn btn-sm btn-success">Completar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">🔄 En Preparación (3)</h6>
                                </div>
                                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                    <div class="mb-3 p-2 border rounded">
                                        <div class="d-flex justify-content-between">
                                            <strong>Mesa 8</strong>
                                            <span class="badge bg-primary">15:25</span>
                                        </div>
                                        <small>2x Piña Colada<br>1x Daiquiri</small>
                                        <div class="mt-2">
                                            <button class="btn btn-sm btn-success">Listo</button>
                                        </div>
                                    </div>
                                    <div class="mb-3 p-2 border rounded">
                                        <div class="d-flex justify-content-between">
                                            <strong>Mesa 15</strong>
                                            <span class="badge bg-primary">15:32</span>
                                        </div>
                                        <small>1x Long Island<br>2x Jugo Naranja</small>
                                        <div class="mt-2">
                                            <button class="btn btn-sm btn-success">Listo</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">✅ Completadas (24)</h6>
                                </div>
                                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                    <div class="mb-2 p-2 border rounded bg-light">
                                        <div class="d-flex justify-content-between">
                                            <strong>Mesa 3</strong>
                                            <span class="badge bg-success">15:20</span>
                                        </div>
                                        <small>2x Cerveza Nacional</small>
                                    </div>
                                    <div class="mb-2 p-2 border rounded bg-light">
                                        <div class="d-flex justify-content-between">
                                            <strong>Mesa 7</strong>
                                            <span class="badge bg-success">15:15</span>
                                        </div>
                                        <small>1x Caipirinha<br>1x Gaseosa</small>
                                    </div>
                                    <div class="mb-2 p-2 border rounded bg-light">
                                        <div class="d-flex justify-content-between">
                                            <strong>Barra</strong>
                                            <span class="badge bg-success">15:10</span>
                                        </div>
                                        <small>3x Shot Tequila</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary">Actualizar Estado</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Inventario -->
    <div class="modal fade" id="modalInventario" tabindex="-1" aria-labelledby="modalInventarioLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="modalInventarioLabel">🍾 Inventario de Licores</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">🔍</span>
                                <input type="text" class="form-control" placeholder="Buscar producto...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select">
                                <option>Todas las categorías</option>
                                <option>Cervezas</option>
                                <option>Licores</option>
                                <option>Vinos</option>
                                <option>Sin Alcohol</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Categoría</th>
                                        <th>Stock Actual</th>
                                        <th>Stock Mínimo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>🍺 Cerveza Corona</td>
                                        <td>Cervezas</td>
                                        <td>48</td>
                                        <td>20</td>
                                        <td><span class="badge bg-success">Normal</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Editar</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>🥃 Whisky Etiqueta Negra</td>
                                        <td>Licores</td>
                                        <td>12</td>
                                        <td>5</td>
                                        <td><span class="badge bg-success">Normal</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Editar</button>
                                        </td>
                                    </tr>
                                    <tr class="table-warning">
                                        <td>🍷 Vino Tinto Reserva</td>
                                        <td>Vinos</td>
                                        <td>8</td>
                                        <td>10</td>
                                        <td><span class="badge bg-warning">Bajo Stock</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning">Reabastecer</button>
                                        </td>
                                    </tr>
                                    <tr class="table-danger">
                                        <td>🍹 Ron Bacardí</td>
                                        <td>Licores</td>
                                        <td>2</td>
                                        <td>8</td>
                                        <td><span class="badge bg-danger">Crítico</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-danger">Urgente</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>🥤 Coca Cola</td>
                                        <td>Sin Alcohol</td>
                                        <td>36</td>
                                        <td>15</td>
                                        <td><span class="badge bg-success">Normal</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Editar</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>🍺 Cerveza Artesanal IPA</td>
                                        <td>Cervezas</td>
                                        <td>24</td>
                                        <td>12</td>
                                        <td><span class="badge bg-success">Normal</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Editar</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>🍸 Vodka Premium</td>
                                        <td>Licores</td>
                                        <td>15</td>
                                        <td>6</td>
                                        <td><span class="badge bg-success">Normal</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Editar</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <strong>📊 Resumen:</strong> 
                                Total productos: 145 | 
                                Stock Normal: 5 | 
                                Bajo Stock: 1 | 
                                Stock Crítico: 1
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-warning">Generar Orden Compra</button>
                    <button type="button" class="btn btn-primary">Actualizar Inventario</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Carta de Bebidas -->
    <div class="modal fade" id="modalCarta" tabindex="-1" aria-labelledby="modalCartaLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="modalCartaLabel">📋 Carta de Bebidas</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Cervezas -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">🍺 Cervezas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Corona Extra</span>
                                        <span class="fw-bold">$8,500</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Cerveza Nacional</span>
                                        <span class="fw-bold">$6,000</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Heineken</span>
                                        <span class="fw-bold">$9,500</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Cerveza Artesanal IPA</span>
                                        <span class="fw-bold">$12,000</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Stella Artois</span>
                                        <span class="fw-bold">$10,500</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cocteles -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">🍹 Cocteles</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Mojito</span>
                                        <span class="fw-bold">$15,000</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Piña Colada</span>
                                        <span class="fw-bold">$16,500</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Margarita</span>
                                        <span class="fw-bold">$14,000</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Caipirinha</span>
                                        <span class="fw-bold">$13,500</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Long Island</span>
                                        <span class="fw-bold">$18,000</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Licores -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-dark text-white">
                                    <h5 class="mb-0">🥃 Licores</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Whisky Etiqueta Negra</span>
                                        <span class="fw-bold">$25,000</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Ron Bacardí</span>
                                        <span class="fw-bold">$12,000</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Vodka Premium</span>
                                        <span class="fw-bold">$15,000</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Tequila Reposado</span>
                                        <span class="fw-bold">$18,500</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Aguardiente</span>
                                        <span class="fw-bold">$8,000</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bebidas Sin Alcohol -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">🥤 Sin Alcohol</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Coca Cola</span>
                                        <span class="fw-bold">$4,500</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Jugo Natural Naranja</span>
                                        <span class="fw-bold">$6,000</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Agua con Gas</span>
                                        <span class="fw-bold">$3,500</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Limonada Natural</span>
                                        <span class="fw-bold">$5,500</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Café Espresso</span>
                                        <span class="fw-bold">$4,000</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-primary">
                        <strong>💡 Nota:</strong> Los precios incluyen IVA. Promociones especiales disponibles de 2x1 en cervezas nacionales los viernes.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success">Imprimir Carta</button>
                    <button type="button" class="btn btn-primary">Actualizar Precios</button>
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