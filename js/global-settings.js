// Global Settings Checker - Aplicar en todas las páginas
(function() {
    // Función para aplicar configuraciones guardadas
    function applyGlobalSettings() {
        try {
            const settings = JSON.parse(localStorage.getItem('gv-settings') || '{}');
            
            // Aplicar configuración de animaciones
            if (settings.animations === false) {
                document.body.classList.add('animations-disabled');
                console.log('Animaciones deshabilitadas globalmente');
            }
            
            // Respetar prefers-reduced-motion
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.body.classList.add('animations-disabled');
            }
            
        } catch (error) {
            console.warn('Error cargando configuraciones globales:', error);
        }
    }
    
    // Aplicar inmediatamente
    applyGlobalSettings();
    
    // Aplicar cuando el DOM esté listo (por si acaso)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyGlobalSettings);
    }
})();