<?php
// Conexón a la base de datos
include_once 'conexion.php';

// Iniciar la sesión, permitiendo el uso de variables de sesión
session_start();

// Desarrollar la lógica para el rol de cocina a partir de aquí


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../css/styles1.css">
    <title>Cocina - Plaza Andina</title>
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
                        <div style="font-weight: 600;">Chef de Cocina <?php echo htmlspecialchars($_SESSION["cocina_name"] ?? 'Usuario'); ?></div>
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
        <div class="dashboard-card">
            <div class="role-header">
                <h1 class="role-title">👨‍🍳 Cocina</h1>
                <p class="role-subtitle">Preparación de Alimentos</p>
            </div>

            <div class="quick-stats">
                <div class="stat-card">
                    <div class="stat-number">15</div>
                    <div class="stat-label">Órdenes en Cola</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">8</div>
                    <div class="stat-label">Platos Preparando</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">3</div>
                    <div class="stat-label">Platos Listos</div>
                </div>
            </div>

            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">📋</div>
                    <h3 class="feature-title">Órdenes Nuevas</h3>
                    <p class="feature-description">Ver y gestionar órdenes recién llegadas de los meseros</p>
                    <a href="#" class="btn-dashboard">Ver Órdenes</a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔥</div>
                    <h3 class="feature-title">En Preparación</h3>
                    <p class="feature-description">Monitorear platos que se están cocinando actualmente</p>
                    <a href="#" class="btn-dashboard">En Cocción</a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">✅</div>
                    <h3 class="feature-title">Platos Listos</h3>
                    <p class="feature-description">Marcar platos terminados y listos para servir</p>
                    <a href="#" class="btn-dashboard">Completar</a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📦</div>
                    <h3 class="feature-title">Inventario Cocina</h3>
                    <p class="feature-description">Verificar disponibilidad de ingredientes y suministros</p>
                    <a href="#" class="btn-dashboard">Ver Stock</a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📝</div>
                    <h3 class="feature-title">Recetas</h3>
                    <p class="feature-description">Consultar recetas y especificaciones de platos</p>
                    <a href="#" class="btn-dashboard">Recetas</a>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">⏰</div>
                    <h3 class="feature-title">Tiempos de Cocción</h3>
                    <p class="feature-description">Control de tiempos para optimizar la preparación</p>
                    <a href="#" class="btn-dashboard">Cronómetro</a>
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