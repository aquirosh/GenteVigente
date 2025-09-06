// admin-dashboard.js - JavaScript para el panel de administración

// Variables globales
let currentSection = 'overview';
let isInitialized = false;
let selectedFiles = [];
let currentEditingUser = null;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    if (isInitialized) return;
    
    initNavigation();
    initFileUpload();
    initMobileMenu();
    loadUsersTable();
    loadContentGrid();
    setupFormHandlers();
    isInitialized = true;
    console.log('Admin Dashboard cargado');
});

// Navegación
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
}

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
    
    // Cargar datos específicos de la sección
    switch(sectionName) {
        case 'users':
            loadUsersTable();
            break;
        case 'content':
            loadContentGrid();
            break;
        case 'analytics':
            loadAnalytics();
            break;
    }
}

// Sistema de subida de archivos
function initFileUpload() {
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    const uploadForm = document.getElementById('uploadForm');
    
    if (!uploadZone || !fileInput) return;
    
    // Drag and drop
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadZone.classList.add('dragover');
    });
    
    uploadZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
    });
    
    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        handleFileSelection(files);
    });
    
    // Click to select
    fileInput.addEventListener('change', function(e) {
        handleFileSelection(e.target.files);
    });
}

function handleFileSelection(files) {
    if (files.length === 0) return;
    
    selectedFiles = Array.from(files);
    const uploadForm = document.getElementById('uploadForm');
    const uploadZone = document.getElementById('uploadZone');
    
    // Validar tipos de archivo
    const allowedExtensions = ['mp4', 'mov', 'avi', 'webm', 'mkv', 'mp3', 'wav', 'm4a', 'aac', 'pdf', 'doc', 'docx'];
    const invalidFiles = selectedFiles.filter(file => {
        const extension = file.name.split('.').pop().toLowerCase();
        return !allowedExtensions.includes(extension);
    });
    
    if (invalidFiles.length > 0) {
        showNotification(`Archivos no permitidos: ${invalidFiles.map(f => f.name).join(', ')}`, 'error');
        return;
    }
    
    // Validar tamaño (500MB por archivo)
    const maxSize = 500 * 1024 * 1024;
    const oversizedFiles = selectedFiles.filter(file => file.size > maxSize);
    
    if (oversizedFiles.length > 0) {
        showNotification(`Archivos demasiado grandes (max 500MB): ${oversizedFiles.map(f => f.name).join(', ')}`, 'error');
        return;
    }
    
    // Mostrar formulario y ocultar zona de arrastre
    uploadZone.style.display = 'none';
    uploadForm.style.display = 'block';
    
    // Auto-detectar categoría basada en el tipo de archivo
    if (selectedFiles.length === 1) {
        const file = selectedFiles[0];
        const extension = file.name.split('.').pop().toLowerCase();
        const fileName = file.name.replace(/\.[^/.]+$/, "");
        
        document.getElementById('fileTitle').value = fileName;
        
        // Auto-seleccionar categoría
        const categorySelect = document.getElementById('fileCategory');
        if (['mp4', 'mov', 'avi', 'webm', 'mkv'].includes(extension)) {
            if (fileName.toLowerCase().includes('zoom') || fileName.toLowerCase().includes('teams')) {
                categorySelect.value = 'grabacion_zoom';
            } else if (fileName.toLowerCase().includes('webinar')) {
                categorySelect.value = 'webinar';
            } else {
                categorySelect.value = 'masterclass';
            }
        } else if (['mp3', 'wav', 'm4a', 'aac'].includes(extension)) {
            categorySelect.value = 'podcast';
        } else if (['pdf', 'doc', 'docx'].includes(extension)) {
            if (fileName.toLowerCase().includes('guia') || fileName.toLowerCase().includes('manual')) {
                categorySelect.value = 'guia';
            } else {
                categorySelect.value = 'documento';
            }
        }
    }
    
    // Mostrar información de los archivos
    const fileInfo = selectedFiles.map(file => {
        const size = (file.size / (1024 * 1024)).toFixed(2);
        return `${file.name} (${size} MB)`;
    }).join('\n');
    
    showNotification(`${selectedFiles.length} archivo(s) seleccionado(s):\n${fileInfo}`, 'success');
}

