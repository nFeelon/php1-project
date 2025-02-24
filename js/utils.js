/**
 * Утилиты для работы с формами и валидацией
 */

// Общие функции для работы с формами
const FormUtils = {
    // Очистка ошибок формы
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

    // Показ ошибки для конкретного поля
    showError: (inputName, message, inputs) => {
        const input = inputs[inputName];
        const errorDiv = document.getElementById(inputName + 'Error');
        if (input && errorDiv) {
            input.classList.add('invalid');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
    },

    // Обработка ответов с сервера
    handleApiError: (error, inputs) => {
        console.error('Ошибка:', error);
        FormUtils.showError(Object.keys(inputs)[0], 'Произошла ошибка. Попробуйте позже.', inputs);
    },

    // Управление состоянием кнопки отправки
    toggleSubmitButton: (button, disabled = true, loadingText = '', originalText = '') => {
        button.disabled = disabled;
        if (disabled && loadingText) {
            button._originalText = button.textContent;
            button.textContent = loadingText;
        } else if (!disabled && button._originalText) {
            button.textContent = button._originalText;
        }
    },

    // Настройка отображения требований к полям
    setupFieldRequirements: (input, requirements) => {
        if (!input || !requirements) return;

        // Показ требований при фокусе
        input.addEventListener('focus', () => {
            document.querySelectorAll('.validation-requirements').forEach(req => req.style.display = 'none');
            requirements.style.display = 'block';
        });

        // Скрытие требований при клике вне поля
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !requirements.contains(e.target)) {
                requirements.style.display = 'none';
            }
        });
    },

    // Обновление статуса требований
    updateRequirements: (requirements) => {
        Object.entries(requirements).forEach(([id, isValid]) => {
            const element = document.getElementById(id);
            if (element) {
                element.classList.toggle('valid', isValid);
                element.classList.toggle('invalid', !isValid);
            }
        });
    },

    // Безопасное получение значения поля
    getFieldValue: (input) => {
        return input ? input.value.trim() : '';
    },

    // Проверка на XSS
    sanitizeInput: (value) => {
        return value.replace(/[<>]/g, '');
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

// Экспорт утилит
window.FormUtils = FormUtils;
window.ValidationPatterns = ValidationPatterns;