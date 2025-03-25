document.addEventListener('DOMContentLoaded', function() {
    const backButton = document.querySelector('.back-button');
    if (!backButton) return;

    // Проверяем, есть ли предыдущая страница в истории
    if (document.referrer && new URL(document.referrer).hostname === window.location.hostname) {
        backButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.history.back();
        });
    } else {
        backButton.href = '/';
    }
});
