<?php 
// admin-user-preview.php - Vista de usuario para administradores
session_start();

// Verificar que es administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

require 'backend/db.php';

// Usar datos del admin pero simular usuario regular
$preview_plan = isset($_GET['plan']) ? $_GET['plan'] : 'despertar';

// Debug para desarrollo
$isDebug = ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '.local') !== false);

// Crear datos simulados para la vista
$simulated_user = [
    'first_name' => 'Usuario',
    'last_name' => 'Demo',
    'email' => 'usuario.demo@example.com',
    'subscription_type' => $preview_plan,
    'phone' => '',
    'country' => '',
    'created_at' => date('Y-m-d H:i:s')
];

$_SESSION['preview_mode'] = true;
$_SESSION['preview_plan'] = $preview_plan;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista de Usuario - Gente Vigente (Admin Preview)</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=visibility,visibility_off" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        /* Estilo especial para indicar que es vista previa */
        .admin-preview-banner {
            background: #f59e0b;
            color: white;
            padding: 0.8rem 1rem;
            text-align: center;
            font-weight: 600;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        body {
            padding-top: 50px;
        }
        
        .plan-switcher {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 2px solid #f59e0b;
        }
        
        .plan-switcher select {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Banner de vista previa -->
    <div class="admin-preview-banner">
        🔍 VISTA PREVIA ADMIN - Dashboard de Usuario (Plan: <?php echo ucfirst($preview_plan); ?>)
        <a href="dashboard-admin.php" style="color: white; text-decoration: underline; margin-left: 1rem;">← Volver al Dashboard Admin</a>
    </div>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <img src="img/LogoGVNB.png" alt="Gente Vigente" class="logo">
        </div>
        
        <!-- Switcher de plan -->
        <div class="plan-switcher">
            <label>Vista como:</label>
            <select onchange="switchPlan(this.value)">
                <option value="despertar" <?php echo $preview_plan === 'despertar' ? 'selected' : ''; ?>>Plan Despertar</option>
                <option value="evolucionar" <?php echo $preview_plan === 'evolucionar' ? 'selected' : ''; ?>>Plan Evolucionar</option>
            </select>
        </div>
        
        <div class="sidebar-menu">
            <a href="#" class="menu-item active" data-section="dashboard">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/></svg></span>
                <span>Mi Dashboard</span>
            </a>
            
            <a href="#" class="menu-item" data-section="contenidos">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M320-240h320v-80H320v80Zm0-160h320v-80H320v80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z"/></svg></span>
                <span>Contenidos</span>
            </a>
            
            <a href="#" class="menu-item" data-section="eventos">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Zm0-80h560v-400H200v400Zm0-480h560v-80H200v80Zm0 0v-80 80Zm280 240q-17 0-28.5-11.5T440-440q0-17 11.5-28.5T480-480q17 0 28.5 11.5T520-440q0 17-11.5 28.5T480-320ZM320-200q-17 0-28.5-11.5T280-240q0-17 11.5-28.5T320-280q17 0 28.5 11.5T360-240q0 17-11.5 28.5T320-200Zm160 0q-17 0-28.5-11.5T440-240q0-17 11.5-28.5T480-280q17 0 28.5 11.5T520-240q0 17-11.5 28.5T480-200Zm160 0q-17 0-28.5-11.5T600-240q0-17 11.5-28.5T640-280q17 0 28.5 11.5T680-240q0 17-11.5 28.5T640-200Z"/></svg></span>
                <span>Eventos</span>
            </a>
            
            <a href="#" class="menu-item" data-section="comunidad">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm720 0v-120q0-44-24.5-84.5T666-434q51 6 96 20.5t84 35.5q36 20 55 44.5t19 53.5v120H760ZM360-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm400-160q0 66-47 113t-113 47q-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-81T544-792q14-5 28-6.5t28-1.5q66 0 113 47t47 113ZM120-240h480v-32q0-11-5.5-20T580-306q-54-27-109-40.5T360-360q-56 0-111 13.5T140-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T440-640q0-33-23.5-56.5T360-720q-33 0-56.5 23.5T280-640q0 33 23.5 56.5T360-560Zm0 320Zm0-400Z"/></svg></span>
                <span>Comunidad</span>
            </a>
            
            <a href="#" class="menu-item" data-section="perfil">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z"/></svg></span>
                <span>Mi Perfil</span>
            </a>
            
            <a href="dashboard-admin.php" class="menu-item logout">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/></svg></span>
                <span>Volver Admin</span>
            </a>
        </div>
    </nav>

    <!-- Top Bar -->
    <header class="topbar">
        <div class="user-info">
            <div class="user-avatar">
                <span id="userInitials">UD</span>
            </div>
            <div class="user-details">
                <span class="user-name" id="userName">Usuario Demo</span>
                <span class="user-email" id="userEmail">usuario.demo@example.com</span>
            </div>
            <span class="dropdown-arrow">▼</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Dashboard Section -->
        <section id="dashboard-section" class="content-section active">
            <h1 class="page-title">Dashboard</h1>
            
            <div class="welcome-card">
                <div class="brand-logo">
                    <img src="img/LogoGV.png" alt="Gente Vigente" class="brand-image">
                </div>
                <div class="welcome-content">
                    <h2>Bienvenido al menú exclusivo de GV</h2>
                    <p>
                        Hola Usuario Demo! Tu plan <strong><?php echo ucfirst($preview_plan); ?></strong> está activo.
                        Continúa tu camino hacia el liderazgo personal y la excelencia. 
                        Explora el contenido exclusivo disponible para tu membresía.
                    </p>
                </div>
            </div>
        </section>

        <!-- Contenidos Section -->
        <section id="contenidos-section" class="content-section">
            <h1 class="page-title">Contenidos Exclusivos</h1>
            
            <div class="content-list">
                <!-- Contenido básico disponible para todos -->
                <div class="content-item">
                    <div class="content-icon">🎥</div>
                    <div class="content-info">
                        <h3>Grabación: Fundamentos del Liderazgo Personal</h3>
                        <p>Sesión grabada sobre los pilares fundamentales para desarrollar tu liderazgo personal y profesional.</p>
                        <span class="instructor">Grabación Zoom - 45 min</span>
                    </div>
                    <div class="content-status">Disponible</div>
                </div>

                <div class="content-item">
                    <div class="content-icon">📚</div>
                    <div class="content-info">
                        <h3>PDF: Guía de Desarrollo Personal</h3>
                        <p>Material completo para tu transformación personal paso a paso - 25 páginas.</p>
                        <span class="instructor">Guía de Estudio</span>
                    </div>
                    <div class="content-status">Disponible</div>
                </div>

                <div class="content-item">
                    <div class="content-icon">🎧</div>
                    <div class="content-info">
                        <h3>Podcast: Mentalidad de Éxito</h3>
                        <p>Serie de episodios sobre el desarrollo de una mentalidad ganadora.</p>
                        <span class="instructor">Audio MP3 - 30 min</span>
                    </div>
                    <div class="content-status">Disponible</div>
                </div>

                <!-- Contenido exclusivo para plan Evolucionar -->
                <div class="content-item <?php echo $preview_plan === 'despertar' ? 'evolucionar-only' : ''; ?>">
                    <div class="content-icon">🏆</div>
                    <div class="content-info">
                        <h3>Masterclass: Estrategias Avanzadas de Liderazgo</h3>
                        <p>Técnicas profesionales para liderar equipos y organizaciones de alto rendimiento.</p>
                        <span class="instructor">Grabación Premium - 90 min</span>
                    </div>
                    <div class="content-status <?php echo $preview_plan === 'despertar' ? 'evolucionar' : ''; ?>">
                        <?php echo $preview_plan === 'despertar' ? 'Solo Evolucionar ($125)' : 'Disponible'; ?>
                    </div>
                </div>

                <div class="content-item <?php echo $preview_plan === 'despertar' ? 'evolucionar-only' : ''; ?>">
                    <div class="content-icon">💼</div>
                    <div class="content-info">
                        <h3>PDF: Manual de Negociación Estratégica</h3>
                        <p>Guía completa con técnicas avanzadas de negociación para empresarios.</p>
                        <span class="instructor">Material Premium - 50 páginas</span>
                    </div>
                    <div class="content-status <?php echo $preview_plan === 'despertar' ? 'evolucionar' : ''; ?>">
                        <?php echo $preview_plan === 'despertar' ? 'Solo Evolucionar ($125)' : 'Disponible'; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Eventos Section -->
        <section id="eventos-section" class="content-section">
            <h1 class="page-title">Próximos Eventos</h1>
            
            <div class="events-list">
                <div class="event-item">
                    <div class="event-date">
                        <span class="day">15</span>
                        <span class="month">ENE</span>
                    </div>
                    <div class="event-info">
                        <h3>Webinar: Visión y Propósito</h3>
                        <p>Descubre cómo definir tu visión personal y alinear tus acciones con tu propósito de vida.</p>
                        <span class="instructor">Horario: 7:00 PM (GMT-6)</span>
                    </div>
                    <button class="event-btn">Registrarse</button>
                </div>

                <div class="event-item <?php echo $preview_plan === 'despertar' ? 'evolucionar-event' : ''; ?>">
                    <div class="event-date">
                        <span class="day">22</span>
                        <span class="month">ENE</span>
                    </div>
                    <div class="event-info">
                        <h3>Masterclass Premium: Negociación Estratégica</h3>
                        <p>Técnicas avanzadas de negociación para líderes empresariales y emprendedores.</p>
                        <span class="instructor"><?php echo $preview_plan === 'despertar' ? 'Solo miembros Evolucionar' : 'Disponible para tu plan'; ?></span>
                    </div>
                    <button class="event-btn <?php echo $preview_plan === 'despertar' ? 'evolucionar-btn' : ''; ?>">
                        <?php echo $preview_plan === 'despertar' ? 'Acceso Premium' : 'Registrarse'; ?>
                    </button>
                </div>

                <div class="event-item">
                    <div class="event-date">
                        <span class="day">30</span>
                        <span class="month">ENE</span>
                    </div>
                    <div class="event-info">
                        <h3>Sesión Q&A Mensual</h3>
                        <p>Sesión interactiva de preguntas y respuestas con el equipo de Gente Vigente.</p>
                        <span class="instructor">Horario: 8:00 PM (GMT-6)</span>
                    </div>
                    <button class="event-btn">Registrarse</button>
                </div>
            </div>
        </section>

        <!-- Comunidad Section -->
        <section id="comunidad-section" class="content-section">
            <h1 class="page-title">Comunidad GV</h1>
            
            <div class="community-list">
                <div class="community-item">
                    <div class="community-icon">💬</div>
                    <div class="community-info">
                        <h3>Foro de Discusión</h3>
                        <p>Conecta con otros miembros, comparte experiencias y aprende en comunidad.</p>
                    </div>
                </div>

                <div class="community-item">
                    <div class="community-icon">📱</div>
                    <div class="community-info">
                        <h3>Grupo de WhatsApp</h3>
                        <p>Únete a nuestro grupo exclusivo para networking y apoyo diario.</p>
                    </div>
                </div>

                <div class="community-item">
                    <div class="community-icon">🎯</div>
                    <div class="community-info">
                        <h3>Círculos de Accountability</h3>
                        <p>Forma parte de grupos pequeños para seguimiento de objetivos y metas.</p>
                    </div>
                </div>

                <div class="community-item">
                    <div class="community-icon">🏅</div>
                    <div class="community-info">
                        <h3>Programa de Mentorías</h3>
                        <p>Accede a mentorías personalizadas con líderes experimentados.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Perfil Section -->
        <section id="perfil-section" class="content-section">
            <h1 class="page-title">Mi Perfil (Vista Demo)</h1>
            
            <div class="profile-edit-card">
                <div class="profile-header">
                    <div class="profile-avatar-large">
                        <span>UD</span>
                    </div>
                    <div class="profile-basic-info">
                        <h2>Usuario Demo</h2>
                        <p class="profile-email">usuario.demo@example.com</p>
                        <span class="membership-badge"><?php echo strtoupper($preview_plan); ?></span>
                    </div>
                </div>

                <div style="background: #f0f9ff; border: 1px solid #0369a1; border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #0369a1;">Vista de Demostración</h4>
                    <p style="margin: 0; font-size: 0.9rem; color: #0369a1;">
                        Esta es una simulación de cómo se ve el perfil para un usuario con plan <?php echo ucfirst($preview_plan); ?>.
                        Los datos mostrados son ficticios para propósitos de demostración.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <script src="js/dashboard.js"></script>
    
    <script>
        // Datos simulados para el usuario demo
        window.userData = {
            name: 'Usuario Demo',
            firstName: 'Usuario',
            lastName: 'Demo',
            email: 'usuario.demo@example.com',
            phone: '',
            country: '',
            subscription: '<?php echo $preview_plan; ?>',
            memberSince: '<?php echo date('F Y'); ?>'
        };
        
        // Función para cambiar de plan
        function switchPlan(plan) {
            window.location.href = 'dashboard-user-preview.php?plan=' + plan;
        }
        
        // Deshabilitar funciones que no deben funcionar en modo demo
        function logout() {
            alert('Esta es una vista demo. Usa el botón "Volver Admin" para regresar.');
        }
        
        // Override de funciones del dashboard original que no deben funcionar en demo
        const originalFunctions = {};
        
        // Deshabilitar envío de formularios
        document.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Esta es una vista demo. Los formularios no funcionan aquí.');
        });
        
        console.log('Admin User Preview cargado con plan:', '<?php echo $preview_plan; ?>');
    </script>
</body>
</html>