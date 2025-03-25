var MAX_SEARCH_HISTORY = 5;

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const searchForm = document.getElementById('search-form');
    const historyContainer = document.getElementById('search-history-container');
    const historyList = document.getElementById('search-history-list');
    const clearHistoryBtn = document.getElementById('clear-history-btn');
    
    let searchHistory = getSearchHistory();
    
    searchInput.addEventListener('focus', function() {
        renderSearchHistory();
        historyContainer.style.display = 'block';
    });
    
    document.addEventListener('click', function(event) {
        if (!historyContainer.contains(event.target) && event.target !== searchInput) {
            historyContainer.style.display = 'none';
        }
    });
    
    searchForm.addEventListener('submit', function(event) {
        const query = searchInput.value.trim();
        if (query) {
            saveSearchQuery(query);
        }
    });
    
    clearHistoryBtn.addEventListener('click', function(event) {
        event.stopPropagation();
        localStorage.removeItem('searchHistory');
        searchHistory = [];
        renderSearchHistory();
    });
    
    function getSearchHistory() {
        const history = localStorage.getItem('searchHistory');
        return history ? JSON.parse(history) : [];
    }
    
    function saveSearchQuery(query) {
        searchHistory = searchHistory.filter(item => item !== query);
        
        searchHistory.unshift(query);
        
        if (searchHistory.length > MAX_SEARCH_HISTORY) {
            searchHistory = searchHistory.slice(0, MAX_SEARCH_HISTORY);
        }
        
        localStorage.setItem('searchHistory', JSON.stringify(searchHistory));
    }
    
    function renderSearchHistory() {
        historyList.innerHTML = '';
        
        if (searchHistory.length === 0) {
            historyList.innerHTML = '<li class="no-history">История поиска пуста</li>';
            return;
        }
        
        searchHistory.forEach(query => {
            const li = document.createElement('li');
            li.className = 'history-item';
            li.textContent = query;
            
            li.addEventListener('click', function() {
                searchInput.value = query;
                historyContainer.style.display = 'none';
                searchForm.submit();
            });
            
            historyList.appendChild(li);
        });
    }
});
