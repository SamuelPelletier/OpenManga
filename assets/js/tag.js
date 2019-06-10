$(document).ready(function () {
    $('.letter-list li').on('click', function () {
        var text = $(this).text().trim();
        if (text == 'All') {
            $('.d-none').removeClass('d-none');
        } else if (text == 'Other') {
            $('.tag-list span a').each(function (index, value) {
                if ($.inArray($(value).text().trim().toUpperCase()[0], ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z']) != -1) {
                    $(value).parent().addClass('d-none');
                } else {
                    $(value).parent().removeClass('d-none');
                }
            });
        } else {
            $('.tag-list span a').each(function (index, value) {
                if ($(value).text().trim().toUpperCase()[0] != text) {
                    $(value).parent().addClass('d-none');
                } else {
                    $(value).parent().removeClass('d-none');
                }
            });
        }
    });
});