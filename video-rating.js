document.addEventListener('DOMContentLoaded', function() {
    const likeBtn = document.querySelector('.like-btn');
    const dislikeBtn = document.querySelector('.dislike-btn');

    if (!likeBtn || !dislikeBtn || !likeBtn.dataset.videoId) {
        console.log('Кнопки не найдены или нет ID видео');
        return;
    }

    const videoId = likeBtn.dataset.videoId;
    console.log('Инициализация для видео:', videoId);
    
    async function rateVideo(action) {
        console.log('Отправка оценки:', action);
        try {
            likeBtn.classList.add('loading');
            dislikeBtn.classList.add('loading');
            
            const requestData = {
                video_id: videoId,
                action: action
            };
            console.log('Отправляемые данные:', requestData);
            
            const response = await fetch('/api/video_rating.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestData),
            });
            
            console.log('Получен ответ:', response.status);
            const result = await response.json();
            console.log('Данные ответа:', result);
            
            if (!response.ok) {
                throw new Error(result.message || 'Ошибка сети');
            }
            
            if (result.success) {
                updateRatingUI(result.data);
            } else {
                throw new Error(result.message || 'Ошибка при обновлении оценки');
            }
        } catch (error) {
            UIUtils.logError('отправке оценки', error);
            UIUtils.showNotification(error.message || 'Произошла ошибка при обновлении оценки', 'error');
        } finally {
            likeBtn.classList.remove('loading');
            dislikeBtn.classList.remove('loading');
        }
    }

    function updateRatingUI(data) {
        console.log('Обновление UI с данными:', data);

        likeBtn.querySelector('span').textContent = UIUtils.formatNumber(data.likes_count);
        dislikeBtn.querySelector('span').textContent = UIUtils.formatNumber(data.dislikes_count);

        likeBtn.classList.remove('active');
        dislikeBtn.classList.remove('active');
        
        if (data.current_rating === true) {
            likeBtn.classList.add('active');
        } else if (data.current_rating === false) {
            dislikeBtn.classList.add('active');
        }
    }

    likeBtn.addEventListener('click', function() {
        rateVideo(this.classList.contains('active') ? 'remove' : 'like');
    });
    
    dislikeBtn.addEventListener('click', function() {
        rateVideo(this.classList.contains('active') ? 'remove' : 'dislike');
    });
});
