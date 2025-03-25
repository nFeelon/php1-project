/**
 * Обработка формы входа
 */
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginForm');
    if (!form) return;

    const inputs = {
        email: document.getElementById('email'),
        password: document.getElementById('password')
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
        email: (value) => {
            const sanitizedValue = FormUtils.sanitizeInput(value);
            const requirements = {
                'email-format': ValidationPatterns.email.test(sanitizedValue)
            };
            FormUtils.updateRequirements(requirements);
            return Object.values(requirements).every(Boolean);
        },
        password: (value) => {
            const requirements = {
                'password-filled': value.length > 0
            };
            FormUtils.updateRequirements(requirements);
            return Object.values(requirements).every(Boolean);
        }
    };

    // Валидация в реальном времени
    Object.keys(inputs).forEach(inputName => {
        const input = inputs[inputName];
        
        input.addEventListener('input', function () {
            const value = FormUtils.getFieldValue(this);
            const isValid = validators[inputName](value);
            
            input.classList.toggle('invalid', !isValid);
            input.classList.toggle('valid', isValid);

            // Проверка валидности всей формы
            formValid = Object.keys(inputs).every(name => 
                validators[name](FormUtils.getFieldValue(inputs[name]))
            );
            submitButton.disabled = !formValid;
        });
    });

    // Отправка формы
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!formValid) return;

        try {
            FormUtils.clearErrors(inputs);
            FormUtils.toggleSubmitButton(submitButton, true, 'Вход...');

            const formData = {
                email: FormUtils.sanitizeInput(inputs.email.value.trim()),
                password: inputs.password.value,
                remember: document.getElementById('remember')?.checked || false
            };

            const response = await fetch('/api/auth/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = '/';
            } else {
                inputs.email.classList.add('invalid');
                inputs.password.classList.add('invalid');
                FormUtils.showError('email', result.message || 'Неверный email или пароль', inputs);
                inputs.password.value = '';
                formValid = false;
            }
        } catch (error) {
            FormUtils.handleApiError(error, inputs);
        } finally {
            FormUtils.toggleSubmitButton(submitButton, false);
        }
    });
});