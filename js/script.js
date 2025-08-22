// Configuración de animaciones al hacer scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

// Observer para animaciones fade-up
const fadeUpObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, observerOptions);

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initializeAnimations();
    initializeNavigation();
    initializeMobileMenu();
    initializeScrollEffects();
    initializeCounters();
    initializeParallax();
    
    console.log('🌟 Gente Vigente - Cargada correctamente');
});

// Función para inicializar animaciones
function initializeAnimations() {
    // Observar elementos con animación fade-up
    document.querySelectorAll('.fade-up').forEach(el => {
        fadeUpObserver.observe(el);
    });

    // Inicializar elementos del hero como visibles después de un delay
    setTimeout(() => {
        document.querySelectorAll('.hero .fade-up').forEach((el, index) => {
            setTimeout(() => {
                el.classList.add('visible');
            }, index * 200);
        });
    }, 300);
}

// Función para inicializar navegación
function initializeNavigation() {
    // Scroll suave para enlaces de navegación
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const offsetTop = target.offsetTop - 80; // Compensar navbar fijo
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Cambiar estilo del navbar al hacer scroll
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        const scrolled = window.scrollY > 100;
        
        if (scrolled) {
            navbar.style.background = 'rgba(15, 15, 15, 0.98)';
            navbar.style.backdropFilter = 'blur(25px)';
            navbar.style.borderBottom = '1px solid rgba(255, 255, 255, 0.1)';
        } else {
            navbar.style.background = 'rgba(15, 15, 15, 0.95)';
            navbar.style.backdropFilter = 'blur(20px)';
            navbar.style.borderBottom = '1px solid rgba(255, 255, 255, 0.05)';
        }
    });
}

