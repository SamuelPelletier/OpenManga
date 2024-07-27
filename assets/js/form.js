$(document).ready(function () {

    // If the form is register
    if ($(location).attr('href').includes('register')) {
        $('.tab a').click();
    }

    var inputs = $('.form').find('input, textarea');
    inputs.each(function (index) {
        label = $(this).prev('label');
        if ($(this).val() !== null && $(this).val() !== '' && !label.hasClass('active')) {
            label.addClass('active');
        }
    });
});

$('.form').find('input, textarea').on('keyup blur focus', function (e) {

    var $this = $(this),
        label = $this.parent().find('label');

    if (e.type === 'keyup') {
        if ($this.val() === '') {
            label.removeClass('active highlight');
        } else {
            label.addClass('active highlight');
        }
    } else if (e.type === 'blur') {
        if ($this.val() === '') {
            label.removeClass('active highlight');
        } else {
            label.removeClass('highlight');
        }
    } else if (e.type === 'focus') {

        if ($this.val() === '') {
            label.removeClass('highlight');
        } else if ($this.val() !== '') {
            label.addClass('highlight');
        }

        $(this).parent('div').find('.form-error').remove();
    }

});

$('.tab a').on('click', function (e) {

    e.preventDefault();

    $(this).parent().addClass('active');
    $(this).parent().siblings().removeClass('active');

    target = $(this).attr('href');

    $('.tab-content > div').not(target).hide();

    $(target).fadeIn(600);

});

$('.patreon-button').on('click', function () {
    var url = $(this).data('url');
    var button = $(this);
    button.replaceWith("<div class=\"small-loader loader--style2\" title=\"1\">\n" +
        "  <svg version=\"1.1\" id=\"loader-1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" x=\"0px\" y=\"0px\"\n" +
        "     width=\"40px\" height=\"40px\" viewBox=\"0 0 50 50\" style=\"enable-background:new 0 0 50 50;\" xml:space=\"preserve\">\n" +
        "  <path fill=\"#000\" d=\"M25.251,6.461c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615V6.461z\">\n" +
        "    <animateTransform attributeType=\"xml\"\n" +
        "      attributeName=\"transform\"\n" +
        "      type=\"rotate\"\n" +
        "      from=\"0 25 25\"\n" +
        "      to=\"360 25 25\"\n" +
        "      dur=\"0.6s\"\n" +
        "      repeatCount=\"indefinite\"/>\n" +
        "    </path>\n" +
        "  </svg>\n" +
        "</div>");
    $.ajax({
        url: url,
        type: "POST",
        dataType: "json",
        async: true,
        success: function (data) {
            $(".small-loader").replaceWith("<div class='animate-box'><svg viewBox=\"0 0 100 100\" class=\"animate-loader\">\n" +
                "  <filter id=\"dropshadow\" height=\"100%\">\n" +
                "    <feGaussianBlur in=\"SourceAlpha\" stdDeviation=\"3\" result=\"blur\"/>\n" +
                "    <feFlood flood-color=\"rgba(76, 175, 80, 1)\" flood-opacity=\"0.5\" result=\"color\"/>\n" +
                "    <feComposite in=\"color\" in2=\"blur\" operator=\"in\" result=\"blur\"/>\n" +
                "    <feMerge> \n" +
                "      <feMergeNode/>\n" +
                "      <feMergeNode in=\"SourceGraphic\"/>\n" +
                "    </feMerge>\n" +
                "  </filter>\n" +
                "  \n" +
                "  <circle cx=\"50\" cy=\"50\" r=\"46.5\" fill=\"none\" stroke=\"rgba(76, 175, 80, 0.5)\" stroke-width=\"5\"/>\n" +
                "  \n" +
                "  <path d=\"M67,93 A46.5,46.5 0,1,0 7,32 L43,67 L88,19\" fill=\"none\" stroke=\"rgba(76, 175, 80, 1)\" stroke-width=\"5\" stroke-linecap=\"round\" stroke-dasharray=\"80 1000\" stroke-dashoffset=\"-220\"  style=\"filter:url(#dropshadow)\"/>\n" +
                "</svg></div>");
            document.querySelector('svg').classList.remove('animate');
            setTimeout(function () {
                document.querySelector('svg').classList.add('animate');
            }, 10);
        }
    });
});
