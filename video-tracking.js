document.addEventListener('DOMContentLoaded', function() {
    const videoPlayer = document.querySelector('video');
    
    if (!videoPlayer || !videoPlayer.dataset.videoId) {
        return;
    }
    
    const videoId = videoPlayer.dataset.videoId;
    
    let isTracked = false;
    let lastProgressTime = 0;
    let trackingInterval = null;
    
    async function trackVideoProgress(progressSeconds) {
        try {
            const response = await fetch('/api/track_view.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    video_id: videoId,
                    progress_seconds: progressSeconds
                }),
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error('Ошибка сети');
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Ошибка при отслеживании просмотра');
            }
        } catch (error) {
            UIUtils.logError('отслеживании просмотра', error);
        }
    }
    
    videoPlayer.addEventListener('play', function() {
        if (!trackingInterval) {
            trackingInterval = setInterval(() => {
                const currentTime = Math.floor(videoPlayer.currentTime);
                
                if (currentTime - lastProgressTime >= 30) {
                    lastProgressTime = currentTime;
                    trackVideoProgress(currentTime);
                }
            }, 10000);
        }
    });
    
    videoPlayer.addEventListener('pause', function() {
        const currentTime = Math.floor(videoPlayer.currentTime);
        trackVideoProgress(currentTime);
    });
    
    videoPlayer.addEventListener('ended', function() {
        isTracked = true;
        
        if (trackingInterval) {
            clearInterval(trackingInterval);
            trackingInterval = null;
        }
        
        const duration = Math.floor(videoPlayer.duration);
        trackVideoProgress(duration);
    });
    
    window.addEventListener('beforeunload', function() {
        if (videoPlayer && !isTracked) {
            const currentTime = Math.floor(videoPlayer.currentTime);
            trackVideoProgress(currentTime);
            
            if (trackingInterval) {
                clearInterval(trackingInterval);
                trackingInterval = null;
            }
        }
    });
});
