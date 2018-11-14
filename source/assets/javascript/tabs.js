(function($, window) {
    function Tabs(element, config) {
        this.element = element;

        var tabConfig = $(element).data("tab-config") || false;
        this.config = $.extend(
            {
                children: tabConfig.children || ".panel", // The children to select.
                attribute: tabConfig.attribute || "href", // Give the attribute on which to trigger.
                allowMultiple: tabConfig.allowMultiple || false, // When set to true, it only opens the targeted item.,
                openFirst: tabConfig.openFirst || false, // If set to true, it will open the first item in the list.
                scrollTo: tabConfig.scrollTo || false // If set to true, the window will scroll to the activated tab
            },
            config
        );

        this.bindEvents();

        if (this.config.openFirst) {
            $(this.element)
                .find(this.config.children)
                .removeClass("is-active")
                .addClass("is-inactive");
            var $child = $(this.element)
                .find(this.config.children)
                .first();

            $child.addClass("is-active").removeClass("is-inactive");

            if ($child.attr("id")) {
                $(
                    "[" +
                        this.config.attribute +
                        "='#" +
                        $child.attr("id") +
                        "']"
                ).addClass("is-current");
            }
        }
    }

    Tabs.prototype.bindEvents = function() {
        var self = this;

        $(this.element)
            .find(this.config.children)
            .each(function() {
                if ($(this).attr("id")) {
                    var target = $(
                        "[" +
                            self.config.attribute +
                            "='#" +
                            $(this).attr("id") +
                            "']"
                    );

                    target.on(
                        "click",
                        function(event) {
                            event.preventDefault();

                            $(target)
                                .parent()
                                .siblings()
                                .find("a")
                                .removeClass("is-current");
                            $(target).addClass("is-current");

                            if (!self.config.allowMultiple) {
                                $(self.element)
                                    .find(self.config.children)
                                    .removeClass("is-active")
                                    .addClass("is-inactive");
                                this.addClass("is-active").removeClass(
                                    "is-inactive"
                                );

                                if (self.config.scrollTo) {
                                    $(window).scrollTop(this.offset().top);
                                }
                            } else {
                                var activeChildren = $(self.element).find(
                                    self.config.children + ".is-active"
                                );

                                if (activeChildren.length === 1) {
                                    if ($(this).is(activeChildren.get(0))) {
                                        return false;
                                    }
                                }

                                if (!$(this).hasClass("is-active")) {
                                    $(this)
                                        .toggleClass("is-active")
                                        .toggleClass("is-inactive");
                                }

                                if (self.config.scrollTo) {
                                    $(window).scrollTop(this.offset().top);
                                }
                            }
                        }.bind($(this))
                    );
                }
            });
    };

    function Filters(element, config) {
        this.element = element;
        this.config = $.extend(
            {
                children: ".panel", // The children to select.
                attribute: "href" // Give the attribute on which to trigger.
            },
            config
        );

        var self = this;

        $(this.element)
            .find(this.config.children)
            .each(function() {
                if ($(this).attr("id")) {
                    $(
                        "[" +
                            self.config.attribute +
                            "='#" +
                            $(this).attr("id") +
                            "']"
                    ).on(
                        "click",
                        function(event) {
                            this.siblings()
                                .removeClass("is-active")
                                .addClass("is-inactive");
                        }.bind($(this))
                    );
                }
            });
    }

    $.fn.tabs = function(config) {
        $(this).each(function(key, element) {
            if (!$(element).data("tabs")) {
                $(element).data("tabs", "set");

                new Tabs(element, config);
            }
        });
    };

    $.fn.filters = function(config) {
        $(this).each(function(key, element) {
            if (!$(element).data("filters")) {
                $(element).data("filters", "set");

                new Filters(element, config);
            }
        });
    };
})(window.jQuery, window);
