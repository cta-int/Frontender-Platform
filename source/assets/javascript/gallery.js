; (function ($, window) {
    function Gallery(element, config) {
        this.element = element;
        this.$element = $(element);
        this.config = config;
        this.index = 0;
        this.length = config.children.length - 1;
        this.mediaStage = this.$element.find('[data-stage=media]');
        this.metaStage = this.$element.find('[data-stage=meta]');

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
        meta = false;

        if (content.description) {
            meta = '<p class="caption">' + content.description + '</p>';
        }

        if (content.credit) {
            if (!meta) {
                meta = '';
            }

            meta = meta + '<p class="credit">© ' + content.credit + '</p>';
        }

        return {
            media: '<img class="actor__media" src="' + content.metadata.url + '" width="800" height="500">',
            meta: meta
        };
    }

    Gallery.prototype.getVideoTemplate = function (content) {
        if (content.metadata.url.match(/youtube.com/g)) {
            return '<iframe class="actor__media" src="' + content.metadata.url.replace('watch?v=', 'embed/') + '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        } else if (content.metadata.url.match(/vimeo.com/g)) {
            return '<iframe class="actor__media" src="https://player.vimeo.com/video/' + content.metadata.url.replace('https://vimeo.com/', '') + '?title=0&byline=0&portrait=0" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>' + '<script src="https://player.vimeo.com/api/player.js"/>';
        }

        return '<a href="' + content.metadata.url + '"><img class="actor__media" src="' + content.metadata.previewUrl + '" width="800" height="500"></a>';
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
        // this.$element.css('visibility', 'visible');
        this.$element.addClass('gallery--active');
    }

    Gallery.prototype.activateSlide = function (slide) {
        this.index = this.config.children.index(slide);

        var $slide = $(slide),
            modalData = $slide.data('modal'),
            type = modalData && modalData.metadata && modalData.metadata.type,
            method = 'get' + (type[0].toUpperCase() + type.slice(1).toLowerCase()) + 'Template';

        // If no data is defined we will close the modal again.
        if (!modalData || !type || !method || !this[method] || !this.mediaStage) {
            return false;
        }

        var output = this[method](modalData);

        if (typeof output === 'string') {
            output = {
                media: output,
                meta: false
            };
        }

        // Get the type of the item.
        this.mediaStage.html(output.media);

        if (output.meta) {
            this.metaStage.html(output.meta);
            this.metaStage.show();
        } else {
            this.metaStage.hide();
        }

        return true;
    }

    Gallery.prototype.closeModal = function () {
        // this.$element.css('visibility', 'hidden');
        this.$element.removeClass('gallery--active');
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