document.addEventListener('DOMContentLoaded', function() {
    initEventHandlers();
});

function initEventHandlers() {
    const navItems = document.querySelectorAll('.channel-nav-item');
    const sections = document.querySelectorAll('.channel-section');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            navItems.forEach(navItem => navItem.classList.remove('active'));
            this.classList.add('active');
            const targetId = this.getAttribute('href').substring(1);
            sections.forEach(section => section.style.display = 'none');
            document.getElementById(targetId).style.display = 'block';
        });
    });

    const subscribeBtn = document.querySelector('.subscribe-btn');
    if (subscribeBtn) {
        subscribeBtn.addEventListener('click', async function() {
            const channelId = this.dataset.channelId;
            const isSubscribed = this.dataset.subscribed === 'true';
            const action = isSubscribed ? 'unsubscribe' : 'subscribe';
            
            try {
                const formData = new FormData();
                formData.append('channel_id', channelId);
                formData.append('action', action);
                
                const response = await fetch('/api/subscribe.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.textContent = result.is_subscribed ? 'Отписаться' : 'Подписаться';
                    this.dataset.subscribed = result.is_subscribed ? 'true' : 'false';
                    this.classList.toggle('subscribed', result.is_subscribed);
                    const subscribersCount = document.getElementById('subscribers-count');
                    if (subscribersCount) {
                        subscribersCount.textContent = result.subscribers_formatted;
                    }
                    
                    UIUtils.showNotification(result.message || 'Подписка успешно изменена', 'success');
                } else {
                    UIUtils.showNotification(result.message || 'Произошла ошибка при изменении подписки', 'error');
                }
            } catch (error) {
                UIUtils.logError('изменении подписки', error);
                UIUtils.showNotification('Произошла ошибка при изменении подписки', 'error');
            }
        });
    }
}
