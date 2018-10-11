(function($, window) {
    /**
     * Usage:
     *
     * $('.image_parent').srcset('> .image_tag');
     */
    $.fn.srcset = function (child) {
        $(this).each(function () {
            if(!$(this).data('srcset')) {
                $(this).data('srcset', 'set');

                var img = $(this).find(child);

                if(img.length) {
                    var source = (img.get(0).currentSrc || img.get(0).src);

                    if(source) {
                        $(this).css('background-image', 'url(' + source + ')');
                    }
                }
            }
        });
    };
})(window.jQuery, window);