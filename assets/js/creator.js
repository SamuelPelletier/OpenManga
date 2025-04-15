let locale = window.location.pathname.split('/')[1];

function registerOnClick() {
    $(".search-result-item").on("click", function (event) {
        let input = $('#' + event.target.parentNode.className)[0];
        if (input.value.includes(',')) {
            input.value = input.value.split(",").slice(0, -1).join(',') + "," + event.target.innerHTML + ",";
        } else {
            input.value = "" + event.target.innerHTML + ",";
        }
        event.target.parentNode.remove();
    })
}

$(function () {
    $('#manga_new_form_tags').parent().append("<div id='tag-search'></div>");
    let tagSearch = $('#tag-search')[0];
    $('#manga_new_form_tags').on('keydown', function (event) {
        const regex = /^[a-z0-9\\.\\-\\ ]$/;
        if (!regex.test(event.key) && event.key !== 'Backspace') {
            event.preventDefault()
        }
    });
    $('#manga_new_form_tags').on('keyup', function (event) {
        tagSearch.innerHTML = "";
        let fieldValue = event.target.value;
        let search = fieldValue.split(",").pop();
        if (search.length >= 3) {
            $.ajax({
                url: "/" + locale + "/tags/search?q=" + search,
                method: "GET",
                success: function (data) {
                    let result = data.data;
                    if (result.length > 0) {
                        let content = "<ul class='manga_new_form_tags'>";
                        result.forEach(function (item) {
                            content += "<li class='search-result-item'>" + item + "</li>"
                        })
                        tagSearch.innerHTML = content + "</ul>"
                        registerOnClick();
                    } else {
                        tagSearch.innerHTML = "No result"
                    }
                }
            });
        }
    })

    $('#manga_new_form_parodies').parent().append("<div id='parody-search'></div>");
    let parodySearch = $('#parody-search')[0];
    $('#manga_new_form_parodies').on('keydown', function (event) {
        if (event.key == ',') {
            event.preventDefault()
        }
    });

    $('#manga_new_form_parodies').on('keyup', function (event) {
        parodySearch.innerHTML = "";
        let fieldValue = event.target.value;
        let search = fieldValue.split(",").pop();
        if (search.length >= 3) {
            $.ajax({
                url: "/" + locale + "/parodies/search?q=" + search,
                method: "GET",
                success: function (data) {
                    let result = data.data;
                    if (result.length > 0) {
                        let content = "<ul class='manga_new_form_parodies'>";
                        result.forEach(function (item) {
                            content += "<li class='search-result-item'>" + item + "</li>"
                        })
                        parodySearch.innerHTML = content + "</ul>"
                        registerOnClick();
                    } else {
                        parodySearch.innerHTML = "No result"
                    }
                }
            });
        }
    })
});
