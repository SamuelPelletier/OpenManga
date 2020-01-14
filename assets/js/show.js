/**
 * Created by Samuel on 17/11/2017.
 */

import 'jquery-touchswipe'
import 'picturefill'
import 'jquery-mousewheel'
import 'lightgallery'
import 'lg-autoplay'
import 'lg-fullscreen'
import 'lg-pager'
import 'lg-thumbnail'
import 'lg-zoom'

$(document).ready(function () {
    $("#lightgallery").lightGallery({
        mode: 'lg-fade',
        preload: 2,
        showThumbByDefault: false,
        enableDrag: false,
        hideBarsDelay: 1000,
    });
});