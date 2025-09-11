<?php 
//dashboard-user.php - Dashboard para usuarios regulares (renombrando el dashboard.php existente)
session_start();

// Verificar que es usuario regular (no admin)
if (!isset($_SESSION['user_id']) || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require 'backend/db.php';

// Debug para desarrollo
$isDebug = ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '.local') !== false);

try {
    // Verificar que el usuario aún existe y está activo
    $stmt = $pdo->prepare("
        SELECT id, email, first_name, last_name, subscription_type, subscription_status, 
               phone, country, created_at, user_role
        FROM users 
        WHERE id = ? AND user_role = 'user'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['subscription_status'] !== 'active') {
        if ($isDebug) {
            echo "<div style='background: red; color: white; padding: 10px; margin: 10px;'>❌ Usuario no válido o inactivo. Destruyendo sesión...</div>";
            echo "<script>setTimeout(() => window.location.href = 'login.php', 2000);</script>";
        }
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    // Actualizar variables de sesión con datos frescos
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_first_name'] = $user['first_name'] ?: '';
    $_SESSION['user_last_name'] = $user['last_name'] ?: '';
    $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']) ?: $user['email'];
    $_SESSION['subscription_type'] = $user['subscription_type'];
    $_SESSION['user_phone'] = $user['phone'] ?: '';
    $_SESSION['user_country'] = $user['country'] ?: '';
    $_SESSION['member_since'] = $user['created_at'];
    
    if ($isDebug) {
        echo "<div style='background: green; color: white; padding: 10px; margin: 10px;'>";
        echo "✅ Sesión válida de usuario<br>";
        echo "Usuario: " . htmlspecialchars($_SESSION['user_name']) . "<br>";
        echo "Email: " . htmlspecialchars($_SESSION['user_email']) . "<br>";
        echo "Plan: " . htmlspecialchars($_SESSION['subscription_type']) . "<br>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    error_log("Error verificando sesión de usuario: " . $e->getMessage());
    if ($isDebug) {
        echo "<div style='background: red; color: white; padding: 10px; margin: 10px;'>❌ Error de BD: " . $e->getMessage() . "</div>";
        echo "<script>setTimeout(() => window.location.href = 'login.php', 3000);</script>";
    } else {
        session_destroy();
        header('Location: login.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gente Vigente</title>
    <link rel="icon" type="image/png" href="img/LogoGVNB.png">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=visibility,visibility_off" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <img src="img/LogoGVNB.png" alt="Gente Vigente" class="logo">
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
            <a href="backend/logout.php" class="menu-item logout">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/></svg></span>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </nav>

    <!-- Top Bar -->
    <header class="topbar">
        <div class="user-info">
            <div class="user-avatar">
                <span id="userInitials">
                    <?php 
                    $fullName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Usuario';
                    if ($fullName && $fullName !== 'Usuario') {
                        $nameParts = explode(' ', trim($fullName));
                        echo strtoupper(substr($nameParts[0], 0, 1));
                        if (count($nameParts) > 1) {
                            echo strtoupper(substr($nameParts[count($nameParts)-1], 0, 1));
                        }
                    } else {
                        echo 'U';
                    }
                    ?>
                </span>
            </div>
            <div class="user-details">
                <span class="user-name" id="userName"><?php echo htmlspecialchars(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Usuario'); ?></span>
                <span class="user-email" id="userEmail"><?php echo htmlspecialchars(isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'email@ejemplo.com'); ?></span>
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
                        <?php if (isset($_SESSION['user_first_name']) && !empty($_SESSION['user_first_name'])): ?>
                            Hola <?php echo htmlspecialchars($_SESSION['user_first_name']); ?>! Tu plan <?php echo ucfirst(isset($_SESSION['subscription_type']) ? $_SESSION['subscription_type'] : 'despertar'); ?> está activo.
                        <?php else: ?>
                            Bienvenido a tu panel de control.
                        <?php endif; ?>
                        Continúa tu camino hacia el liderazgo personal y la excelencia. 
                        Explora el contenido exclusivo disponible para tu membresía.
                    </p>
                </div>
            </div>
        </section>

        <!-- Contenidos Section -->
        <section id="contenidos-section" class="content-section">
            <h1 class="page-title">Contenidos Exclusivos</h1>

            <!-- Loading state -->
            <div id="contentLoading" class="loading-state">
                <div class="loading-spinner"></div>
                <p>Cargando contenido...</p>
            </div>
            
            <!-- Error state -->
            <div id="contentError" class="error-state" style="display: none;">
                <p>Error al cargar el contenido. <button onclick="loadUserContent()">Reintentar</button></p>
            </div>
                    
            <div id="contentList" class="content-list" style="display: none;">
            <!-- Loads JavaScript -->
            </div>

            <!-- Empty state -->
            <div id="contentEmpty" class="empty-state" style="display: none;">
                <div class="empty-icon">📚</div>
                <h3>No hay contenido disponible</h3>
                <p>Aún no se ha subido contenido para tu plan de membresía.</p>
            </div>`
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

                <div class="event-item evolucionar-event">
                    <div class="event-date">
                        <span class="day">22</span>
                        <span class="month">ENE</span>
                    </div>
                    <div class="event-info">
                        <h3>Masterclass Premium: Negociación Estratégica</h3>
                        <p>Técnicas avanzadas de negociación para líderes empresariales y emprendedores.</p>
                        <span class="instructor">Solo miembros Evolucionar</span>
                    </div>
                    <button class="event-btn evolucionar-btn">Acceso Premium</button>
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
            <h1 class="page-title">Mi Perfil</h1>
            
            <!-- Card Principal de Perfil -->
            <div class="profile-edit-card">
                <div class="profile-header">
                    <div class="profile-avatar-large">
                        <span id="profileAvatarLarge">U</span>
                    </div>
                    <div class="profile-basic-info">
                        <h2 id="profileDisplayName">Usuario</h2>
                        <p class="profile-email" id="profileDisplayEmail">email@ejemplo.com</p>
                        <span class="membership-badge" id="profileMembershipBadge">DESPERTAR</span>
                    </div>
                </div>

                <!-- Formulario de Edición -->
                <form id="profileEditForm" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">Nombre:</label>
                            <input type="text" id="firstName" name="firstName" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Apellido:</label>
                            <input type="text" id="lastName" name="lastName" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Teléfono:</label>
                            <input type="tel" id="phone" name="phone" placeholder="Opcional">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="country">País:</label>
                            <select id="country" name="country">
                                <option value="">Seleccionar país</option>
                                <option value="CR">Costa Rica</option>
                                <option value="MX">México</option>
                                <option value="US">Estados Unidos</option>
                                <option value="ES">España</option>
                                <option value="AR">Argentina</option>
                                <option value="CO">Colombia</option>
                                <option value="PE">Perú</option>
                                <option value="CL">Chile</option>
                                <option value="OTHER">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="birthDate">Fecha de Nacimiento:</label>
                            <input type="date" id="birthDate" name="birthDate">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="resetForm()">Cancelar</button>
                        <button type="submit" class="btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>

            <!-- Card de Seguridad -->
            <div class="security-card">
                <h3>Seguridad de la Cuenta</h3>
                <div class="security-item">
                    <div class="security-info">
                        <h4>Cambiar Contraseña</h4>
                        <p>Mantén tu cuenta segura actualizando tu contraseña regularmente</p>
                    </div>
                    <button class="btn-outline" onclick="openPasswordModal()">Cambiar Contraseña</button>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal para Cambiar Contraseña -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cambiar Contraseña</h3>
                <button class="modal-close" onclick="closePasswordModal()">&times;</button>
            </div>
            <form id="passwordForm" class="modal-body">
                <div class="form-group">
                    <label for="currentPassword">Contraseña Actual:</label>
                    <div class="password-input-container">
                        <input type="password" id="currentPassword" name="currentPassword" required>
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('currentPassword')">
                            <span class="material-symbols-outlined">visibility_off</span>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="newPassword">Nueva Contraseña:</label>
                    <div class="password-input-container">
                        <input type="password" id="newPassword" name="newPassword" required minlength="8">
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('newPassword')">
                            <span class="material-symbols-outlined">visibility_off</span>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirmar Nueva Contraseña:</label>
                    <div class="password-input-container">
                        <input type="password" id="confirmPassword" name="confirmPassword" required minlength="8">
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirmPassword')">
                            <span class="material-symbols-outlined">visibility_off</span>
                        </button>
                    </div>
                </div>
                <div class="password-requirements">
                    <small>La contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas y números.</small>
                </div>
            </form>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closePasswordModal()">Cancelar</button>
                <button type="button" class="btn-primary" onclick="savePassword()">Cambiar Contraseña</button>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
    
    <!-- Script para manejar datos de PHP en JavaScript -->
    <script>
        // Pasar datos completos de PHP a JavaScript de forma segura
        window.userData = {
            name: '<?php echo addslashes(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : ''); ?>',
            firstName: '<?php echo addslashes(isset($_SESSION['user_first_name']) ? $_SESSION['user_first_name'] : ''); ?>',
            lastName: '<?php echo addslashes(isset($_SESSION['user_last_name']) ? $_SESSION['user_last_name'] : ''); ?>',
            email: '<?php echo addslashes(isset($_SESSION['user_email']) ? $_SESSION['user_email'] : ''); ?>',
            phone: '<?php echo addslashes(isset($_SESSION['user_phone']) ? $_SESSION['user_phone'] : ''); ?>',
            country: '<?php echo addslashes(isset($_SESSION['user_country']) ? $_SESSION['user_country'] : ''); ?>',
            subscription: '<?php echo addslashes(isset($_SESSION['subscription_type']) ? $_SESSION['subscription_type'] : 'despertar'); ?>',
            memberSince: '<?php echo isset($_SESSION['member_since']) ? date('F Y', strtotime($_SESSION['member_since'])) : date('F Y'); ?>'
        };
        
        // Debug para ver los datos
        console.log('Datos del usuario cargados:', window.userData);
        
        // Función de logout
        function logout() {
            if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                window.location.href = 'backend/logout.php';
            }
        }

        // Función para toggle de visibilidad de contraseñas
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.parentNode.querySelector('.password-toggle .material-symbols-outlined');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility_off';
            }
        }
    </script>
</body>
</html>