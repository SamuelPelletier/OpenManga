$(document).ready(function () {

    if (getParameterByName('qt') === null || getParameterByName('qt') === '') {
        $('.letter-list li:first-child a').css('color', '#ff306a');
    } else {
        $('.letter-list li a').each(function (index, node) {
            if ($(node).text().trim().toLowerCase() === getParameterByName('qt').toLowerCase()) {
                $(node).css('color', '#ff306a');
                return false;
            }
        })
    }
});

function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}