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
    })
});