// Función para inicializar menú móvil
function initializeMobileMenu() {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (mobileToggle && navMenu) {
        mobileToggle.addEventListener('click', function() {
            mobileToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
            
            // Prevenir scroll del body cuando el menú está abierto
            if (navMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });

        // Cerrar menú al hacer click en enlaces
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                mobileToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        });

        // Cerrar menú al hacer click fuera
        document.addEventListener('click', function(e) {
            if (!mobileToggle.contains(e.target) && !navMenu.contains(e.target)) {
                mobileToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
}

// Función para efectos de scroll
function initializeScrollEffects() {
    // Indicador de progreso de scroll (opcional)
    const progressBar = document.createElement('div');
    progressBar.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 3px;
        background: linear-gradient(90deg, #D4AF37, #B8941F);
        z-index: 10000;
        transition: width 0.1s ease;
    `;
    document.body.appendChild(progressBar);

    window.addEventListener('scroll', () => {
        const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
        progressBar.style.width = Math.min(scrollPercent, 100) + '%';
    });

    // Efecto parallax para elementos geométricos
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const geometricCircle = document.querySelector('.geometric-circle');
        const geometricLines = document.querySelector('.geometric-lines');
        
        if (geometricCircle) {
            const speed = 0.3;
            const yPos = -(scrolled * speed);
            geometricCircle.style.transform = `translateY(${yPos}px)`;
        }
        
        if (geometricLines) {
            const speed = 0.1;
            const yPos = -(scrolled * speed);
            geometricLines.style.transform = `translateY(${yPos}px)`;
        }
    });
}

// Función para inicializar contadores animados
// function initializeCounters() {
//     function animateCounter(element, target, duration = 2000) {
//         let start = 0;
//         const increment = target / (duration / 16);
        
//         function updateCounter() {
//             start += increment;
//             if (start < target) {
//                 element.textContent = Math.floor(start) + (element.textContent.includes('%') ? '%' : element.textContent.includes('+') ? '+' : '');
//                 requestAnimationFrame(updateCounter);
//             } else {
//                 element.textContent = target + (element.textContent.includes('%') ? '%' : element.textContent.includes('+') ? '+' : '');
//             }
//         }
        
//         updateCounter();
//     }

//     // Observer para activar contadores cuando entren en vista
//     const counterObserver = new IntersectionObserver((entries) => {
//         entries.forEach(entry => {
//             if (entry.isIntersecting) {
//                 const element = entry.target;
//                 const text = element.textContent;
//                 const target = parseInt(text.replace(/[^\d]/g, ''));
                
//                 if (!isNaN(target)) {
//                     animateCounter(element, target);
//                     counterObserver.unobserve(element);
//                 }
//             }
//         });
//     });

//     document.querySelectorAll('.stat-number').forEach(counter => {
//         counterObserver.observe(counter);
//     });
// }

// Función para efectos parallax
function initializeParallax() {
    // Parallax para elementos visuales en la sección about
    const visualElements = document.querySelectorAll('.visual-element');
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        
        visualElements.forEach((element, index) => {
            const speed = 0.2 + (index * 0.1);
            const yPos = -(scrolled * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
    });
}

// Efectos hover mejorados para tarjetas
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.product-card, .testimonial, .membership-card');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            if (!this.classList.contains('featured')) {
                this.style.transform = 'translateY(-10px)';
                this.style.transition = 'all 0.3s ease';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            if (!this.classList.contains('featured')) {
                this.style.transform = 'translateY(0)';
            }
        });
    });
});

// Efecto de typing para el título principal (opcional)
function typeWriter(element, text, speed = 100) {
    let i = 0;
    element.innerHTML = '';
    
    function type() {
        if (i < text.length) {
            element.innerHTML += text.charAt(i);
            i++;
            setTimeout(type, speed);
        }
    }
    
    setTimeout(type, 1000); // Delay inicial
}

// Función para manejar formularios (para futuros forms)
function handleFormSubmission(formElement) {
    formElement.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(formElement);
        const submitBtn = formElement.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Estado de carga
        submitBtn.textContent = 'Enviando...';
        submitBtn.disabled = true;
        
        // Simular envío (aquí conectarías con tu backend)
        setTimeout(() => {
            // Mostrar mensaje de éxito
            showNotification('¡Mensaje enviado correctamente!', 'success');
            formElement.reset();
            
            // Restaurar botón
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }, 2000);
    });
}

// Sistema de notificaciones
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 1rem 2rem;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        z-index: 10000;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
        font-weight: 500;
        max-width: 300px;
    `;
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remover después de 4 segundos
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 4000);
}

// Función para detectar dispositivo móvil
function isMobile() {
    return window.innerWidth <= 768;
}

// Función para optimizar rendimiento
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

// Optimizar eventos de scroll
const debouncedScrollHandler = debounce(function() {
    // Aquí van los efectos de scroll optimizados
}, 10);

window.addEventListener('scroll', debouncedScrollHandler);

// Función para crear efectos de partículas sutiles (opcional)
function createParticleEffect() {
    if (isMobile()) return; // No mostrar en móviles para mejor rendimiento
    
    const particle = document.createElement('div');
    particle.style.cssText = `
        position: fixed;
        width: 4px;
        height: 4px;
        background: rgba(212, 175, 55, 0.3);
        border-radius: 50%;
        pointer-events: none;
        z-index: 1;
        animation: floatParticle 8s linear infinite;
    `;
    
    // Posición aleatoria
    particle.style.left = Math.random() * 100 + '%';
    particle.style.top = '100%';
    
    document.body.appendChild(particle);
    
    // Remover después de la animación
    setTimeout(() => {
        if (particle.parentNode) {
            particle.remove();
        }
    }, 8000);
}

// CSS para partículas
const particleStyles = `
    @keyframes floatParticle {
        0% {
            transform: translateY(0) rotate(0deg);
            opacity: 0;
        }
        10% {
            opacity: 1;
        }
        90% {
            opacity: 1;
        }
        100% {
            transform: translateY(-100vh) rotate(360deg);
            opacity: 0;
        }
    }
`;

// Agregar estilos de partículas
const styleSheet = document.createElement('style');
styleSheet.textContent = particleStyles;
document.head.appendChild(styleSheet);

// Generar partículas ocasionalmente
setInterval(() => {
    if (Math.random() > 0.7) { // 30% de probabilidad
        createParticleEffect();
    }
}, 3000);

// Función para manejar intersecciones avanzadas
function createAdvancedObserver() {
    const observerOptions = {
        threshold: [0, 0.25, 0.5, 0.75, 1],
        rootMargin: '-10px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const element = entry.target;
            const ratio = entry.intersectionRatio;
            
            // Efecto de fade basado en visibilidad
            if (ratio > 0.1) {
                element.style.opacity = Math.min(ratio * 2, 1);
                element.style.transform = `translateY(${(1 - ratio) * 30}px)`;
            }
        });
    }, observerOptions);
    
    // Observar elementos específicos
    document.querySelectorAll('.section-title, .section-description').forEach(el => {
        observer.observe(el);
    });
}

// Inicializar observer avanzado
setTimeout(createAdvancedObserver, 1000);

// Función para manejar redimensionado de ventana
function handleResize() {
    // Ajustar altura de elementos si es necesario
    const hero = document.querySelector('.hero');
    if (hero && window.innerHeight < 600) {
        hero.style.minHeight = '100vh';
    }
}

window.addEventListener('resize', debounce(handleResize, 250));

