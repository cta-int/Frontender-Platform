;(function($, window) {
    function GMaps(element, config) {
        this.element = element;
        this.config = $.extend({
            location: {
                longitude: 5.3385,
                latitude: 51.7183
            },
            marker: {
                icon: null
            },
            zoom: 12
        }, config);

        this.map = new google.maps.Map(this.element, {
            center: new google.maps.LatLng(this.config.location.latitude, this.config.location.longitude),
            zoom: this.config.zoom,
            styles: this.config.styles || {}
        });

        this.drawMarker();
    }

    GMaps.prototype.drawMarker = function() {
        new google.maps.Marker({
            position: new google.maps.LatLng(this.config.location.latitude, this.config.location.longitude),
            map: this.map,
            icon: this.config.marker.icon
        });
    };

    $.fn.gmaps = function(config) {
        $(this).each(function() {
            if(!$(this).data('gmaps')) {
                $(this).data('gmaps', 'set');

                new GMaps(this, config);
            }
        })
    };
})(window.jQuery, window);