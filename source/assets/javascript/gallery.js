; (function ($, window) {
    function Gallery(element, config) {
        this.element = element;
        this.$element = $(element);
        this.config = config;
        this.index = 0;
        this.length = config.children.length - 1;

        this.closeModal();
        this.bindEvents();
    }

    Gallery.prototype.bindEvents = function () {
        var self = this;

        this.$element.find('[data-next]').on('click', function (event) {
            event.preventDefault();

            self.showNext();
        });
        this.$element.find('[data-previous]').on('click', function (event) {
            event.preventDefault();

            self.showPrevious();
        });
        this.$element.find('[data-close]').on('click', function (event) {
            event.preventDefault();

            self.closeModal();
        });

        this.config.children.on('click', function (event) {
            event.preventDefault();

            if (self.activateSlide(this)) {
                self.openModal();
            }
        });
    };

    Gallery.prototype.getImageTemplate = function (content) {
        return '<img src="' + content.metadata.url + '" width="800" height="500">';
    }

    Gallery.prototype.getVideoTemplate = function (content) {
        if (content.metadata.url.match(/youtube.com/g)) {
            return '<iframe width="800" height="500" src="' + content.metadata.url.replace('watch?v=', 'embed/') + '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        } else if (content.metadata.url.match(/vimeo.com/g)) {
            return '<iframe width="800" height="500" src="https://player.vimeo.com/video/' + content.metadata.url.replace('https://vimeo.com/', '') + '?title=0&byline=0&portrait=0" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>' + '<script src="https://player.vimeo.com/api/player.js"/>';
        }

        // If not wrap the cover in an anchor tag
        return '<a href="' + content.metadata.url + '"><img src="' + content.metadata.previewUrl + '" width="800" height="500"></a>';
    }

    Gallery.prototype.showNext = function () {
        ++this.index;

        if (this.index > this.length) {
            this.index = 0;
        }

        this.activateSlide(
            this.config.children[this.index]
        );
    }

    Gallery.prototype.showPrevious = function () {
        --this.index;

        if (this.index < 0) {
            // Get the last item.
            this.index = this.length;
        }

        this.activateSlide(
            this.config.children[this.index]
        );
    }

    Gallery.prototype.openModal = function () {
        this.$element.css('visibility', 'visible');
    }

    Gallery.prototype.activateSlide = function (slide) {
        this.index = this.config.children.index(slide);

        var $slide = $(slide),
            modalData = $slide.data('modal'),
            type = modalData && modalData.metadata && modalData.metadata.type,
            method = 'get' + (type[0].toUpperCase() + type.slice(1).toLowerCase()) + 'Template';

        // If no data is defined we will close the modal again.
        if (!modalData || !type || !method || !this[method] || !this.config.stage) {
            return false;
        }

        // Get the type of the item.
        this.config.stage.html(
            this[method](modalData)
        );

        return true;
    }

    Gallery.prototype.closeModal = function () {
        this.$element.css('visibility', 'hidden');
    }

    $.fn.gallery = function (config) {
        $(this).each(function () {
            if (!$(this).data('gallery')) {
                $(this).data('gallery', 'set');

                new Gallery(this, config);
            }
        });
    }
})(jQuery, window);