<?php 
// admin-dashboard.php - Dashboard completo para administradores
session_start();

// Verificar que es administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

require 'backend/db.php';

// Obtener métricas para el dashboard
try {
    // Métricas generales
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN subscription_status = 'active' THEN 1 END) as active_users,
            COUNT(CASE WHEN subscription_type = 'despertar' AND subscription_status = 'active' THEN 1 END) as despertar_users,
            COUNT(CASE WHEN subscription_type = 'evolucionar' AND subscription_status = 'active' THEN 1 END) as evolucionar_users,
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_registrations,
            COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_registrations
        FROM users
    ");
    $metrics = $stmt->fetch();
    
    // Ingresos estimados
    $revenue_despertar = $metrics['despertar_users'] * 75.00;
    $revenue_evolucionar = $metrics['evolucionar_users'] * 125.00;
    $total_revenue = $revenue_despertar + $revenue_evolucionar;
    
    // Archivos subidos
    $stmt = $pdo->query("SELECT COUNT(*) as total_files FROM admin_uploads WHERE status = 'active'");
    $files_count = $stmt->fetch()['total_files'] ?? 0;
    
    // Actividad reciente (últimas 10)
    $stmt = $pdo->query("
        SELECT ua.*, u.first_name, u.last_name, u.email 
        FROM user_activity ua 
        JOIN users u ON ua.user_id = u.id 
        ORDER BY ua.created_at DESC 
        LIMIT 10
    ");
    $recent_activity = $stmt->fetchAll();
    
    // Usuarios recientes (últimos 8)
    $stmt = $pdo->query("
        SELECT id, email, first_name, last_name, subscription_type, created_at 
        FROM users 
        WHERE user_role = 'user'
        ORDER BY created_at DESC 
        LIMIT 8
    ");
    $recent_users = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error obteniendo métricas: " . $e->getMessage());
    $metrics = ['total_users' => 0, 'active_users' => 0, 'despertar_users' => 0, 'evolucionar_users' => 0, 'today_registrations' => 0, 'week_registrations' => 0];
    $total_revenue = 0;
    $files_count = 0;
    $recent_activity = [];
    $recent_users = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Gente Vigente</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard-admin.css">
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <img src="img/LogoGVNB.png" alt="Gente Vigente" class="logo">
            <div class="admin-badge">ADMIN</div>
        </div>
        
        <div class="sidebar-menu">
            <a href="#" class="menu-item active" data-section="overview">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M280-280h80v-200h-80v200Zm320 0h80v-400h-80v400Zm-160 0h80v-120h-80v120Zm0-200h80v-80h-80v80ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm0-560v560-560Z"/></svg></span>
                <span>Resumen General</span>
            </a>
            
            <a href="#" class="menu-item" data-section="users">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm720 0v-120q0-44-24.5-84.5T666-434q51 6 96 20.5t84 35.5q36 20 55 44.5t19 53.5v120H760ZM360-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm400-160q0 66-47 113t-113 47q-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-81T544-792q14-5 28-6.5t28-1.5q66 0 113 47t47 113ZM120-240h480v-32q0-11-5.5-20T580-306q-54-27-109-40.5T360-360q-56 0-111 13.5T140-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T440-640q0-33-23.5-56.5T360-720q-33 0-56.5 23.5T280-640q0 33 23.5 56.5T360-560Zm0 320Zm0-400Z"/></svg></span>
                <span>Gestión de Usuarios</span>
            </a>
            
            <a href="#" class="menu-item" data-section="content">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h240l80 80h320q33 0 56.5 23.5T880-640v400q0 33-23.5 56.5T800-160H160Zm0-80h640v-400H447l-80-80H160v480Zm0 0v-480 480Z"/></svg></span>
                <span>Gestión de Contenido</span>
            </a>
            
            <a href="#" class="menu-item" data-section="uploads">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M440-320v-326L336-542l-56-58 200-200 200 200-56 58-104-104v326h-80ZM240-160q-33 0-56.5-23.5T160-240v-120h80v120h480v-120h80v120q0 33-23.5 56.5T720-160H240Z"/></svg></span>
                <span>Subir Archivos</span>
            </a>
            
            <a href="#" class="menu-item" data-section="analytics">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M120-120v-80l80-80v160h-80Zm160 0v-240l80-80v320h-80Zm160 0v-320l80 81v239h-80Zm160 0v-239l80-80v319h-80Zm160 0v-400l80-80v480h-80ZM120-327v-113l280-280 160 160 280-280v113L560-447 400-607 120-327Z"/></svg></span>
                <span>Analytics</span>
            </a>
            
            <a href="#" class="menu-item" data-section="settings">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m370-80-16-128q-13-5-24.5-12T307-235l-119 50L78-375l103-78q-1-7-1-13.5v-27q0-6.5 1-13.5L78-585l110-190 119 50q11-8 23-15t24-12l16-128h220l16 128q13 5 24.5 12t22.5 15l119-50 110 190-103 78q1 7 1 13.5v27q0 6.5-2 13.5l103 78-110 190-118-50q-11 8-23 15t-24 12L590-80H370Zm70-80h79l14-106q31-8 57.5-23.5T639-327l99 41 39-68-86-65q5-14 7-29.5t2-31.5q0-16-2-31.5t-7-29.5l86-65-39-68-99 42q-22-23-48.5-38.5T533-694l-13-106h-79l-14 106q-31 8-57.5 23.5T321-633l-99-41-39 68 86 64q-5 15-7 30t-2 32q0 16 2 31t7 30l-86 65 39 68 99-42q22 23 48.5 38.5T427-266l13 106Zm42-180q58 0 99-41t41-99q0-58-41-99t-99-41q-59 0-99.5 41T342-480q0 58 40.5 99t99.5 41Zm-2-140Z"/></svg></span>
                <span>Configuración</span>
            </a>
            
            <div class="menu-divider"></div>
            
            <a href="dashboard-user-preview.php" class="menu-item" target="_blank">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z"/></svg></span>
                <span>Vista de Usuario</span>
            </a>
            
            <a href="backend/logout.php" class="menu-item logout">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/></svg></span>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </nav>

    <!-- Top Bar -->
    <header class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Panel de Administración</h1>
        </div>
        <div class="user-info">
            <div class="user-avatar">
                <span><?php 
                    $fullName = $_SESSION['user_name'];
                    $nameParts = explode(' ', trim($fullName));
                    echo strtoupper(substr($nameParts[0], 0, 1));
                    if (count($nameParts) > 1) {
                        echo strtoupper(substr($nameParts[count($nameParts)-1], 0, 1));
                    }
                ?></span>
            </div>
            <div class="user-details">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <span class="user-role">Administrador</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Resumen General -->
        <section id="overview-section" class="content-section active">
            <div class="metrics-grid">
                <div class="metric-card users">
                    <div class="metric-header">
                        <h3>Usuarios Totales</h3>
                        <span class="metric-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#3b82f6"><path d="M40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm720 0v-120q0-44-24.5-84.5T666-434q51 6 96 20.5t84 35.5q36 20 55 44.5t19 53.5v120H760ZM360-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm400-160q0 66-47 113t-113 47q-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-81T544-792q14-5 28-6.5t28-1.5q66 0 113 47t47 113ZM120-240h480v-32q0-11-5.5-20T580-306q-54-27-109-40.5T360-360q-56 0-111 13.5T140-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T440-640q0-33-23.5-56.5T360-720q-33 0-56.5 23.5T280-640q0 33 23.5 56.5T360-560Zm0 320Zm0-400Z"/></svg></span>
                    </div>
                    <div class="metric-value"><?php echo number_format($metrics['total_users']); ?></div>
                    <div class="metric-change positive">
                        +<?php echo $metrics['today_registrations']; ?> hoy
                    </div>
                </div>

                <div class="metric-card active">
                    <div class="metric-header">
                        <h3>Usuarios Activos</h3>
                        <span class="metric-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#22c55e"><path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/></svg></span>
                    </div>
                    <div class="metric-value"><?php echo number_format($metrics['active_users']); ?></div>
                    <div class="metric-change">
                        <?php echo round(($metrics['active_users'] / max($metrics['total_users'], 1)) * 100, 1); ?>% del total
                    </div>
                </div>

                <div class="metric-card revenue">
                    <div class="metric-header">
                        <h3>Ingresos Mensuales</h3>
                        <span class="metric-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#c78b42"><path d="M336-120q-91 0-153.5-62.5T120-336q0-38 13-74t37-65l142-171-97-194h530l-97 194 142 171q24 29 37 65t13 74q0 91-63 153.5T624-120H336Zm144-200q-33 0-56.5-23.5T400-400q0-33 23.5-56.5T480-480q33 0 56.5 23.5T560-400q0 33-23.5 56.5T480-320Zm-95-360h190l40-80H345l40 80Zm-49 480h288q57 0 96.5-39.5T760-336q0-24-8.5-46.5T728-423L581-600H380L232-424q-15 18-23.5 41t-8.5 47q0 57 39.5 96.5T336-200Z"/></svg></span>
                    </div>
                    <div class="metric-value">$<?php echo number_format($total_revenue, 2); ?></div>
                    <div class="metric-change">
                        Despertar: <?php echo $metrics['despertar_users']; ?> | Evolucionar: <?php echo $metrics['evolucionar_users']; ?>
                    </div>
                </div>

                <div class="metric-card files">
                    <div class="metric-header">
                        <h3>Archivos Subidos</h3>
                        <span class="metric-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h240l80 80h320q33 0 56.5 23.5T880-640v400q0 33-23.5 56.5T800-160H160Zm0-80h640v-400H447l-80-80H160v480Zm0 0v-480 480Z"/></svg></span>
                    </div>
                    <div class="metric-value"><?php echo number_format($files_count); ?></div>
                    <div class="metric-change">
                        Contenido disponible
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Actividad Reciente -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Actividad Reciente</h3>
                    </div>
                    <div class="activity-list">
                        <?php if (empty($recent_activity)): ?>
                            <div class="no-activity">No hay actividad reciente</div>
                        <?php else: ?>
                            <?php foreach ($recent_activity as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <?php
                                        $icon = '🔔';
                                        switch ($activity['activity_type']) {
                                            case 'login': $icon = '🔐'; break;
                                            case 'logout': $icon = '🚪'; break;
                                            case 'password_change': $icon = '🔑'; break;
                                            case 'profile_update': $icon = '👤'; break;
                                        }
                                        echo $icon;
                                        ?>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-description">
                                            <strong><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></strong>
                                            <?php echo htmlspecialchars($activity['description'] ?: $activity['activity_type']); ?>
                                        </div>
                                        <div class="activity-time">
                                            <?php echo date('H:i', strtotime($activity['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Usuarios Recientes -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Usuarios Recientes</h3>
                    </div>
                    <div class="users-list">
                        <?php if (empty($recent_users)): ?>
                            <div class="no-users">No hay usuarios recientes</div>
                        <?php else: ?>
                            <?php foreach ($recent_users as $user): ?>
                                <div class="user-item">
                                    <div class="user-avatar">
                                        <?php 
                                        $name = $user['first_name'] . ' ' . $user['last_name'];
                                        $nameParts = explode(' ', trim($name));
                                        echo strtoupper(substr($nameParts[0], 0, 1));
                                        if (count($nameParts) > 1) {
                                            echo strtoupper(substr($nameParts[count($nameParts)-1], 0, 1));
                                        }
                                        ?>
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name"><?php echo htmlspecialchars($name); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                    <div class="user-plan <?php echo $user['subscription_type']; ?>">
                                        <?php echo ucfirst($user['subscription_type']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Gestión de Usuarios -->
        <section id="users-section" class="content-section">
            <div class="section-header">
                <h2>Gestión de Usuarios</h2>
                <button class="btn-primary" onclick="openUserModal()">Agregar Usuario</button>
            </div>
            
            <div class="users-table-container">
                <table class="users-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Plan</th>
                            <th>Estado</th>
                            <th>Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Contenido cargado dinámicamente -->
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Gestión de Contenido -->
        <section id="content-section" class="content-section">
            <div class="section-header">
                <h2>Gestión de Contenido</h2>
                <button class="btn-primary" onclick="openContentModal()">Agregar Contenido</button>
            </div>
            
            <div class="content-grid" id="contentGrid">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </section>

        <!-- Subir Archivos -->
        <section id="uploads-section" class="content-section">
            <div class="section-header">
                <h2>Subir Archivos</h2>
            </div>
            
            <div class="upload-area">
                <div class="upload-card">
                    <div class="upload-zone" id="uploadZone">
                    <div class="upload-icon">
    <svg xmlns="http://www.w3.org/2000/svg" height="64px" viewBox="0 -960 960 960" width="64px" fill="currentColor"><path d="M440-320v-326L336-542l-56-58 200-200 200 200-56 58-104-104v326h-80ZM240-160q-33 0-56.5-23.5T160-240v-120h80v120h480v-120h80v120q0 33-23.5 56.5T720-160H240Z"/></svg></div>
                        <h3>Arrastra archivos aquí o haz clic para seleccionar</h3>
                        <p>Archivos permitidos: PDF, DOC, DOCX, MP4, MP3, JPG, PNG</p>
                        <input type="file" id="fileInput" multiple accept=".pdf,.doc,.docx,.mp4,.mp3,.jpg,.jpeg,.png">
                    </div>
                    
                    <div class="upload-form" id="uploadForm" style="display: none;">
                        <div class="form-group">
                            <label for="fileTitle">Título:</label>
                            <input type="text" id="fileTitle" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="fileDescription">Descripción:</label>
                            <textarea id="fileDescription" rows="3"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fileCategory">Categoría:</label>
                                <select id="fileCategory" required>
                                    <option value="">Seleccionar categoría</option>
                                    <option value="grabacion_zoom">Grabación Zoom/Teams</option>
                                    <option value="webinar">Webinar</option>
                                    <option value="masterclass">Masterclass</option>
                                    <option value="podcast">Podcast/Audio</option>
                                    <option value="documento">Documento PDF</option>
                                    <option value="material">Material de Estudio</option>
                                    <option value="guia">Guía/Manual</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="fileAccess">Acceso:</label>
                                <select id="fileAccess" required>
                                    <option value="all">Todos los usuarios</option>
                                    <option value="despertar">Solo Despertar</option>
                                    <option value="evolucionar">Solo Evolucionar</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" onclick="cancelUpload()">Cancelar</button>
                            <button type="button" class="btn-primary" onclick="submitUpload()">Subir Archivo</button>
                        </div>
                    </div>
                </div>
                
                <div class="upload-progress" id="uploadProgress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <div class="progress-text" id="progressText">Subiendo...</div>
                </div>
            </div>
        </section>

        <!-- Analytics -->
        <section id="analytics-section" class="content-section">
            <div class="section-header">
                <h2>Analytics</h2>
                <div class="date-selector">
                    <select id="analyticsRange">
                        <option value="7">Últimos 7 días</option>
                        <option value="30" selected>Últimos 30 días</option>
                        <option value="90">Últimos 90 días</option>
                    </select>
                </div>
            </div>
            
            <div class="analytics-grid">
                <div class="chart-card">
                    <h3>Registros por Día</h3>
                    <canvas id="registrationsChart"></canvas>
                </div>
                
                <div class="chart-card">
                    <h3>Distribución de Planes</h3>
                    <canvas id="plansChart"></canvas>
                </div>
                
                <div class="chart-card">
                    <h3>Ingresos Mensuales</h3>
                    <canvas id="revenueChart"></canvas>
                </div>
                
                <div class="chart-card">
                    <h3>Actividad de Usuarios</h3>
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
        </section>

        <!-- Configuración -->
        <section id="settings-section" class="content-section">
            <div class="section-header">
                <h2>Configuración del Sistema</h2>
            </div>
            
            <div class="settings-grid">
                <div class="settings-card">
                    <h3>Configuración General</h3>
                    <form id="generalSettings">
                        <div class="form-group">
                            <label for="siteName">Nombre del Sitio:</label>
                            <input type="text" id="siteName" value="Gente Vigente">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="maintenanceMode">
                                Modo de Mantenimiento
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="userRegistration" checked>
                                Permitir Registro de Usuarios
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-primary">Guardar Configuración</button>
                    </form>
                </div>
                
                <div class="settings-card">
                    <h3>Precios de Membresías</h3>
                    <form id="pricingSettings">
                        <div class="form-group">
                            <label for="despertarPrice">Precio Plan Despertar ($):</label>
                            <input type="number" id="despertarPrice" value="75.00" step="0.01" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="evolucionarPrice">Precio Plan Evolucionar ($):</label>
                            <input type="number" id="evolucionarPrice" value="125.00" step="0.01" min="0">
                        </div>
                        
                        <button type="submit" class="btn-primary">Actualizar Precios</button>
                    </form>
                </div>
                
                <div class="settings-card">
                    <h3>Configuración de Archivos</h3>
                    <form id="fileSettings">
                        <div class="form-group">
                            <label for="maxFileSize">Tamaño Máximo de Archivo (MB):</label>
                            <input type="number" id="maxFileSize" value="50" min="1" max="500">
                        </div>
                        
                        <div class="form-group">
                            <label for="allowedTypes">Tipos de Archivo Permitidos:</label>
                            <textarea id="allowedTypes" rows="2" readonly>PDF, DOC, DOCX, MP4, MP3, JPG, PNG, JPEG</textarea>
                        </div>
                        
                        <button type="submit" class="btn-primary">Actualizar Configuración</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal para Agregar/Editar Usuario -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="userModalTitle">Agregar Usuario</h3>
                <button class="modal-close" onclick="closeUserModal()">&times;</button>
            </div>
            <form id="userForm" class="modal-body">
    <div class="form-row">
        <div class="form-group">
            <label for="userFirstName">Nombre:</label>
            <input type="text" id="userFirstName" required>
        </div>
        <div class="form-group">
            <label for="userLastName">Apellido:</label>
            <input type="text" id="userLastName" required>
        </div>
    </div>
    
    <div class="form-group">
        <label for="userEmail">Email:</label>
        <input type="email" id="userEmail" required>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="userRole">Rol:</label>
            <select id="userRole" required>
                <option value="user">Usuario</option>
                <option value="admin">Administrador</option>
            </select>
        </div>
        <div class="form-group">
            <label for="userPlan">Plan:</label>
            <select id="userPlan" required>
                <option value="despertar">Despertar</option>
                <option value="evolucionar">Evolucionar</option>
            </select>
        </div>
    </div>
    
    <div class="form-group">
        <label for="userStatus">Estado:</label>
        <select id="userStatus" required>
            <option value="active">Activo</option>
            <option value="inactive">Inactivo</option>
            <option value="suspended">Suspendido</option>
        </select>
    </div>
    
    <div class="temp-password-info" style="background: #f0f9ff; border: 1px solid #0369a1; border-radius: 8px; padding: 1rem; margin-top: 1rem;">
        <h4 style="margin: 0 0 0.5rem 0; color: #0369a1;">Información importante:</h4>
        <p style="margin: 0; font-size: 0.9rem; color: #0369a1;">Se generará una contraseña temporal automáticamente. El usuario deberá cambiarla en su primer login.</p>
    </div>
</form>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeUserModal()">Cancelar</button>
                <button type="button" class="btn-primary" onclick="saveUser()">Guardar Usuario</button>
            </div>
        </div>
    </div>

    <script src="js/dashboard-admin.js"></script>
    
    <!-- Script para datos del administrador -->
    <script>
        window.adminData = {
            name: '<?php echo addslashes($_SESSION['user_name']); ?>',
            email: '<?php echo addslashes($_SESSION['user_email']); ?>',
            role: 'admin',
            metrics: <?php echo json_encode($metrics); ?>,
            totalRevenue: <?php echo $total_revenue; ?>
        };
        
        console.log('Admin Dashboard cargado:', window.adminData);
    </script>
    
    <script>
// Funciones de contenido - Script inline temporal
function deleteContent(contentId) {
    console.log('Eliminando contenido ID:', contentId);
    
    if (!confirm('¿Estás seguro de que quieres eliminar este contenido? Esta acción no se puede deshacer.')) {
        return;
    }
    
    fetch('backend/admin/delete-content.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ contentId: contentId })
    })
    .then(response => {
        console.log('Respuesta:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Datos recibidos:', data);
        if (data.success) {
            showNotification('Contenido eliminado correctamente', 'success');
            // Recargar contenido
            loadContentGrid();
        } else {
            showNotification(data.message || 'Error al eliminar contenido', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión: ' + error.message, 'error');
    });
}

function editContent(contentId) {
    showNotification('Funcionalidad de edición en desarrollo', 'info');
    console.log('Editando contenido ID:', contentId);
}

function openContentModal() {
    showNotification('Modal de contenido en desarrollo. Usa "Subir Archivos" por ahora.', 'info');
}

// Función showNotification si no existe
if (typeof showNotification === 'undefined') {
    function showNotification(message, type = 'info') {
        alert(message); // Fallback temporal
    }
}

// Función loadContentGrid si no existe
if (typeof loadContentGrid === 'undefined') {
    function loadContentGrid() {
        location.reload(); // Fallback: recargar página
    }
}

console.log('Funciones de contenido cargadas inline');
</script>
    
</body>
</html>