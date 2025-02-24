/**
 * Обработка формы регистрации
 */
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    if (!form) return;

    const inputs = {
        displayName: document.getElementById('displayName'),
        email: document.getElementById('email'),
        password: document.getElementById('password'),
        confirmPassword: document.getElementById('confirmPassword')
    };

    // Проверяем наличие всех необходимых элементов
    if (!Object.values(inputs).every(Boolean)) {
        console.error('Не найдены все необходимые поля формы');
        return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    if (!submitButton) return;

    let formValid = false;

    // Настройка отображения требований
    Object.values(inputs).forEach(input => {
        const requirements = input.closest('.input-wrapper')?.querySelector('.validation-requirements');
        if (requirements) {
            FormUtils.setupFieldRequirements(input, requirements);
        }
    });

    // Валидаторы для полей
    const validators = {
        displayName: (value) => {
            const sanitizedValue = FormUtils.sanitizeInput(value);
            const requirements = {
                'name-length': sanitizedValue.length >= 2,
                'name-letters': ValidationPatterns.username.test(sanitizedValue),
                'name-no-digits': !/\d/.test(sanitizedValue)
            };
            FormUtils.updateRequirements(requirements);
            return Object.values(requirements).every(Boolean);
        },
        email: (value) => {
            const sanitizedValue = FormUtils.sanitizeInput(value);
            const requirements = {
                'email-format': ValidationPatterns.email.test(sanitizedValue),
                'email-at': sanitizedValue.includes('@'),
                'email-domain': sanitizedValue.includes('@') && sanitizedValue.split('@')[1].includes('.')
            };
            FormUtils.updateRequirements(requirements);
            return Object.values(requirements).every(Boolean);
        },
        password: (value) => {
            const requirements = {
                'length': value.length >= ValidationPatterns.password.minLength,
                'uppercase': ValidationPatterns.password.patterns.uppercase.test(value),
                'lowercase': ValidationPatterns.password.patterns.lowercase.test(value),
                'number': ValidationPatterns.password.patterns.number.test(value),
                'special': ValidationPatterns.password.patterns.special.test(value)
            };
            FormUtils.updateRequirements(requirements);
            return Object.values(requirements).every(Boolean);
        },
        confirmPassword: (value) => {
            const requirements = {
                'passwords-match': value === inputs.password.value
            };
            FormUtils.updateRequirements(requirements);
            return Object.values(requirements).every(Boolean);
        }
    };

    // Валидация в реальном времени
    Object.keys(inputs).forEach(inputName => {
        const input = inputs[inputName];
        
        input.addEventListener('input', function() {
            const value = FormUtils.getFieldValue(this);
            const isValid = validators[inputName](value);
            
            input.classList.toggle('invalid', !isValid);
            input.classList.toggle('valid', isValid);

            formValid = Object.keys(inputs).every(name => 
                validators[name](FormUtils.getFieldValue(inputs[name]))
            );
            submitButton.disabled = !formValid;
        });
    });

    // Отправка формы
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (!formValid) return;

        try {
            FormUtils.clearErrors(inputs);
            FormUtils.toggleSubmitButton(submitButton, true, 'Создание аккаунта...');

            const response = await fetch('/api/auth/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: FormUtils.sanitizeInput(inputs.displayName.value.trim()),
                    email: FormUtils.sanitizeInput(inputs.email.value.trim()),
                    password: inputs.password.value
                })
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = '/login.php';
            } else {
                // Определяем, какое поле вызвало ошибку
                if (result.message.toLowerCase().includes('email')) {
                    FormUtils.showError('email', result.message, inputs);
                } else if (result.message.toLowerCase().includes('имя') || 
                         result.message.toLowerCase().includes('username')) {
                    FormUtils.showError('displayName', result.message, inputs);
                } else {
                    FormUtils.showError('displayName', result.message, inputs);
                }
                formValid = false;
            }
        } catch (error) {
            FormUtils.handleApiError(error, inputs);
        } finally {
            FormUtils.toggleSubmitButton(submitButton, false);
        }
    });
});