function cancelUpload() {
    selectedFiles = [];
    const uploadForm = document.getElementById('uploadForm');
    const uploadZone = document.getElementById('uploadZone');
    
    uploadForm.style.display = 'none';
    uploadZone.style.display = 'block';
    
    // Resetear formulario
    uploadForm.querySelector('form').reset();
}

function submitUpload() {
    if (selectedFiles.length === 0) {
        showNotification('No hay archivos seleccionados', 'error');
        return;
    }
    
    const title = document.getElementById('fileTitle').value;
    const description = document.getElementById('fileDescription').value;
    const category = document.getElementById('fileCategory').value;
    const access = document.getElementById('fileAccess').value;
    
    if (!title || !category || !access) {
        showNotification('Por favor completa todos los campos requeridos', 'error');
        return;
    }
    
    // Mostrar progreso
    const uploadProgress = document.getElementById('uploadProgress');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    uploadProgress.style.display = 'block';
    
    // Simular subida de archivo
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 100) progress = 100;
        
        progressFill.style.width = progress + '%';
        progressText.textContent = `Subiendo... ${Math.round(progress)}%`;
        
        if (progress >= 100) {
            clearInterval(interval);
            setTimeout(() => {
                // Aquí iría la llamada AJAX real
                uploadFileToServer({
                    files: selectedFiles,
                    title: title,
                    description: description,
                    category: category,
                    access: access
                });
            }, 500);
        }
    }, 200);
}

function uploadFileToServer(uploadData) {
    const formData = new FormData();
    
    // Agregar archivos
    uploadData.files.forEach((file, index) => {
        formData.append(`files[${index}]`, file);
    });
    
    // Agregar metadatos
    formData.append('title', uploadData.title);
    formData.append('description', uploadData.description);
    formData.append('category', uploadData.category);
    formData.append('access', uploadData.access);
    
    fetch('backend/admin/upload-file.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const uploadProgress = document.getElementById('uploadProgress');
        const progressText = document.getElementById('progressText');
        
        if (data.success) {
            progressText.textContent = 'Subida completada';
            showNotification('Archivo(s) subido(s) correctamente', 'success');
            
            setTimeout(() => {
                cancelUpload();
                uploadProgress.style.display = 'none';
            }, 2000);
        } else {
            progressText.textContent = 'Error en la subida';
            showNotification(data.message || 'Error al subir archivo', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    });
}

// Gestión de usuarios
function loadUsersTable() {
    const tbody = document.querySelector('#usersTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem;">Cargando usuarios...</td></tr>';
    
    fetch('backend/admin/get-users.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderUsersTable(data.users);
            } else {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: #ef4444;">Error cargando usuarios</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: #ef4444;">Error de conexión</td></tr>';
        });
}

function renderUsersTable(users) {
    const tbody = document.querySelector('#usersTable tbody');
    
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem;">No hay usuarios registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = users.map(user => `
        <tr>
            <td>${user.id}</td>
            <td>${user.first_name} ${user.last_name}</td>
            <td>${user.email}</td>
            <td><span class="role-badge ${user.user_role}">${user.user_role === 'admin' ? 'Admin' : 'Usuario'}</span></td>
            <td><span class="user-plan ${user.subscription_type}">${user.subscription_type}</span></td>
            <td><span class="status-badge ${user.subscription_status}">${user.subscription_status}</span></td>
            <td>${new Date(user.created_at).toLocaleDateString('es-ES')}</td>
            <td>
                <button onclick="deleteUser(${user.id})" class="btn-secondary" style="padding: 0.4rem 0.8rem; background: #ef4444; color: white; border-color: #ef4444;">Eliminar</button>
            </td>
        </tr>
    `).join('');
}

function editUser(userId) {
    fetch(`backend/admin/get-user.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentEditingUser = data.user;
                populateUserModal(data.user);
                openUserModal();
            } else {
                showNotification('Error cargando datos del usuario', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error de conexión', 'error');
        });
}

