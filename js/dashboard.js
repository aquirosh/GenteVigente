// dashboard.js - JavaScript completo con funcionalidad de perfil integrada

// Variables globales
let currentSection = 'dashboard';
let isInitialized = false;
let currentUserData = {}; // Para datos del perfil

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    if (isInitialized) return;
    
    // Ya no necesitamos checkAuth() porque dashboard.php maneja la autenticación
    loadUserData();
    initNavigation();
    initMobileMenu();
    initializeProfile();
    setupFormValidation();
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
            const userMembership = window.userData.subscription || 'despertar';
            
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
    const evolucionarItems = document.querySelectorAll('.evolucionar-only, .evolucionar-event');
    
    evolucionarItems.forEach(item => {
        if (membership !== 'evolucionar') {
            item.style.opacity = '0.7';
            item.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showMessage('Este contenido es exclusivo para miembros con el plan Evolucionar');
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
            if (this.classList.contains('evolucionar-only')) {
                const membership = window.userData ? window.userData.subscription : 'despertar';
                if (membership !== 'evolucionar') {
                    showMessage('Contenido exclusivo para miembros plan Evolucionar');
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
            
            if (this.classList.contains('evolucionar-btn')) {
                const membership = window.userData ? window.userData.subscription : 'despertar';
                if (membership !== 'evolucionar') {
                    showMessage('Evento exclusivo para miembros evolucionar');
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

// =======================================
// FUNCIONES DEL PERFIL
// =======================================

function initializeProfile() {
    // Cargar datos desde window.userData (viene de PHP)
    if (window.userData) {
        currentUserData = {
            firstName: window.userData.firstName || '',
            lastName: window.userData.lastName || '',
            email: window.userData.email || '',
            phone: window.userData.phone || '',
            country: window.userData.country || '',
            birthDate: window.userData.birthDate || '',
            subscription: window.userData.subscription || 'despertar'
        };
    } else {
        // Datos por defecto si no hay window.userData
        currentUserData = {
            firstName: '',
            lastName: '',
            email: '',
            phone: '',
            country: '',
            birthDate: '',
            subscription: 'despertar'
        };
    }
    
    // Actualizar la interfaz si estamos en la sección de perfil
    setTimeout(() => {
        updateProfileDisplay();
        populateForm();
    }, 100);
}

function updateProfileDisplay() {
    const fullName = `${currentUserData.firstName} ${currentUserData.lastName}`.trim();
    const initials = getInitials(currentUserData.firstName, currentUserData.lastName);
    
    // Actualizar elementos de visualización del perfil
    const profileDisplayName = document.getElementById('profileDisplayName');
    const profileDisplayEmail = document.getElementById('profileDisplayEmail');
    const profileAvatarLarge = document.getElementById('profileAvatarLarge');
    const profileMembershipBadge = document.getElementById('profileMembershipBadge');
    
    if (profileDisplayName) profileDisplayName.textContent = fullName || 'Usuario';
    if (profileDisplayEmail) profileDisplayEmail.textContent = currentUserData.email || 'email@ejemplo.com';
    if (profileAvatarLarge) profileAvatarLarge.textContent = initials;
    
    // Actualizar badge de membresía con colores correctos según la nueva paleta
    if (profileMembershipBadge) {
        const subscription = currentUserData.subscription.toLowerCase();
        profileMembershipBadge.textContent = subscription.toUpperCase();
        profileMembershipBadge.className = `membership-badge ${subscription}`;
    }
    
    // Actualizar también el topbar
    const userName = document.getElementById('userName');
    const userEmail = document.getElementById('userEmail');
    const userInitials = document.getElementById('userInitials');
    
    if (userName) userName.textContent = fullName || 'Usuario';
    if (userEmail) userEmail.textContent = currentUserData.email;
    if (userInitials) userInitials.textContent = initials;
}

function getInitials(firstName, lastName) {
    const first = (firstName || '').charAt(0).toUpperCase();
    const last = (lastName || '').charAt(0).toUpperCase();
    return (first + last) || 'U';
}

function populateForm() {
    // Llenar el formulario con datos actuales si existen los elementos
    const firstNameInput = document.getElementById('firstName');
    const lastNameInput = document.getElementById('lastName');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const countryInput = document.getElementById('country');
    const birthDateInput = document.getElementById('birthDate');
    
    if (firstNameInput) firstNameInput.value = currentUserData.firstName || '';
    if (lastNameInput) lastNameInput.value = currentUserData.lastName || '';
    if (emailInput) emailInput.value = currentUserData.email || '';
    if (phoneInput) phoneInput.value = currentUserData.phone || '';
    if (countryInput) countryInput.value = currentUserData.country || '';
    if (birthDateInput) birthDateInput.value = currentUserData.birthDate || '';
}

function resetForm() {
    populateForm(); // Restaurar valores originales
    showNotification('Cambios cancelados', 'info');
}

// Configurar validación del formulario de perfil
function setupFormValidation() {
    const profileForm = document.getElementById('profileEditForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Obtener valores del formulario
            const formData = {
                firstName: document.getElementById('firstName').value.trim(),
                lastName: document.getElementById('lastName').value.trim(),
                email: document.getElementById('email').value.trim(),
                phone: document.getElementById('phone').value.trim(),
                country: document.getElementById('country').value,
                birthDate: document.getElementById('birthDate').value
            };
            
            // Validar campos requeridos
            if (!formData.firstName || !formData.lastName || !formData.email) {
                showNotification('Por favor completa todos los campos requeridos', 'error');
                return;
            }
            
            // Validar email
            if (!isValidEmail(formData.email)) {
                showNotification('Por favor ingresa un email válido', 'error');
                return;
            }
            
            // Guardar perfil con llamada AJAX real
            saveProfile(formData);
        });
    }
    
    // Configurar validación de contraseñas
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    
    if (newPasswordInput && confirmPasswordInput) {
        function validatePasswords() {
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (newPassword && confirmPassword) {
                if (newPassword === confirmPassword) {
                    confirmPasswordInput.style.borderColor = '#22c55e';
                } else {
                    confirmPasswordInput.style.borderColor = '#ef4444';
                }
            } else {
                confirmPasswordInput.style.borderColor = 'var(--border-color)';
            }
        }
        
        newPasswordInput.addEventListener('input', validatePasswords);
        confirmPasswordInput.addEventListener('input', validatePasswords);
    }
}

function saveProfile(formData) {
    // Mostrar loading
    const submitBtn = document.querySelector('#profileEditForm .btn-primary');
    if (submitBtn) {
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Guardando...';
        submitBtn.disabled = true;
        
        // Llamada AJAX al servidor
        fetch('backend/update-profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            // Restaurar botón
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            
            if (data.success) {
                // Actualizar datos locales con la respuesta del servidor
                currentUserData = { ...currentUserData, ...data.data };
                
                // Actualizar interfaz
                updateProfileDisplay();
                
                // Mostrar éxito
                showNotification('Perfil actualizado correctamente', 'success');
            } else {
                // Mostrar error del servidor
                showNotification(data.message || 'Error al actualizar perfil', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Restaurar botón
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            
            showNotification('Error de conexión. Inténtalo de nuevo.', 'error');
        });
    }
}

// Modal de contraseña
function openPasswordModal() {
    const modal = document.getElementById('passwordModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

function closePasswordModal() {
    const modal = document.getElementById('passwordModal');
    if (modal) {
        modal.style.display = 'none';
        const form = document.getElementById('passwordForm');
        if (form) form.reset();
    }
}

function savePassword() {
    const current = document.getElementById('currentPassword').value;
    const newPass = document.getElementById('newPassword').value;
    const confirm = document.getElementById('confirmPassword').value;
    
    if (!current || !newPass || !confirm) {
        showNotification('Por favor completa todos los campos', 'error');
        return;
    }
    
    if (newPass !== confirm) {
        showNotification('Las contraseñas no coinciden', 'error');
        return;
    }
    
    if (newPass.length < 8) {
        showNotification('La contraseña debe tener al menos 8 caracteres', 'error');
        return;
    }
    
    // Mostrar loading en el botón
    const saveBtn = document.querySelector('#passwordModal .btn-primary');
    if (saveBtn) {
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Cambiando...';
        saveBtn.disabled = true;
        
        // Llamada AJAX real para cambiar contraseña
        fetch('backend/change-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                currentPassword: current,
                newPassword: newPass,
                confirmPassword: confirm
            })
        })
        .then(response => response.json())
        .then(data => {
            // Restaurar botón
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
            
            if (data.success) {
                closePasswordModal();
                showNotification('Contraseña actualizada correctamente', 'success');
            } else {
                showNotification(data.message || 'Error al cambiar contraseña', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Restaurar botón
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
            
            showNotification('Error de conexión. Inténtalo de nuevo.', 'error');
        });
    }
}

// =======================================
// FUNCIONES UTILITARIAS
// =======================================

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Estilos
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        z-index: 10001;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 300px;
        font-weight: 500;
    `;
    
    // Colores
    switch(type) {
        case 'success':
            notification.style.background = '#22c55e';
            notification.style.color = 'white';
            break;
        case 'error':
            notification.style.background = '#ef4444';
            notification.style.color = 'white';
            break;
        default:
            notification.style.background = 'var(--primary-color)';
            notification.style.color = 'white';
    }
    
    document.body.appendChild(notification);
    
    // Animaciones
    setTimeout(() => notification.style.transform = 'translateX(0)', 100);
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
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

// Mostrar mensajes (método original para compatibilidad)
function showMessage(message) {
    showNotification(message, 'info');
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

// Cerrar modales al hacer clic fuera
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

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

console.log('Dashboard completo con funcionalidad de perfil cargado');

// Hacer funciones globales disponibles para llamadas desde HTML
window.navigateTo = navigateTo;
window.openPasswordModal = openPasswordModal;
window.closePasswordModal = closePasswordModal;
window.savePassword = savePassword;
window.resetForm = resetForm;
window.logout = logout;
window.togglePasswordVisibility = togglePasswordVisibility;

// Función para toggle de visibilidad de contraseñas
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const button = input.parentNode.querySelector('.password-toggle');
    const icon = button.querySelector('.material-symbols-outlined');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility_off';
    }
}