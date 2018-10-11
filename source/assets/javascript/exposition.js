jQuery(function($) {
    function isScrolledIntoView(elem) {
        var docViewTop = $(window).scrollTop();
        var docViewBottom = docViewTop + $(window).height();

        var elemTop = $(elem).offset().top;
        var elemBottom = elemTop + $(elem).height();

        return ((elemTop <= docViewBottom));
    }

    $(window).scroll(function () {
        $('.exposition').each(function () {
            if (isScrolledIntoView(this) === true && !$(this).hasClass('exposition-visible')) {
                $(this).addClass('exposition-visible')
            }
        });
        $('.theme-slider').each(function () {
            if (isScrolledIntoView(this) === true && !$(this).hasClass('is-visible')) {
                $(this).addClass('is-visible')
            }
        });
    });

    $(window).trigger('scroll');

    /**
     * Custom javascript to correct the numbers.
     * By default they show only 1, this script will make them numbered.
     */
    var expositions = $('.exposition').toArray();
    for(var index in expositions) {
        if(expositions.hasOwnProperty(index)) {
            $(expositions[index]).find('.section-number').text(parseInt(index) + 1);
        }
    }
});
