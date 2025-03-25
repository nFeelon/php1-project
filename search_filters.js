document.addEventListener('DOMContentLoaded', function() {
    const filtersButton = document.querySelector('.filters-button');
    const filtersContent = document.querySelector('.filters-content');
    
    if (!filtersButton || !filtersContent) return;
    
    filtersButton.addEventListener('click', function(e) {
        e.preventDefault();
        filtersButton.classList.toggle('active');
        filtersContent.classList.toggle('active');
    });
    
    document.addEventListener('click', function(e) {
        if (!filtersContent.contains(e.target) && e.target !== filtersButton && !filtersButton.contains(e.target)) {
            filtersButton.classList.remove('active');
            filtersContent.classList.remove('active');
        }
    });
});
