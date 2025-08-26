<?php include 'dashboard-session.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gente Vigente</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <img src="img/GenteVigente.png" alt="Gente Vigente" class="logo">
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
                <span id="userInitials"><?php echo isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : 'U'; ?></span>
            </div>
            <div class="user-details">
                <span class="user-name" id="userName"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuario'; ?></span>
                <span class="user-email" id="userEmail"><?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : 'email@ejemplo.com'; ?></span>
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
                    <img src="img/GenteVigente.png" alt="Gente Vigente" class="brand-image">
                </div>
                <div class="welcome-content">
                    <h2>Bienvenido al menu exclusivo de GV</h2>
                    <p>
                        <?php if (isset($_SESSION['user_name']) && isset($_SESSION['subscription_type'])): ?>
                            Hola <?php echo htmlspecialchars($_SESSION['user_name']); ?>! Tu plan <?php echo ucfirst($_SESSION['subscription_type']); ?> está activo.
                        <?php else: ?>
                            Bienvenido a tu panel de control.
                        <?php endif; ?>
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor 
                        incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud 
                        exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
                    </p>
                </div>
            </div>
        </section>

        <!-- Perfil Section -->
        <section id="perfil-section" class="content-section">
            <h1 class="page-title">Mi Perfil</h1>
            
            <div class="profile-info-card">
                <div class="profile-section">
                    <h3>Información Personal</h3>
                    <div class="info-item">
                        <label>Nombre:</label>
                        <span id="profileNameDisplay"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuario'; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email:</label>
                        <span id="profileEmailDisplay"><?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : 'email@ejemplo.com'; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Membresía:</label>
                        <span id="profileMembership" class="membership-badge <?php echo isset($_SESSION['subscription_type']) ? $_SESSION['subscription_type'] : 'despertar'; ?>">
                            <?php echo isset($_SESSION['subscription_type']) ? ucfirst($_SESSION['subscription_type']) : 'despertar'; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <label>Miembro desde:</label>
                        <span><?php echo date('F Y'); ?></span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="js/dashboard.js"></script>
    
    <!-- Script para manejar datos de PHP en JavaScript -->
    <script>
        // Pasar datos de PHP a JavaScript de forma segura
        window.userData = {
            name: '<?php echo isset($_SESSION['user_name']) ? addslashes($_SESSION['user_name']) : 'Usuario'; ?>',
            email: '<?php echo isset($_SESSION['user_email']) ? addslashes($_SESSION['user_email']) : 'email@ejemplo.com'; ?>',
            subscription: '<?php echo isset($_SESSION['subscription_type']) ? addslashes($_SESSION['subscription_type']) : 'despertar'; ?>'
        };
        
        // Función de logout
        function logout() {
            if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                window.location.href = 'backend/logout.php';
            }
        }
    </script>
</body>
</html>