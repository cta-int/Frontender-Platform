/**
 * Config variables and functions.
 *
 * wrapper: The wrapper class.
 * demoted: The class that demoted items have (no dot notation).
 * init: The function called after initialization.
 * addAllElements: The function called when the wrapper completely exceeds the element, possibly triggered on orientation change.
 * addElement: When the wrapper resizes to accommodate another element.
 * removeElement: When the wrapper size becomes smaller then the element size.
 */

;(function($, window) {
    function ResponsiveMenu(element, config) {
        this.config = $.extend({
            wrapper: '.wrapper',
            template: 'demoted-menu',
            trigger: 'demoted-menu',
            text: {
                noItems: 'Menu',
                items: 'More',
            },
            init: null,
            addAllElements: null,
            addElement: null,
            removeElement: null
        }, config);

        // TODO: Check which items are needed and which not.
        this.wrapper = $(element).closest(this.config.wrapper).get(0);
        this.element = element;

        this.demotedList = null;
        this.triggerElement = null;

        this._widthRegistry = {};
        this._hiddenWidthRegistry = [];

        this.reorderElements()
            .bindEvents()
            .buildWidthRegister()
            .checkWidth(); // Initial width check for new navigation to the page.

        // Execute the init handler.
        this._execute('init', this);
    }

    ResponsiveMenu.prototype.buildWidthRegister = function() {
        $(this.element).find('> li').each(function(key, element) {
            this._widthRegistry[key] = $(element).outerWidth(true);
        }.bind(this));

        return this;
    };

    ResponsiveMenu.prototype.reorderElements = function() {
        $(this.element).find('> li').sort(function(a, b) {
            var aprio = $(a).data('priority'),
                bprio = $(b).data('priority');

            if(aprio < bprio) {
                return -1;
            } else if(aprio == bprio) {
                return 0;
            } else {
                return 1;
            }
        }).appendTo(this.element);

        return this;
    };

    ResponsiveMenu.prototype.bindEvents = function() {
        var self = this;

        $(window).on('resize', this.checkWidth.bind(this));
        // $(this.element).on('finishResize', this.checkButton.bind(this));
        $(this.element).on('calcRegistry', function() {
            // Recheck everything.
            $(this.demotedList).find('> li').each(function(key, element) {
                element = $(element).detach();
                this.triggerElement.before(element);
            }.bind(this));

            this._hiddenWidthRegistry = [];
            if(this.triggerElement !== null) {
                this.triggerElement.remove();
                this.triggerElement = null;
                this.demotedList = null;
            }

            this.buildWidthRegister.call(this);
            this.checkWidth.call(this);
        }.bind(this));
        $(this.element).on('click', this.config.trigger, function(event) {
            if($(event.target).attr('href') !== self.config.trigger) {
                return true;
            }

            event.preventDefault();
            $(this).toggleClass('is-inactive').toggleClass('is-active');

            if($(self.wrapper).css('overflow') === 'hidden') {
                $(self.wrapper).css('overflow', 'visible');
                // TODO: This is still to be fixed in css!
                self.demotedList.css('z-index', '20');
            } else {
                $(self.wrapper).css('overflow', 'hidden');
            }
        });

        return this;
    };

    ResponsiveMenu.prototype.checkWidth = function() {
        var wrapperWidth = $(this.config.wrapper).outerWidth(true),
            currentWidth = this._getWidth(),
            fullWidth = this._getWidth(true);

        if(wrapperWidth > fullWidth) {
            $(this.demotedList).find('> li').each(function(key, element) {
                element = $(element).detach();
                this.triggerElement.before(element);
            }.bind(this));

            if(this.triggerElement !== null) {
                this.triggerElement.remove();
                this.triggerElement = null;
                this.demotedList = null;
            }

            $(this.element).trigger('finishResize');
            return;
        }

        if(currentWidth < wrapperWidth) {
            while(currentWidth < wrapperWidth) {
                var result = this.addMenuItem();
                currentWidth = this._getWidth();

                if(!result) {
                    break;
                }
            }
        } else {
            this._addTrigger();

            while(currentWidth > wrapperWidth) {
                var result = this.removeMenuItem();
                currentWidth = this._getWidth();

                if(!result) {
                    break;
                }
            }
        }

        $(this.element).trigger('finishResize');

        return this;
    };

    ResponsiveMenu.prototype._addTrigger = function() {
        // Dynamically add the trigger.
        if(this.demotedList === null) {
            var template = $('template[data-template=' + this.config.template + ']').html().replace(/\n/, '').replace('    ', '');

            this.triggerElement = $(template).appendTo(this.element);
            this.demotedList = this.triggerElement.find('ul');
        }
    };

    ResponsiveMenu.prototype.checkButton = function() {
        if(this._isCompletelyHidden()) {
            $(this.config.trigger).text(this.config.text.noItems);
            return;
        } else {
            $(this.config.trigger).text(this.config.text.items);
        }

        return this;
    };

    ResponsiveMenu.prototype._getItem = function(type) {
        var elements = [];

        if(type) {
            elements = $(this.element).find('> li');
        } else {
            elements = $(this.demotedList).find('> li');
        }

        var element = type ? elements[elements.length - 2] : elements[0];

        return $(element);
    };

    /**
     * This method will demote a single menu item,
     * the demoted menu item will be moved to the demotedList.
     * @return {*}
     */
    ResponsiveMenu.prototype.removeMenuItem = function() {
        var element = this._getItem(true),
            index = $(this.element).find('> li').index(element);

        if(!element || $(this.triggerElement).is(element)) {
            return false;
        }

        this._execute('removeElement', this, element);
        this._hiddenWidthRegistry.push(this._widthRegistry[index]);

        element = element.detach();
        if(this.demotedList.find('> li').length === 0) {
            element.appendTo(this.demotedList);
        } else {
            this.demotedList.find('> li').first().before($(element));
        }

        return this;
    };

    /**
     * This method adds a demoted item to the menu.
     * This is only done when there are any demoted items.
     *
     * @return {ResponsiveMenu}
     */
    ResponsiveMenu.prototype.addMenuItem = function() {
        var element = this._getItem();

        if(!element || $(this.config.wrapper).outerWidth(true) < (this._getWidth() + this._hiddenWidthRegistry[this._hiddenWidthRegistry.length - 1])) {
            return false;
        }

        // TODO: Only add a menu item if it fits.
        this._execute('addElement', this, element);

        this._hiddenWidthRegistry.pop();
        element = element.detach();
        this.triggerElement.before(element);

        return this;
    };

    /**
     * This method will call another method located in the config.
     * This method accepts three parameters that NOT defined in the function declaration.
     *
     * The first parameter is the method name as a string.
     * The second parameter is the context of the function.
     * All following parameters are passed through to the function.
     *
     * @private
     */
    ResponsiveMenu.prototype._execute = function() {
        var args = [].slice.call(arguments),
            method = args.shift(),
            context = args.shift();

        if(!method) {
            throw new Error('No method given! (first parameter)');
        }

        if(!context) {
            throw new Error('No context given! (second parameter)')
        }

        if(this.config[method] && typeof this.config[method] === 'function') {
            this.config[method].apply(context, args);
        }
    };

    /**
     * This method gets the width of all the elements in the wrapper.
     * This is to hold other parts into account and make sure they don't conflict with the functionality of the
     * responsive menu.
     *
     * @param {Boolean} total Get the total width or not.
     * @return {number}
     * @private
     */
    ResponsiveMenu.prototype._getElementsWidth = function(total) {
        var self = this,
            width = 0;

        $(this.wrapper).children().each(function() {
            if(this === self.element) {
                width += self._getWidth(total);
            } else {
                width += $(this).outerWidth(true);
            }
        });

        return width;
    };

    /**
     * This method calculates the width of the current element.
     *
     * @param {Boolean} total The complete total if all the elements combined.
     * @return {number} The complete width in pixels.
     * @private
     */
    ResponsiveMenu.prototype._getWidth = function(total) {
        var width = 0;

        for(var index in this._widthRegistry) {
            if(this._widthRegistry.hasOwnProperty(index)) {
                width += this._widthRegistry[index];
            }
        }

        if(total !== true) {
            // This is not a single issue.
            for(var i = 0; i < this._hiddenWidthRegistry.length; i++) {
                width -= this._hiddenWidthRegistry[i];
            }
        }

        if(this.triggerElement !== null) {
            width += this.triggerElement.outerWidth(true);
        }

        return width;
    };

    /**
     * This method checks if there are hidden elements.
     * All the hidden elements are moved to the demotedList, if there are more than 0 elements in here,
     * it means that there are indeed hidden elements
     *
     * @return {boolean}
     * @private
     */
    ResponsiveMenu.prototype._hasHiddenItems = function() {
        return $(this.demotedList).find('> li').length > 0;
    };

    /**
     * This method check if all the original items are hidden.
     * The trigger should be the only item left.
     *
     * @return {boolean}
     * @private
     */
    ResponsiveMenu.prototype._isCompletelyHidden = function() {
        return $(this.element).find('> li').length === 1;
    };

    $.fn.responsiveMenu = function(config) {
        $(this).each(function() {
            if(!$(this).data('responsiveMenu')) {
                $(this).data('responsiveMenu', 'set');

                new ResponsiveMenu(this, config);
            }
        });
    }
})(window.jQuery, window);