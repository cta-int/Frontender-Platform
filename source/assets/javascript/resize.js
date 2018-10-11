;(function($, window) {
    function Resize(element, config) {
        this.element = element;
        this.config = $.extend({
            breakpoint: null,
            onHit: function() {},
            onMiss: function() {}
        }, config);

        this.hit = false;

        // Check if breakpoint matches.
        if(!matchMedia(this.config.breakpoint).matches) {
            this.config.onMiss.call(this);
            this.hit = false;
        } else {
            this.hit = true;
        }

        $(this.element).on('resize', function() {
            if(matchMedia(this.config.breakpoint).matches && this.hit === false) {
                this.config.onHit.call(this);
                this.hit = true;
            } else if(!matchMedia(this.config.breakpoint).matches && this.hit === true) {
                this.config.onMiss.call(this);
                this.hit = false;
            }
        }.bind(this));
    }

    $.fn.resize = function(config) {
        $(this).each(function() {
            new Resize(this, config);
        });
    }
})(window.jQuery, window);