function deleteUser(userId) {
    if (!confirm('¿Estás seguro de que quieres eliminar este usuario?')) {
        return;
    }
    
    fetch('backend/admin/delete-user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ userId: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Usuario eliminado correctamente', 'success');
            loadUsersTable();
        } else {
            showNotification(data.message || 'Error al eliminar usuario', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    });
}

// Modal de usuario
function openUserModal() {
    const modal = document.getElementById('userModal');
    const title = document.getElementById('userModalTitle');
    
    title.textContent = 'Agregar Usuario';
    clearUserModal();
    modal.style.display = 'block';
}

function closeUserModal() {
    const modal = document.getElementById('userModal');
    modal.style.display = 'none';
    currentEditingUser = null;
    clearUserModal();
}

function populateUserModal(user) {
    document.getElementById('userFirstName').value = user.first_name || '';
    document.getElementById('userLastName').value = user.last_name || '';
    document.getElementById('userEmail').value = user.email || '';
    document.getElementById('userPlan').value = user.subscription_type || 'despertar';
    document.getElementById('userStatus').value = user.subscription_status || 'active';
}

function clearUserModal() {
    document.getElementById('userFirstName').value = '';
    document.getElementById('userLastName').value = '';
    document.getElementById('userEmail').value = '';
    document.getElementById('userRole').value = 'user';
    document.getElementById('userPlan').value = 'despertar';
    document.getElementById('userStatus').value = 'active';
}

function saveUser() {
    const userData = {
        firstName: document.getElementById('userFirstName').value.trim(),
        lastName: document.getElementById('userLastName').value.trim(),
        email: document.getElementById('userEmail').value.trim(),
        role: document.getElementById('userRole').value,
        plan: document.getElementById('userPlan').value,
        status: document.getElementById('userStatus').value
    };
    
    if (!userData.firstName || !userData.lastName || !userData.email) {
        showNotification('Por favor completa todos los campos requeridos', 'error');
        return;
    }
    
    if (!isValidEmail(userData.email)) {
        showNotification('Por favor ingresa un email válido', 'error');
        return;
    }
    
    fetch('backend/admin/create-user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(
                `Usuario creado correctamente. Contraseña temporal: ${data.data.tempPassword}`, 
                'success'
            );
            closeUserModal();
            loadUsersTable();
            
            // Mostrar información de contraseña temporal en una ventana separada
            setTimeout(() => {
                alert(`INFORMACIÓN IMPORTANTE:\n\nUsuario: ${data.data.email}\nContraseña temporal: ${data.data.tempPassword}\n\nEl usuario debe cambiar la contraseña en su primer login.\n\nGuarda esta información de forma segura.`);
            }, 1000);
            
        } else {
            showNotification(data.message || 'Error al crear usuario', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    });
}

// Gestión de contenido
function loadContentGrid() {
    const contentGrid = document.getElementById('contentGrid');
    if (!contentGrid) return;
    
    contentGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 2rem;">Cargando contenido...</div>';
    
    fetch('backend/admin/get-content.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderContentGrid(data.content);
            } else {
                contentGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: #ef4444;">Error cargando contenido</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            contentGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: #ef4444;">Error de conexión</div>';
        });
}

function renderContentGrid(content) {
    const contentGrid = document.getElementById('contentGrid');
    
    if (content.length === 0) {
        contentGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 2rem;">No hay contenido disponible</div>';
        return;
    }
    
    contentGrid.innerHTML = content.map(item => `
        <div class="content-item">
            <h4>${item.title}</h4>
            <p>${item.description || 'Sin descripción'}</p>
            <div class="content-meta">
                <span class="status-badge ${item.status}">${item.status}</span>
                <span>${item.category || item.file_type}</span>
            </div>
            <div style="margin-top: 1rem;">
                <button onclick="editContent(${item.id})" class="btn-secondary" style="padding: 0.4rem 0.8rem; margin-right: 0.5rem;">Editar</button>
                <button onclick="deleteContent(${item.id})" class="btn-secondary" style="padding: 0.4rem 0.8rem; background: #ef4444; color: white; border-color: #ef4444;">Eliminar</button>
            </div>
        </div>
    `).join('');
}

