document.addEventListener('DOMContentLoaded', function() {
    const sortButton = document.querySelector('.sort-button');
    const sortDropdown = document.querySelector('.sort-dropdown');
    
    if (!sortButton || !sortDropdown) return;
    
    sortButton.addEventListener('click', function(e) {
        e.preventDefault();
        sortDropdown.classList.toggle('active');
    });
    
    document.addEventListener('click', function(e) {
        if (!sortDropdown.contains(e.target)) {
            sortDropdown.classList.remove('active');
        }
    });
    
    const sortItems = document.querySelectorAll('.sort-item');
    sortItems.forEach(item => {
        item.addEventListener('click', function() {
            sortDropdown.classList.remove('active');
        });
    });
});
