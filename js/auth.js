/**
 * Обработка выхода из системы
 */
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.querySelector('.logout-btn');
    if (!logoutBtn) return;

    logoutBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        
        try {
            // Показываем процесс
            const originalText = logoutBtn.innerHTML;
            logoutBtn.innerHTML = '<img src="img/navbar/loading.gif" alt="Loading" class="nav-icon"> Выход...';
            logoutBtn.disabled = true;

            const response = await fetch('/api/auth/logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin' // Важно для работы с сессией
            });

            if (!response.ok) {
                throw new Error('Ошибка сети');
            }

            const result = await response.json();

            if (result.success) {
                window.location.href = '/login.php';
            } else {
                throw new Error(result.message || 'Ошибка при выходе');
            }
        } catch (error) {
            console.error('Ошибка:', error);
            logoutBtn.innerHTML = originalText;
            logoutBtn.disabled = false;
            alert(error.message || 'Произошла ошибка при выходе. Попробуйте позже.');
        }
    });
});
