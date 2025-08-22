// Crear estrellas fugaces
function createShootingStar() {
    const star = document.createElement('div');
    star.className = 'shooting-star';
    star.style.left = Math.random() * 100 + '%';
    star.style.top = Math.random() * 100 + '%';
    star.style.animationDelay = Math.random() * 3 + 's';
    star.style.animationDuration = (Math.random() * 2 + 2) + 's';
    
    document.getElementById('starsContainer').appendChild(star);
    
    setTimeout(() => {
        star.remove();
    }, 5000);
}

// Generar estrellas fugaces continuamente (menos frecuente)
setInterval(createShootingStar, 2500);

// Ráfaga inicial de estrellas (menos estrellas)
for(let i = 0; i < 2; i++) {
    setTimeout(() => createShootingStar(), i * 800);
}

// Animación fade-in al hacer scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, observerOptions);

document.querySelectorAll('.fade-in').forEach(el => {
    observer.observe(el);
});

// Inicializar elementos del hero como visibles
setTimeout(() => {
    document.querySelectorAll('.hero .fade-in').forEach((el, index) => {
        setTimeout(() => el.classList.add('visible'), index * 300);
    });
}, 500);

// Efectos hover mejorados
document.querySelectorAll('.content-card, .feature-item').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = this.classList.contains('feature-item') 
            ? 'translateX(15px)' 
            : 'translateY(-20px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = this.classList.contains('feature-item')
            ? 'translateX(0)'
            : 'translateY(0) scale(1)';
    });
});

// Scroll suave para enlaces ancla
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});