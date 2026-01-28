
const Jaguata = {
    config: {
        baseUrl: window.location.origin + '/jaguata',
        apiUrl: window.location.origin + '/jaguata/api',
        assetsUrl: window.location.origin + '/jaguata/assets',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        locale: 'es',
        currency: 'PYG',
        currencySymbol: '₲'
    },
    
    // Utilidades
    utils: {
        // Formatear precio
        formatPrice: function(price) {
            return this.currencySymbol + ' ' + new Intl.NumberFormat('es-PY').format(price);
        },
        
        // Formatear fecha
        formatDate: function(date, options = {}) {
            const defaultOptions = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            return new Intl.DateTimeFormat('es-PY', { ...defaultOptions, ...options }).format(new Date(date));
        },
        
        // Formatear fecha relativa
        formatRelativeDate: function(date) {
            const now = new Date();
            const diff = now - new Date(date);
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);
            
            if (minutes < 1) return 'Hace un momento';
            if (minutes < 60) return `Hace ${minutes} minuto${minutes > 1 ? 's' : ''}`;
            if (hours < 24) return `Hace ${hours} hora${hours > 1 ? 's' : ''}`;
            if (days < 7) return `Hace ${days} día${days > 1 ? 's' : ''}`;
            return this.formatDate(date);
        },
        
        // Generar slug
        slugify: function(text) {
            return text
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
        },
        
        // Validar email
        validateEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },
        
        // Validar teléfono paraguayo
        validatePhone: function(phone) {
            const re = /^[0-9]{4}-[0-9]{3}-[0-9]{3}$/;
            return re.test(phone);
        },
        
        // Debounce
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // Throttle
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }
    },
    
    // API
    api: {
        // Realizar petición AJAX
        request: async function(url, options = {}) {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': Jaguata.config.csrfToken
                }
            };
            
            const config = { ...defaultOptions, ...options };
            
            try {
                const response = await fetch(url, config);
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Error en la petición');
                }
                
                return data;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        },
        
        // GET
        get: function(url) {
            return this.request(url, { method: 'GET' });
        },
        
        // POST
        post: function(url, data) {
            return this.request(url, {
                method: 'POST',
                body: JSON.stringify(data)
            });
        },
        
        // PUT
        put: function(url, data) {
            return this.request(url, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        },
        
        // DELETE
        delete: function(url) {
            return this.request(url, { method: 'DELETE' });
        }
    },
    
    // Notificaciones
    notifications: {
        // Mostrar notificación
        show: function(message, type = 'info', duration = 5000) {
            const alertClass = {
                'success': 'alert-success',
                'error': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            }[type] || 'alert-info';
            
            const iconClass = {
                'success': 'fas fa-check-circle',
                'error': 'fas fa-exclamation-circle',
                'warning': 'fas fa-exclamation-triangle',
                'info': 'fas fa-info-circle'
            }[type] || 'fas fa-info-circle';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="${iconClass} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Crear contenedor si no existe
            let container = document.querySelector('.notifications-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'notifications-container position-fixed top-0 end-0 p-3';
                container.style.zIndex = '9999';
                document.body.appendChild(container);
            }
            
            // Agregar notificación
            const alertElement = document.createElement('div');
            alertElement.innerHTML = alertHtml;
            container.appendChild(alertElement.firstElementChild);
            
            // Auto-remover después del tiempo especificado
            if (duration > 0) {
                setTimeout(() => {
                    const alert = container.querySelector('.alert');
                    if (alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, duration);
            }
        },
        
        // Mostrar error
        error: function(message) {
            this.show(message, 'error');
        },
        
        // Mostrar éxito
        success: function(message) {
            this.show(message, 'success');
        },
        
        // Mostrar advertencia
        warning: function(message) {
            this.show(message, 'warning');
        },
        
        // Mostrar información
        info: function(message) {
            this.show(message, 'info');
        }
    },
    
    // Formularios
    forms: {
        // Validar formulario
        validate: function(form) {
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    this.showFieldError(input, 'Este campo es requerido');
                    isValid = false;
                } else {
                    this.clearFieldError(input);
                }
            });
            
            return isValid;
        },
        
        // Mostrar error en campo
        showFieldError: function(field, message) {
            field.classList.add('is-invalid');
            
            let feedback = field.parentNode.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                field.parentNode.appendChild(feedback);
            }
            feedback.textContent = message;
        },
        
        // Limpiar error en campo
        clearFieldError: function(field) {
            field.classList.remove('is-invalid');
            const feedback = field.parentNode.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.remove();
            }
        },
        
        // Serializar formulario
        serialize: function(form) {
            const formData = new FormData(form);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            return data;
        }
    },
    
    // Modales
    modals: {
        // Mostrar modal
        show: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        },
        
        // Ocultar modal
        hide: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
        }
    },
    
    // Loading
    loading: {
        // Mostrar loading
        show: function() {
            let spinner = document.getElementById('loading-spinner');
            if (!spinner) {
                spinner = document.createElement('div');
                spinner.id = 'loading-spinner';
                spinner.className = 'loading-spinner d-none';
                spinner.innerHTML = `
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                `;
                document.body.appendChild(spinner);
            }
            spinner.classList.remove('d-none');
        },
        
        // Ocultar loading
        hide: function() {
            const spinner = document.getElementById('loading-spinner');
            if (spinner) {
                spinner.classList.add('d-none');
            }
        }
    }
};

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicializar popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Inicializar formularios
    initializeForms();
    
    // Inicializar notificaciones
    initializeNotifications();
    
    // Inicializar back to top
    initializeBackToTop();
    
    // Inicializar validaciones
    initializeValidations();
});