// Función para eliminar contenido
function deleteContent(contentId) {
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Contenido eliminado correctamente', 'success');
            // Recargar la lista de contenido
            loadContentGrid();
        } else {
            showNotification(data.message || 'Error al eliminar contenido', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    });
}

// Función para editar contenido (placeholder)
function editContent(contentId) {
    showNotification('Funcionalidad de edición en desarrollo', 'info');
    console.log('Editando contenido ID:', contentId);
}

// Función para abrir modal de contenido (placeholder)
function openContentModal() {
    showNotification('Modal de contenido en desarrollo. Usa "Subir Archivos" por ahora.', 'info');
}

// Analytics
function loadAnalytics() {
    const range = document.getElementById('analyticsRange')?.value || 30;
    
    // Cargar datos de analytics
    fetch(`backend/admin/get-analytics.php?range=${range}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCharts(data.analytics);
            }
        })
        .catch(error => {
            console.error('Error cargando analytics:', error);
        });
}

function renderCharts(data) {
    // Aquí se implementarían los gráficos con Chart.js
    // Por ahora mostraremos un mensaje
    console.log('Datos de analytics:', data);
}

// Configuración
function setupFormHandlers() {
    // Configuración general
    const generalForm = document.getElementById('generalSettings');
    if (generalForm) {
        generalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveGeneralSettings();
        });
    }
    
    // Configuración de precios
    const pricingForm = document.getElementById('pricingSettings');
    if (pricingForm) {
        pricingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            savePricingSettings();
        });
    }
    
    // Configuración de archivos
    const fileForm = document.getElementById('fileSettings');
    if (fileForm) {
        fileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveFileSettings();
        });
    }
}

function saveGeneralSettings() {
    const settings = {
        siteName: document.getElementById('siteName').value,
        maintenanceMode: document.getElementById('maintenanceMode').checked,
        userRegistration: document.getElementById('userRegistration').checked
    };
    
    fetch('backend/admin/save-settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ type: 'general', settings: settings })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Configuración guardada correctamente', 'success');
        } else {
            showNotification(data.message || 'Error al guardar configuración', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    });
}

function savePricingSettings() {
    const settings = {
        despertarPrice: parseFloat(document.getElementById('despertarPrice').value),
        evolucionarPrice: parseFloat(document.getElementById('evolucionarPrice').value)
    };
    
    fetch('backend/admin/save-settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ type: 'pricing', settings: settings })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Precios actualizados correctamente', 'success');
        } else {
            showNotification(data.message || 'Error al actualizar precios', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    });
}

function saveFileSettings() {
    const settings = {
        maxFileSize: parseInt(document.getElementById('maxFileSize').value)
    };
    
    fetch('backend/admin/save-settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ type: 'files', settings: settings })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Configuración de archivos actualizada', 'success');
        } else {
            showNotification(data.message || 'Error al actualizar configuración', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    });
}

// Funciones utilitarias
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
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
    
    switch(type) {
        case 'success':
            notification.style.background = '#22c55e';
            notification.style.color = 'white';
            break;
        case 'error':
            notification.style.background = '#ef4444';
            notification.style.color = 'white';
            break;
        case 'warning':
            notification.style.background = '#f59e0b';
            notification.style.color = 'white';
            break;
        default:
            notification.style.background = 'var(--primary-color)';
            notification.style.color = 'white';
    }
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.style.transform = 'translateX(0)', 100);
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Menú móvil
function initMobileMenu() {
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

// Cerrar modales al hacer clic fuera
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Exportar funciones globales
window.navigateTo = navigateTo;
window.openUserModal = openUserModal;
window.closeUserModal = closeUserModal;
window.saveUser = saveUser;
window.editUser = editUser;
window.deleteUser = deleteUser;
window.cancelUpload = cancelUpload;
window.submitUpload = submitUpload;
window.deleteContent = deleteContent;
window.editContent = editContent;
window.openContentModal = openContentModal;

console.log('Admin Dashboard JavaScript cargado completamente');