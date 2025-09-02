<?php include 'dashboard-session.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gente Vigente</title>
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
            
            <a href="#" class="menu-item" data-section="perfil">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z"/></svg></span>
                <span>Mi Perfil</span>
            </a>
            <a href="backend/logout.php" class="menu-item logout">
                <span class="menu-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/></svg></span>
                <span>Salir</span>
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

        <!-- Perfil Section - AQUÍ VA EL HTML DEL PERFIL -->
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