// dashboard.js - JavaScript actualizado para trabajar con sesiones PHP

// Variables globales
let currentSection = 'dashboard';
let isInitialized = false;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    if (isInitialized) return;
    
    // Ya no necesitamos checkAuth() porque dashboard.php maneja la autenticación
    loadUserData();
    initNavigation();
    initMobileMenu();
    isInitialized = true;
    console.log('Dashboard Gente Vigente cargado');
});

// Cargar datos del usuario (desde variables PHP globales)
function loadUserData() {
    try {
        // Los datos vienen de PHP a través de window.userData
        if (window.userData) {
            const userName = window.userData.name || 'Usuario';
            const userEmail = window.userData.email || 'usuario@email.com';
            const userMembership = window.userData.subscription || 'bronce';
            
            // Generar iniciales de forma segura
            const nameParts = userName.trim().split(' ');
            const initials = nameParts.map(name => name.charAt(0).toUpperCase()).join('').substring(0, 2) || 'U';
            
            // Actualizar elementos del DOM que no fueron llenados por PHP
            const elements = {
                'userInitials': initials
            };
            
            Object.keys(elements).forEach(id => {
                const element = document.getElementById(id);
                if (element && !element.textContent.trim()) {
                    element.textContent = elements[id];
                }
            });
            
            // Manejar contenido exclusivo
            handleExclusiveContent(userMembership);
            
        } else {
            console.error('No se encontraron datos de usuario desde PHP');
        }
        
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
                const membership = window.userData ? window.userData.subscription : 'bronce';
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
                const membership = window.userData ? window.userData.subscription : 'bronce';
                if (membership !== 'gold') {
                    showMessage('Evento exclusivo para miembros Gold');
                    return;
                }
            }
            
            showMessage(`Registrado en: ${title}`);
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
        background: var(--primary-color, #c78b42);
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
        background: var(--primary-color, #c78b42);
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

// Función de logout - Ahora usa el logout PHP
function logout() {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
        try {
            // Mostrar mensaje
            showMessage('Cerrando sesión...');
            
            // Redirigir al logout PHP
            setTimeout(() => {
                window.location.href = 'backend/logout.php';
            }, 500);
            
        } catch (error) {
            console.error('Error en logout:', error);
            // Forzar redirección en caso de error
            window.location.href = 'backend/logout.php';
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

// Verificación periódica de sesión (opcional)
function checkSessionStatus() {
    // Hacer una petición AJAX para verificar si la sesión sigue activa
    fetch('backend/session-check.php')
        .then(response => response.json())
        .then(data => {
            if (!data.active) {
                showMessage('Sesión expirada. Redirigiendo...');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error verificando sesión:', error);
        });
}

// Verificar sesión cada 10 minutos (opcional)
setInterval(checkSessionStatus, 600000);

console.log('Dashboard actualizado para sesiones PHP');