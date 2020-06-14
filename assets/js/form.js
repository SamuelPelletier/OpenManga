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