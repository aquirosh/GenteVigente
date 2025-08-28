// Back to Top - JavaScript Premium para Gente Vigente
class BackToTop {
    constructor() {
        this.button = document.getElementById('backToTop');
        this.scrollThreshold = 700; // Mostrar después de 300px
        this.isVisible = false;
        this.firstShow = true;
        
        this.init();
    }
    
    init() {
        // Eventos
        window.addEventListener('scroll', this.handleScroll.bind(this));
        this.button.addEventListener('click', this.scrollToTop.bind(this));
        
        // Check inicial
        this.handleScroll();
    }
    
    handleScroll() {
        const scrolled = window.pageYOffset;
        const shouldShow = scrolled > this.scrollThreshold;
        
        if (shouldShow && !this.isVisible) {
            this.showButton();
        } else if (!shouldShow && this.isVisible) {
            this.hideButton();
        }
        
        // Opcional: Actualizar progreso de scroll
        this.updateScrollProgress(scrolled);
    }
    
    showButton() {
        this.isVisible = true;
        this.button.classList.add('visible');
        
        // Animación especial para la primera vez
        if (this.firstShow) {
            this.button.classList.add('first-show');
            this.firstShow = false;
            
            // Remover clase después de la animación
            setTimeout(() => {
                this.button.classList.remove('first-show');
            }, 500);
        }
    }
    
    hideButton() {
        this.isVisible = false;
        this.button.classList.remove('visible');
    }
    
    scrollToTop() {
        // Scroll suave hacia arriba
        const scrollOptions = {
            top: 0,
            left: 0,
            behavior: 'smooth'
        };
        
        window.scrollTo(scrollOptions);
        
        // Feedback visual al hacer click
        this.button.style.transform = 'translateY(-5px) scale(0.95)';
        setTimeout(() => {
            this.button.style.transform = '';
        }, 150);
    }
    
    // Opcional: Mostrar progreso de scroll en el botón
    updateScrollProgress(scrolled) {
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = (scrolled / docHeight) * 360;
        
        // Si quieres mostrar el progreso, descomenta estas líneas:
        // this.button.classList.add('with-progress');
        // this.button.style.setProperty('--progress', `${progress}deg`);
    }
    
    // Métodos públicos para personalización
    setThreshold(pixels) {
        this.scrollThreshold = pixels;
    }
    
    destroy() {
        window.removeEventListener('scroll', this.handleScroll.bind(this));
        this.button.removeEventListener('click', this.scrollToTop.bind(this));
        this.button.remove();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Crear instancia del Back to Top
    const backToTop = new BackToTop();
    
    // Opcional: Personalizar threshold
    // backToTop.setThreshold(500);
    
    // Hacer disponible globalmente para debugging
    window.backToTop = backToTop;
});

// Opcional: Detectar tema oscuro automáticamente
document.addEventListener('DOMContentLoaded', function() {
    // Agregar clase dark-theme al body si el sitio usa tema oscuro
    if (document.body.style.background?.includes('#') || 
        getComputedStyle(document.body).backgroundColor === 'rgb(26, 26, 26)') {
        document.body.classList.add('dark-theme');
    }
});