// loads the Bootstrap jQuery plugins
import 'bootstrap-sass/assets/javascripts/bootstrap/transition.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/alert.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/collapse.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/dropdown.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/modal.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/tooltip.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/popover.js';

import 'jquery'
import 'jquery-ui-autocomplete'

// loads the code syntax highlighting library
import './highlight.js';

// Creates links to the Symfony documentation
import './doclinks.js';

function pad(str, max) {
    str = str.toString();
    return str.length < max ? pad("0" + str, max) : str;
}


$(function () {
    $.ajax({
        url: "/en/json",
        method: "GET",
        success: function (data) {
            $("#search_field").autocomplete({
                source: data
            });
            $("#search_field").removeClass("search-field-load").addClass("search-field-completed");
        }
    });

    $('#toggle').on('click', function () {
        $('.search-button').click();
    });

    $("#bubble-info").popover({trigger: 'hover', placement: 'bottom'}).on('click', function (e) {
        e.preventDefault();
    });

    var interval;
    var thumbnailUrl;
    var numberPages;
    var pages;
    var newUrl;

    $('.manga img').on('mouseover', function () {
        var img = $(this);
        if (pages === undefined) {
            pages = img.closest("article").find("p");
        }
        numberPages = pages.text();
        thumbnailUrl = img.prop("src");
        if (newUrl === undefined) {
            newUrl = thumbnailUrl.substring(0, thumbnailUrl.lastIndexOf("/"));
        }
        var i = 1;
        interval = setInterval(function () {
            i++;
            img.prop("src", newUrl + "/" + pad(i, 3) + ".jpg");
            pages.text(i + "/" + numberPages);
        }, 1200);
    }).on('mouseout', function () {
        clearInterval(interval);
        $(this).prop("src", thumbnailUrl);
        pages.text(numberPages);
        pages = undefined;
        newUrl = undefined;
    });

    $('.manga').on('click', function (e) {
        $(".loader").remove();

        if (!window.event.ctrlKey) {
            $(this).append('<div class="loader"><div class="loader-inner"><div class="loader-line-wrap"><div class="loader-line"></div></div><div class="loader-line-wrap"><div class="loader-line"></div></div><div class="loader-line-wrap"><div class="loader-line"></div></div><div class="loader-line-wrap"><div class="loader-line"></div></div><div class="loader-line-wrap"><div class="loader-line"></div></div></div></div>');
        }
    })
});

