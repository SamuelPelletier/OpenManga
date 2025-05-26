const searchField = document.querySelector('.search-field');
const searchRecommendation = document.querySelector('.search-recommendation');
const tagsRecommendation = document.querySelector('.search-recommendation-tag');

searchField.addEventListener('click', () => {
    if (window.innerWidth >= 768) {
        searchRecommendation.style.opacity = '1';
        searchRecommendation.style.pointerEvents = 'all';
    }
});

searchField.addEventListener('blur', () => {
    setTimeout(
        function () {
            searchRecommendation.style.opacity = '0';
            searchRecommendation.style.pointerEvents = 'none';
        }, 100);
});
