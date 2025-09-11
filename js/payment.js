const plans = {
    despertar: {
        name: 'Plan Despertar',
        price: '$75 USD/mes'
    },
    evolucionar: {
        name: 'Plan Evolucionar', 
        price: '$125 USD/mes'
    }
};

let isProcessingPayment = false;

function openPaymentModal(planType) {
    if (!plans[planType]) {
        alert('Plan no válido');
        return;
    }
    
    const modal = document.getElementById('paymentModal');
    const planName = document.getElementById('planName');
    const planPrice = document.getElementById('planPrice');
    const selectedPlan = document.getElementById('selectedPlan');
    const planSummary = document.querySelector('.plan-summary');
    
    planName.textContent = plans[planType].name;
    planPrice.textContent = plans[planType].price;
    selectedPlan.value = planType;
    
    // FORZAR el cambio de background directamente con JavaScript
    if (planSummary) {
        // Limpiar clases previas
        planSummary.className = 'plan-summary';
        
        // Aplicar background directamente
        if (planType === 'despertar') {
            planSummary.style.background = 'linear-gradient(135deg, #a36628, #CD7F32, #ee943a)';
            planSummary.style.boxShadow = '0 4px 20px rgba(205, 127, 50, 0.3)';
        } else if (planType === 'evolucionar') {
            planSummary.style.background = 'linear-gradient(135deg, #CC9432, #E4B948, #EEC851)';
            planSummary.style.boxShadow = '0 4px 20px rgba(228, 185, 72, 0.3)';
        }
        
        console.log('Plan:', planType);
        console.log('Background aplicado:', planSummary.style.background);
    } else {
        console.error('No se encontró .plan-summary');
    }
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    document.getElementById('paymentForm').reset();
    document.getElementById('selectedPlan').value = planType;
    clearAlerts();
    
    setTimeout(() => {
        document.getElementById('firstName').focus();
    }, 100);
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    clearAlerts();
    setLoadingState(false);
}

function showAlert(message, type = 'error') {
    const container = document.getElementById('alertContainer');
    const alertClass = type === 'error' ? 'error-message' : 'success-message';
    
    container.innerHTML = `<div class="${alertClass}">${message}</div>`;
    
    const modalContent = document.querySelector('.modal-content');
    modalContent.scrollTop = 0;
    
    if (type === 'success') {
        setTimeout(() => {
            container.innerHTML = '';
        }, 8000);
    }
}

function clearAlerts() {
    document.getElementById('alertContainer').innerHTML = '';
}

async function processPayment() {
    if (isProcessingPayment) return;
    
    const form = document.getElementById('paymentForm');
    if (!form.checkValidity()) {
        showAlert('Por favor, completa todos los campos correctamente.');
        return;
    }
    
    const formData = {
        plan: document.getElementById('selectedPlan').value,
        firstName: document.getElementById('firstName').value.trim(),
        lastName: document.getElementById('lastName').value.trim(),
        email: document.getElementById('email').value.trim()
    };
    
    if (formData.firstName.length < 2) {
        showAlert('El nombre debe tener al menos 2 caracteres.');
        return;
    }
    
    if (formData.lastName.length < 2) {
        showAlert('El apellido debe tener al menos 2 caracteres.');
        return;
    }
    
    if (!validateEmail(formData.email)) {
        showAlert('Por favor, ingresa un email válido.');
        return;
    }
    
    setLoadingState(true);
    clearAlerts();
    
    try {
        const response = await fetch('backend/payments/process-payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('¡Perfecto! Redirigiendo a PayPal para completar el pago...', 'success');
            
            setTimeout(() => {
                window.location.href = result.approval_url;
            }, 2000);
        } else {
            showAlert(result.message || 'Error procesando el pago. Inténtalo nuevamente.');
            setLoadingState(false);
        }
        
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión. Verifica tu internet e inténtalo nuevamente.');
        setLoadingState(false);
    }
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function setLoadingState(loading) {
    const btn = document.getElementById('paypalBtn');
    const form = document.getElementById('paymentForm');
    
    isProcessingPayment = loading;
    
    if (loading) {
        btn.disabled = true;
        btn.innerHTML = '<div class="loading-spinner"></div> Procesando...';
        form.style.pointerEvents = 'none';
    } else {
        btn.disabled = false;
        btn.innerHTML = '<img src="https://www.paypalobjects.com/webstatic/icon/pp258.png" alt="PayPal" width="20" height="20"> Pagar con PayPal';
        form.style.pointerEvents = 'auto';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const paypalBtn = document.getElementById('paypalBtn');
    if (paypalBtn) {
        paypalBtn.addEventListener('click', processPayment);
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePaymentModal();
        }
    });
    
    const inputs = document.querySelectorAll('#paymentForm input[type="text"], #paymentForm input[type="email"]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.style.borderColor = '#ef4444';
            } else {
                this.style.borderColor = '#e5e7eb';
            }
        });
        
        input.addEventListener('input', function() {
            if (this.style.borderColor === 'rgb(239, 68, 68)' && this.value.trim()) {
                this.style.borderColor = '#e5e7eb';
            }
        });
    });
    
    const form = document.getElementById('paymentForm');
    if (form) {
        form.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                processPayment();
            }
        });
    }
    
    // Manejar mensajes de error/cancelación en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const cancelled = urlParams.get('cancelled');
    
    if (error) {
        alert('Error: ' + decodeURIComponent(error));
    }
    
    if (cancelled) {
        alert('Pago cancelado. Puedes intentar nuevamente cuando gustes.');
    }
});