// Inicializar formularios
function initializeForms() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!Jaguata.forms.validate(this)) {
                e.preventDefault();
                Jaguata.notifications.error('Por favor corrige los errores en el formulario');
            }
        });
        
        // Validación en tiempo real
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
        });
    });
}

// Validar campo individual
function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const required = field.hasAttribute('required');
    
    if (required && !value) {
        Jaguata.forms.showFieldError(field, 'Este campo es requerido');
        return false;
    }
    
    if (value) {
        if (type === 'email' && !Jaguata.utils.validateEmail(value)) {
            Jaguata.forms.showFieldError(field, 'Ingresa un email válido');
            return false;
        }
        
        if (type === 'tel' && !Jaguata.utils.validatePhone(value)) {
            Jaguata.forms.showFieldError(field, 'Ingresa un teléfono válido (0981-123-456)');
            return false;
        }
    }
    
    Jaguata.forms.clearFieldError(field);
    return true;
}

// Inicializar notificaciones
function initializeNotifications() {
    // Verificar notificaciones no leídas cada 30 segundos
    setInterval(function() {
        if (typeof checkUnreadNotifications === 'function') {
            checkUnreadNotifications();
        }
    }, 30000);
}

// Inicializar botón "volver arriba"
function initializeBackToTop() {
    const backToTopBtn = document.getElementById('back-to-top');
    
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.style.display = 'block';
            } else {
                backToTopBtn.style.display = 'none';
            }
        });
        
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

// Inicializar validaciones
function initializeValidations() {
    // Validación de contraseñas
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', function() {
            validatePassword(this);
        });
    });
    
    // Validación de confirmación de contraseña
    const confirmPasswordInputs = document.querySelectorAll('input[name="confirm_password"]');
    confirmPasswordInputs.forEach(input => {
        input.addEventListener('input', function() {
            validatePasswordConfirmation(this);
        });
    });
}

// Validar contraseña
function validatePassword(input) {
    const password = input.value;
    const minLength = 8;
    
    if (password.length > 0 && password.length < minLength) {
        Jaguata.forms.showFieldError(input, `La contraseña debe tener al menos ${minLength} caracteres`);
        return false;
    }
    
    Jaguata.forms.clearFieldError(input);
    return true;
}

// Validar confirmación de contraseña
function validatePasswordConfirmation(input) {
    const password = document.querySelector('input[name="password"]').value;
    const confirmPassword = input.value;
    
    if (confirmPassword && password !== confirmPassword) {
        Jaguata.forms.showFieldError(input, 'Las contraseñas no coinciden');
        return false;
    }
    
    Jaguata.forms.clearFieldError(input);
    return true;
}

// Funciones globales para compatibilidad
window.Jaguata = Jaguata;
window.formatPrice = Jaguata.utils.formatPrice;
window.formatDate = Jaguata.utils.formatDate;
window.formatRelativeDate = Jaguata.utils.formatRelativeDate;
