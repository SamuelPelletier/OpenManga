const searchField = document.querySelector('.search-field');
const searchRecommendation = document.querySelector('.search-recommendation');
const tagsRecommendation = document.querySelector('.search-recommendation-tag');

searchField.addEventListener('click', () => {
    searchRecommendation.style.display = 'block';
    tagsRecommendation.style.opacity = '1';
    tagsRecommendation.style.visibility = 'visible';
});

searchField.addEventListener('blur', () => {
    searchRecommendation.style.display = 'none';
});
