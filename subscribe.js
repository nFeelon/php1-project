document.addEventListener('DOMContentLoaded', function() {
    const subscribeButtons = document.querySelectorAll('.subscribe-btn');

    subscribeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            if (!isLoggedIn) {
                window.location.href = '/login.php';
                return;
            }
            
            const channelId = this.dataset.channelId;
            const isSubscribed = this.dataset.subscribed === 'true';

            const formData = new FormData();
            formData.append('channel_id', channelId);
            formData.append('action', isSubscribed ? 'unsubscribe' : 'subscribe');

            this.disabled = true;
            const buttonText = this.textContent;
            this.textContent = isSubscribed ? 'Отписываемся...' : 'Подписываемся...';

            fetch('/api/subscribe.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.dataset.subscribed = data.is_subscribed.toString();
                    this.textContent = data.is_subscribed ? 'Отписаться' : 'Подписаться';
                    this.classList.toggle('subscribed', data.is_subscribed);
                    
                    const subscribersCountElement = document.querySelector('.subscribers-count');
                    if (subscribersCountElement) {
                        subscribersCountElement.textContent = data.subscribers_formatted;
                    }

                    UIUtils.showNotification(data.message, 'success');
                } else {
                    UIUtils.showNotification(data.message, 'error');
                    this.textContent = buttonText;
                }
            })
            .catch(error => {
                UIUtils.logError('выполнении операции подписки', error);
                UIUtils.showNotification('Произошла ошибка при выполнении операции', 'error');
                this.textContent = buttonText;
            })
            .finally(() => {
                this.disabled = false;
            });
        });
    });
});
