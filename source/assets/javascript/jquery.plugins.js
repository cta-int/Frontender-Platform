// ---------------------------------
// ------------ Toggle -------------
// ---------------------------------
// Generic class to toggle between is-active and is-inactive class
// ------------------------

;(function ($, window, document, undefined) {

    var pluginName = 'toggle';

    function Plugin (element, options) {
        this.element = element;
        this._name = pluginName;
        this._defaults = $.fn.toggle.defaults;
        this.options = $.extend( {}, this._defaults, options );

        this.init();
    }

    $.extend(Plugin.prototype, {

        // Initialization logic
        init: function () {

        	// Set the state trigger from the element's data-toggle attribute
        	// If it's empty, it means that the element triggers the state on itself
        	this.target = $( $(this.element).data("toggle") );

        	// set the initial target state
        	this.state = this.target.hasClass("is-active") ? "is-active" : "is-inactive";

            this.target.addClass( this.state );


            this.buildCache();
            this.bindEvents();
        },

        // Remove plugin instance completely
        destroy: function() {
            this.unbindEvents();
            this.$element.removeData();
        },

        // Cache DOM nodes for performance
        buildCache: function () {
            this.$element = $(this.element);
        },

        // Bind events that trigger methods
        bindEvents: function() {
            var plugin = this;

            plugin.$element.on('click'+'.'+plugin._name, function(e) {
            	e.preventDefault();
                plugin.changeState.call(plugin);
            });
        },

        // Unbind events that trigger methods
        unbindEvents: function() {
            this.$element.off('.'+this._name);
        },

        // Create custom methods
        changeState: function() {

            if( this.target.hasClass("tab") ) {
                if( this.target.hasClass("is-active") ) {
                    return;
                }

                this.target.parents(".tab-grp").find(".tab, .panel").removeClass("is-inactive is-active").addClass("is-inactive");
            }

            this.target.toggleClass("is-active").toggleClass("is-inactive");

            this.toggleState();

        },

        toggleState: function() {

            if( this.state == "is-active") {
                this.state = "is-inactive";
            }
            else {
                this.state = "is-active";
            }
        },

        callback: function() {
            // Cache onComplete option
            var onComplete = this.options.onComplete;

            if ( typeof onComplete === 'function' ) {
                onComplete.call(this.element);
            }
        }

    });

    $.fn.toggle = function (options) {
        this.each(function() {
            if ( !$.data( this, "plugin_" + pluginName ) ) {
                $.data( this, "plugin_" + pluginName, new Plugin( this, options ) );
            }
        });
        return this;
    };

    $.fn.toggle.defaults = {
        property: 'value',
        onComplete: null
    };

})( jQuery, window, document );
