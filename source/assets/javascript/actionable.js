;(function($, window) {
    function Actionable(element) {
        this.element = element;

        this.bindEvents();
    }

    Actionable.prototype.bindEvents = function() {
        var self = this;

        $(this.element).on('click', '[data-action]', function(event) {
            var action = $(this).data('action');
            if(typeof self[action] === 'function') {
                event.preventDefault();

                self[action]($(this));
            }
        });
    };

    Actionable.prototype.ajax = function(element, callback) {
        if(element.toString() !== '[object Object]') {
            element = {
                method: element.data('action'),
                url: element.data('url'),
                states: element.data('states') || element.attr('href'),
                target: element.data('target'),
                append: false
            }
        }

        if(typeof element.states === 'object') {
            element.states = Object.keys(element.states).map(function(item) {
                var value = element.states[item];
                if(typeof value === 'object' || Array.isArray(value)) {
                    value = JSON.stringify(value);
                }

                return [item, value].join('=');
            }).join('&');
        }

        var modifier = (element.url.indexOf('?') > -1) ? '&' : '?';
        $.ajax({
            url: element.url + modifier + element.states,
            success: function(htmlContent) {
                if(!element.append) {
                    $(element.target).html(htmlContent);
                } else {
                    $(element.target).append(htmlContent);
                }

                if(callback) {
                    callback();
                }
            }
        })
    };

    // Intentional set on the window, prototype functions may be used later.
    window.Actionable = Actionable;

    /**
     * This is a wrapper for the custom states in the url,
     * and a wrapper for the ajaxified content loads.
     *
     * Example:
     * $('body').actionable()
     */
    $.fn.actionable = function() {
        $(this).each(function() {
            if(!$(this).data('actionable')) {
                $(this).data('actionable', 'set');

                new Actionable(this);
            }
        })
    }
})(window.jQuery, window);