// dashboard.js - JavaScript sencillo para el dashboard

// Variables globales
let currentSection = 'dashboard';
let isInitialized = false;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    if (isInitialized) return;
    
    if (checkAuth()) {
        loadUserData();
        initNavigation();
        initMobileMenu();
        isInitialized = true;
        console.log('✅ Dashboard Gente Vigente cargado');
    }
});

// Verificar autenticación
function checkAuth() {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const userName = localStorage.getItem('userName');
    
    if (isLoggedIn !== 'true' || !userName) {
        // Solo redirigir si no estamos ya en la página de login
        if (!window.location.href.includes('backend/login.html')) {
            window.location.href = '../index.html';
        }
        return false;
    }
    return true;
}

// Cargar datos del usuario
function loadUserData() {
    try {
        const userName = localStorage.getItem('userName') || 'Usuario';
        const userEmail = localStorage.getItem('userEmail') || 'usuario@email.com';
        const userMembership = localStorage.getItem('userMembership') || 'bronce';
        
        // Generar iniciales de forma segura
        const nameParts = userName.trim().split(' ');
        const initials = nameParts.map(name => name.charAt(0).toUpperCase()).join('').substring(0, 2) || 'U';
        
        // Actualizar elementos del DOM de forma segura
        const elements = {
            'userName': userName,
            'userEmail': userEmail,
            'userInitials': initials,
            'profileName': userName,
            'profileNameDisplay': userName,
            'profileEmailDisplay': userEmail
        };
        
        Object.keys(elements).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = elements[id];
            }
        });
        
        // Configurar membresía
        const membershipBadge = document.getElementById('profileMembership');
        if (membershipBadge) {
            if (userMembership === 'gold') {
                membershipBadge.textContent = 'Gold';
                membershipBadge.classList.add('gold');
            } else {
                membershipBadge.textContent = 'Bronce';
            }
        }
        
        // Manejar contenido exclusivo
        handleExclusiveContent(userMembership);
        
    } catch (error) {
        console.error('Error cargando datos del usuario:', error);
        showMessage('Error cargando perfil de usuario');
    }
}

// Manejar contenido exclusivo
function handleExclusiveContent(membership) {
    const goldItems = document.querySelectorAll('.gold-only, .gold-event');
    
    goldItems.forEach(item => {
        if (membership !== 'gold') {
            item.style.opacity = '0.7';
            item.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showMessage('Este contenido es exclusivo para miembros Gold');
            });
        }
    });
}

// Inicializar navegación
function initNavigation() {
    const menuItems = document.querySelectorAll('.menu-item:not(.logout)');
    
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            if (section) {
                navigateTo(section);
            }
        });
    });
    
    // Inicializar contenido clickeable
    initContentEvents();
}

// Navegar a sección
function navigateTo(sectionName) {
    // Ocultar todas las secciones
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Mostrar sección actual
    const targetSection = document.getElementById(`${sectionName}-section`);
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Actualizar menú activo
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
    });
    
    const activeItem = document.querySelector(`[data-section="${sectionName}"]`);
    if (activeItem) {
        activeItem.classList.add('active');
    }
    
    currentSection = sectionName;
}

// Inicializar eventos de contenido
function initContentEvents() {
    // Contenidos
    document.querySelectorAll('#contenidos-section .content-item').forEach(item => {
        item.addEventListener('click', function() {
            const title = this.querySelector('h3').textContent;
            if (this.classList.contains('gold-only')) {
                const membership = localStorage.getItem('userMembership');
                if (membership !== 'gold') {
                    showMessage('Contenido exclusivo para miembros Gold');
                    return;
                }
            }
            showMessage(`Abriendo: ${title}`);
        });
    });
    
    // Eventos
    document.querySelectorAll('.event-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const eventItem = this.closest('.event-item');
            const eventTitle = eventItem.querySelector('h3').textContent;
            
            if (this.classList.contains('gold-btn')) {
                const membership = localStorage.getItem('userMembership');
                if (membership !== 'gold') {
                    showMessage('Evento exclusivo para miembros Gold');
                    return;
                }
            }
            
            showMessage(`Registrado en: ${eventTitle}`);
        });
    });
    
    // Comunidad
    document.querySelectorAll('#comunidad-section .community-item').forEach(item => {
        item.addEventListener('click', function() {
            const title = this.querySelector('h3').textContent;
            showMessage(`Accediendo a: ${title}`);
        });
    });
}

// Menú móvil
function initMobileMenu() {
    // Crear botón de menú móvil
    if (window.innerWidth <= 768) {
        createMobileToggle();
    }
    
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768 && !document.querySelector('.mobile-toggle')) {
            createMobileToggle();
        } else if (window.innerWidth > 768) {
            const toggle = document.querySelector('.mobile-toggle');
            if (toggle) {
                toggle.remove();
            }
            document.querySelector('.sidebar').classList.remove('mobile-open');
        }
    });
}

// Crear botón de menú móvil
function createMobileToggle() {
    const toggle = document.createElement('button');
    toggle.className = 'mobile-toggle';
    toggle.innerHTML = '☰';
    toggle.style.cssText = `
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1100;
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 0.8rem;
        border-radius: 8px;
        font-size: 1.2rem;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    `;
    
    toggle.addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('mobile-open');
    });
    
    document.body.appendChild(toggle);
    
    // Cerrar al hacer click fuera
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.querySelector('.mobile-toggle');
        
        if (sidebar.classList.contains('mobile-open') && 
            !sidebar.contains(e.target) && 
            !toggle.contains(e.target)) {
            sidebar.classList.remove('mobile-open');
        }
    });
}

// Mostrar mensajes
function showMessage(message) {
    // Crear notificación simple
    const notification = document.createElement('div');
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--primary-color);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 300px;
    `;
    
    document.body.appendChild(notification);
    
    // Mostrar
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Ocultar automáticamente
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 3000);
}

// Función de logout
function logout() {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
        try {
            // Limpiar datos
            localStorage.removeItem('isLoggedIn');
            localStorage.removeItem('userEmail');
            localStorage.removeItem('userName');
            localStorage.removeItem('userMembership');
            localStorage.removeItem('lastAuthCheck');
            
            // Mostrar mensaje
            showMessage('Cerrando sesión...');
            
            // Redirigir después de un pequeño delay
            setTimeout(() => {
                window.location.href = '../index.html';
            }, 500);
            
        } catch (error) {
            console.error('Error en logout:', error);
            // Forzar redirección en caso de error
            window.location.href = '../index.html';
        }
    }
}

// Manejo de errores
window.addEventListener('error', function(e) {
    console.error('Error:', e.error);
    showMessage('Ha ocurrido un error inesperado');
});

// Navegación por teclado (accesibilidad)
document.addEventListener('keydown', function(e) {
    if (e.altKey) {
        switch(e.key) {
            case '1':
                e.preventDefault();
                navigateTo('dashboard');
                break;
            case '2':
                e.preventDefault();
                navigateTo('contenidos');
                break;
            case '3':
                e.preventDefault();
                navigateTo('eventos');
                break;
            case '4':
                e.preventDefault();
                navigateTo('comunidad');
                break;
            case '5':
                e.preventDefault();
                navigateTo('perfil');
                break;
        }
    }
});

// Optimización para dispositivos táctiles
if ('ontouchstart' in window) {
    document.body.classList.add('touch-device');
}

// Log final
console.log('🌟 Dashboard sencillo iniciado correctamente');