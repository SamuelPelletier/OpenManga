// loads the Bootstrap jQuery plugins
import 'bootstrap-sass/assets/javascripts/bootstrap/transition.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/alert.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/collapse.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/dropdown.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/modal.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/tooltip.js';
import 'bootstrap-sass/assets/javascripts/bootstrap/popover.js';

import '@fortawesome/fontawesome-free/js/all'
import '@fortawesome/fontawesome-free/js/brands'

import 'jquery-ui-autocomplete'

// loads the code syntax highlighting library
import './highlight.js';

// Creates links to the Symfony documentation
import './doclinks';


function pad(str, max) {
    str = str.toString();
    return str.length < max ? pad("0" + str, max) : str;
}

// page counter for infinite scroll
var pageCounter = 2;
var mangaLoading = false;

let locale = window.location.pathname.split('/')[1];

function addMangaBlock() {
    mangaLoading = true;
    $('#main').append('<div id="loader-scroll"><svg version="1.1"  xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"\n' +
        '     width="24px" height="30px" viewBox="0 0 24 30" style="enable-background:new 0 0 50 50;" xml:space="preserve">\n' +
        '    <rect x="0" y="13" width="4" height="5" fill="#333">\n' +
        '      <animate attributeName="height" attributeType="XML"\n' +
        '        values="5;21;5" \n' +
        '        begin="0s" dur="0.6s" repeatCount="indefinite" />\n' +
        '      <animate attributeName="y" attributeType="XML"\n' +
        '        values="13; 5; 13"\n' +
        '        begin="0s" dur="0.6s" repeatCount="indefinite" />\n' +
        '    </rect>\n' +
        '    <rect x="10" y="13" width="4" height="5" fill="#333">\n' +
        '      <animate attributeName="height" attributeType="XML"\n' +
        '        values="5;21;5" \n' +
        '        begin="0.15s" dur="0.6s" repeatCount="indefinite" />\n' +
        '      <animate attributeName="y" attributeType="XML"\n' +
        '        values="13; 5; 13"\n' +
        '        begin="0.15s" dur="0.6s" repeatCount="indefinite" />\n' +
        '    </rect>\n' +
        '    <rect x="20" y="13" width="4" height="5" fill="#333">\n' +
        '      <animate attributeName="height" attributeType="XML"\n' +
        '        values="5;21;5" \n' +
        '        begin="0.3s" dur="0.6s" repeatCount="indefinite" />\n' +
        '      <animate attributeName="y" attributeType="XML"\n' +
        '        values="13; 5; 13"\n' +
        '        begin="0.3s" dur="0.6s" repeatCount="indefinite" />\n' +
        '    </rect>\n' +
        '  </svg></div>');
    $.ajax({
        url: "/" + locale + "/page/" + pageCounter,
        method: "GET",
        success: function (data) {
            $('#main').append(data);
            pageCounter++;
            mangaLoading = false;
            $('.navigation').css('display', 'none');
            $('#loader-scroll').remove();
        }
    });
}

$(function () {
    if (window.innerWidth < 738 && $('.manga-container').length > 0) {
        addMangaBlock();

        $(window).scroll(function () {
            if ($('#end').length === 0) {
                let mangasContainer = $('.manga-container');
                let previousLastMangaContainer = $(mangasContainer[mangasContainer.length - 2]);
                if (mangaLoading === false && window.scrollY > previousLastMangaContainer.offset().top + previousLastMangaContainer.height()) {
                    addMangaBlock();
                }
            }
        });
    }

    $('#toggle').on('click', function () {
        $('.search-button').click();
    });

    $("#bubble-info").popover({trigger: 'hover', placement: 'bottom'}).on('click', function (e) {
        e.preventDefault();
    });
    // Next image on mouseover
    /*

     var interval;
     var thumbnailUrl;
     var numberPages;
     var pages;
     var newUrl;

    $('.manga img').on('mouseover', function () {
         var img = $(this);
         if (pages === undefined) {
             pages = img.closest("article").find(".manga-count-pages");
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
     });*/

    /*$('.manga a').on('click', function (e) {
        $(".loader").remove();

        if (!window.event.ctrlKey) {
            $(this).parent().append('<div class="loader"><div class="loader-inner"><div class="loader-line-wrap"><div class="loader-line"></div></div><div class="loader-line-wrap"><div class="loader-line"></div></div><div class="loader-line-wrap"><div class="loader-line"></div></div><div class="loader-line-wrap"><div class="loader-line"></div></div><div class="loader-line-wrap"><div class="loader-line"></div></div></div></div>');
        }
    });*/

    $('.manga-remove-favorite').on('click', function (e) {
        let translationKey = "account.remove.favorite";
        var button = $(this);
        $.ajax({
            url: "/" + locale + "/translation?key=" + translationKey,
            method: "GET",
            success: function (data) {
                var confirmBox = confirm(data.response);
                var manga = button.parent();
                if (confirmBox) {
                    $.ajax({
                        url: "/en/favorite/" + manga.data("id") + "/remove",
                        method: "POST",
                        success: function (data) {
                            if ($('.manga-remove-favorite').length > 1) {
                                manga.remove();
                            } else {
                                document.location.reload();
                            }
                        }
                    });
                }
            }
        });

    })

    $('.fi-en').addClass('fi-gb');
    $('.fi-ko').addClass('fi-kr');
    $('.fi-ja').addClass('fi-jp');
});

