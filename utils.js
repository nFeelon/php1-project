// Общие функции для работы с формами
const FormUtils = {
    clearErrors: (inputs) => {
        Object.keys(inputs).forEach(inputName => {
            const input = inputs[inputName];
            const errorDiv = document.getElementById(inputName + 'Error');
            if (errorDiv) {
                errorDiv.textContent = '';
                errorDiv.style.display = 'none';
            }
            input.classList.remove('invalid');
        });
    },

    showError: (inputName, message, inputs) => {
        const input = inputs[inputName];
        const errorDiv = document.getElementById(inputName + 'Error');
        if (input && errorDiv) {
            input.classList.add('invalid');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
    },

    handleApiError: (error, inputs) => {
        console.error('Ошибка:', error);

        let errorMessage = 'Произошла ошибка. Попробуйте позже.';
        
        if (error instanceof TypeError && error.message === 'Failed to fetch') {
            errorMessage = 'Нет соединения с сервером. Проверьте подключение к интернету.';
        } else if (error.status === 429) {
            errorMessage = 'Слишком много попыток. Пожалуйста, подождите несколько минут.';
        } else if (error.status === 403) {
            errorMessage = 'Доступ запрещен. Попробуйте войти заново.';
        }
        
        FormUtils.showError(Object.keys(inputs)[0], errorMessage, inputs);
    },

    toggleSubmitButton: (button, disabled = true, loadingText = '', originalText = '') => {
        if (!button) return;
        
        button.disabled = disabled;
        if (disabled && loadingText) {
            button._originalText = button.textContent;
            button.innerHTML = `<img src="/img/loading.gif" alt="Загрузка" class="btn-loader"> ${loadingText}`;
        } else if (!disabled && button._originalText) {
            button.textContent = button._originalText;
        }
    },

    // Настройка отображения требований к полям
    setupFieldRequirements: (input, requirements) => {
        if (!input || !requirements) return;

        input.addEventListener('focus', () => {
            document.querySelectorAll('.validation-requirements').forEach(req => req.style.display = 'none');
            requirements.style.display = 'block';
        });

        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !requirements.contains(e.target)) {
                requirements.style.display = 'none';
            }
        });
    },

    updateRequirements: (requirements) => {
        Object.entries(requirements).forEach(([id, isValid]) => {
            const element = document.getElementById(id);
            if (element) {
                element.classList.toggle('valid', isValid);
                element.classList.toggle('invalid', !isValid);
            }
        });
    },

    getFieldValue: (input) => {
        if (!input) return '';
        return typeof input.value === 'string' ? input.value.trim() : '';
    },

    sanitizeInput: (value) => {
        if (typeof value !== 'string') return '';
        
        return value
            .replace(/[<>]/g, '') // Базовая защита от XSS
            .replace(/javascript:/gi, '') // Защита от javascript: URL
            .replace(/data:/gi, '') // Защита от data: URL
            .replace(/\\/g, '\\\\') // Экранирование обратных слешей
            .replace(/"/g, '\\"') // Экранирование кавычек
            .trim(); // Удаление пробелов
    },

    // Проверка поддержки Fetch API
    checkFetchSupport: () => {
        return 'fetch' in window && 'Promise' in window;
    }
};

// Общие регулярные выражения для валидации
const ValidationPatterns = {
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    username: /^[а-яА-Яa-zA-Z\s-]+$/i,
    password: {
        minLength: 8,
        patterns: {
            uppercase: /[A-Z]/,
            lowercase: /[a-z]/,
            number: /[0-9]/,
            special: /[!@#$%^&*]/
        }
    }
};

// Утилиты для работы с форматированием и уведомлениями
const UIUtils = {
    formatNumber: (num) => {
        return new Intl.NumberFormat('ru-RU').format(num);
    },
    
    formatTimeAgo: (dateStr) => {
        const date = new Date(dateStr);
        const now = new Date();
        const diffMs = now - date;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHour = Math.floor(diffMin / 60);
        const diffDay = Math.floor(diffHour / 24);
        const diffMonth = Math.floor(diffDay / 30);
        const diffYear = Math.floor(diffMonth / 12);

        if (diffSec < 60) {
            return 'только что';
        } else if (diffMin < 60) {
            return `${diffMin} ${UIUtils.pluralize(diffMin, 'минута', 'минуты', 'минут')} назад`;
        } else if (diffHour < 24) {
            return `${diffHour} ${UIUtils.pluralize(diffHour, 'час', 'часа', 'часов')} назад`;
        } else if (diffDay < 30) {
            return `${diffDay} ${UIUtils.pluralize(diffDay, 'день', 'дня', 'дней')} назад`;
        } else if (diffMonth < 12) {
            return `${diffMonth} ${UIUtils.pluralize(diffMonth, 'месяц', 'месяца', 'месяцев')} назад`;
        } else {
            return `${diffYear} ${UIUtils.pluralize(diffYear, 'год', 'года', 'лет')} назад`;
        }
    },
    
    // Функция для склонения существительных
    pluralize: (count, oneForm, twoForm, fiveForm) => {
        const lastDigit = count % 10;
        const lastTwoDigits = count % 100;
        
        if (lastTwoDigits >= 11 && lastTwoDigits <= 19) {
            return fiveForm;
        }
        
        if (lastDigit === 1) {
            return oneForm;
        }
        
        if (lastDigit >= 2 && lastDigit <= 4) {
            return twoForm;
        }
        
        return fiveForm;
    },
    
    // Отображение уведомлений
    showNotification: (message, type = 'info') => {
        let notificationContainer = document.getElementById('notification-container');

        if (!notificationContainer) {
            notificationContainer = document.createElement('div');
            notificationContainer.id = 'notification-container';
            document.body.appendChild(notificationContainer);
            notificationContainer.style.position = 'fixed';
            notificationContainer.style.top = '20px';
            notificationContainer.style.right = '20px';
            notificationContainer.style.zIndex = '9999';
        }
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;

        notification.style.backgroundColor = type === 'success' ? '#4CAF50' : 
                                          type === 'error' ? '#F44336' : '#2196F3';
        notification.style.color = 'white';
        notification.style.padding = '12px 20px';
        notification.style.marginBottom = '10px';
        notification.style.borderRadius = '4px';
        notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s ease';

        notificationContainer.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '1';
        }, 10);

        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notificationContainer.removeChild(notification);
            }, 300);
        }, 3000);
    },

    logError: (context, error) => {
        // Проверяем, является ли ошибка объектом SyntaxError и содержит ли HTML-теги
        if (error instanceof SyntaxError && 
            error.message && 
            (error.message.includes('<br') || 
             error.message.includes('<b>') || 
             error.message.includes('<!DOCTYPE'))) {
            console.error(`Ошибка в ${context}: Сервер вернул HTML вместо JSON. Возможно, произошла ошибка на сервере.`);
        } else {
            console.error(`Ошибка в ${context}:`, error);
        }
    }
};

window.FormUtils = FormUtils;
window.ValidationPatterns = ValidationPatterns;
window.UIUtils = UIUtils;