// Función para precargar imágenes críticas (si las hay)
function preloadCriticalImages() {
    const criticalImages = [
        // Agregar URLs de imágenes críticas aquí
    ];
    
    criticalImages.forEach(src => {
        const img = new Image();
        img.src = src;
    });
}

// Función para inicializar lazy loading (si hay imágenes)
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
}

// Función para manejar temas (claro/oscuro) - opcional
function initializeThemeToggle() {
    const themeToggle = document.querySelector('.theme-toggle');
    if (!themeToggle) return;
    
    const currentTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', currentTheme);
    
    themeToggle.addEventListener('click', function() {
        const newTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Animar cambio de tema
        document.body.style.transition = 'all 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    });
}

// Función para analytics y tracking (placeholder)
function trackEvent(eventName, parameters = {}) {
    // Aquí irían las llamadas a Google Analytics, Facebook Pixel, etc.
    console.log('Track event:', eventName, parameters);
    
    // Ejemplo para Google Analytics (si está configurado)
    if (typeof gtag !== 'undefined') {
        gtag('event', eventName, parameters);
    }
}

// Eventos de tracking para botones importantes
document.addEventListener('click', function(e) {
    const target = e.target.closest('a, button');
    if (!target) return;
    
    // Track CTA clicks
    if (target.classList.contains('hero-cta')) {
        trackEvent('hero_cta_click', { location: 'hero' });
    }
    
    if (target.classList.contains('plan-cta')) {
        const planName = target.closest('.membership-card').querySelector('.plan-title').textContent;
        trackEvent('plan_cta_click', { plan: planName });
    }
    
    if (target.classList.contains('final-cta-button')) {
        trackEvent('final_cta_click', { location: 'footer' });
    }
});

// Función para manejar errores globales
window.addEventListener('error', function(e) {
    console.error('Error global capturado:', e.error);
    // Aquí podrías enviar errores a un servicio de monitoreo
});

// Función para verificar soporte de características
function checkFeatureSupport() {
    const support = {
        intersectionObserver: 'IntersectionObserver' in window,
        webp: false,
        backdrop: CSS.supports('backdrop-filter', 'blur(10px)'),
        customProperties: CSS.supports('color', 'var(--test)')
    };
    
    // Test WebP support
    const webp = new Image();
    webp.onload = webp.onerror = function () {
        support.webp = (webp.height === 2);
    };
    webp.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
    
    return support;
}

// Inicializar verificación de soporte
const featureSupport = checkFeatureSupport();

// Aplicar fallbacks si es necesario
if (!featureSupport.backdrop) {
    document.querySelectorAll('.navbar, .manifesto-content').forEach(el => {
        el.style.backgroundColor = 'rgba(0, 0, 0, 0.9)';
    });
}

// Función para manejar el estado de carga de la página
function handlePageLoad() {
    document.body.classList.add('loaded');
    
    // Remover preloader si existe
    const preloader = document.querySelector('.preloader');
    if (preloader) {
        preloader.style.opacity = '0';
        setTimeout(() => preloader.remove(), 500);
    }
}

// Manejar carga completa de la página
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', handlePageLoad);
} else {
    handlePageLoad();
}

// Función para mejorar la accesibilidad
function improveAccessibility() {
    // Agregar indicadores de foco mejorados
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            document.body.classList.add('keyboard-navigation');
        }
    });
    
    document.addEventListener('mousedown', function() {
        document.body.classList.remove('keyboard-navigation');
    });
    
    // Mejorar navegación por teclado en elementos personalizados
    document.querySelectorAll('.product-card, .testimonial, .membership-card').forEach(card => {
        card.setAttribute('tabindex', '0');
        
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                card.click();
            }
        });
    });
}

// Inicializar mejoras de accesibilidad
improveAccessibility();

// CSS para navegación por teclado
const accessibilityStyles = `
    .keyboard-navigation *:focus {
        outline: 2px solid #D4AF37 !important;
        outline-offset: 2px !important;
    }
    
    body:not(.keyboard-navigation) *:focus {
        outline: none !important;
    }
`;

const accessibilityStyleSheet = document.createElement('style');
accessibilityStyleSheet.textContent = accessibilityStyles;
document.head.appendChild(accessibilityStyleSheet);

// Performance monitoring
const perfObserver = new PerformanceObserver((list) => {
    list.getEntries().forEach((entry) => {
        if (entry.entryType === 'largest-contentful-paint') {
            console.log('LCP:', entry.startTime);
        }
    });
});

if ('PerformanceObserver' in window) {
    perfObserver.observe({ entryTypes: ['largest-contentful-paint'] });
}

// Log final
console.log('✅ Gente Vigente - Todos los sistemas iniciados correctamente');
console.log('📊 Soporte de características:', featureSupport);