; (function ($, window) {
    function Gallery(element, config) {
        this.element = element;
        this.$element = $(element);
        this.config = config;
        this.index = 0;
        this.length = config.children.length - 1;
        this.mediaStage = this.$element.find('[data-stage=media]');
        this.metaStage = this.$element.find('[data-stage=meta]');
        this.locales = this.config.locales ? JSON.parse(this.config.locales) : {};

        if (config.children.length === 1) {
            // remove the previous and next button.
            this.$element.find('[data-next]').parent().remove();
            this.$element.find('[data-previous]').parent().remove();
        } if (config.children.length === 2) {
            this.$element.find('[data-previous]').parent().remove();
        }

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
        return {
            media: '<img class="actor__media" src="' + content.metadata.url + '" width="800" height="500">',
            meta: this._createMeta({
                title: 'title',
                caption: 'description',
                credit: 'credit'
            }, content)
        };
    };

    Gallery.prototype.getFlickrTemplate = function (content) {
        return {
            media: '<img class="actor__media" src="' + content.metadata.url + '" width="800" height="500">',
            meta: this._createMeta({
                title: 'title',
                caption: 'description'
            }, content)
        };
    };

    Gallery.prototype.getVideoTemplate = function (content) {
        var media = {
            media: '<a href="' + content.metadata.url + '"><img class="actor__media" src="' + content.metadata.previewUrl + '" width="800" height="500"></a>',
            meta: this._createMeta({
                title: 'title',
                caption: 'description',
                credit: 'credit'
            }, content)
        };

        if (content.metadata.url.match(/youtube.com/g)) {
            media.media = '<iframe class="actor__media" src="' + content.metadata.url.replace('watch?v=', 'embed/') + '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        } else if (content.metadata.url.match(/vimeo.com/g)) {
            media.media = '<iframe class="actor__media" src="https://player.vimeo.com/video/' + content.metadata.url.replace('https://vimeo.com/', '') + '?title=0&byline=0&portrait=0" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>' + '<script src="https://player.vimeo.com/api/player.js"/>';
        }

        return media;
    };

    Gallery.prototype.getNextSlide = function (reset) {
        var currentIndex = this.index,
            slide = null;

        ++this.index;

        if (this.index > this.length) {
            this.index = 0;
        }

        slide = this.config.children[this.index];

        if (reset) {
            this.index = currentIndex;
        }

        return slide;
    }

    Gallery.prototype.getPreviousSlide = function (reset) {
        var currentIndex = this.index,
            slide = null;

        --this.index;

        if (this.index < 0) {
            // Get the last item.
            this.index = this.length;
        }

        slide = this.config.children[this.index];

        if (reset) {
            this.index = currentIndex;
        }

        return slide;
    };

    Gallery.prototype.showNext = function () {
        this.activateSlide(
            this.getNextSlide()
        );
    };

    Gallery.prototype.showPrevious = function () {
        this.activateSlide(
            this.getPreviousSlide()
        );
    };

    Gallery.prototype.openModal = function () {
        // this.$element.css('visibility', 'visible');
        this.$element.addClass('gallery--active');
    };

    Gallery.prototype.activateSlide = function (slide) {
        this.index = this.config.children.index(slide);

        var $slide = $(slide),
            modalData = $slide.data('modal'),
            type = this._getType(modalData),
            method = 'get' + (type[0].toUpperCase() + type.slice(1).toLowerCase()) + 'Template';

        // If no data is defined we will close the modal again.
        if (!modalData || !type || !method || !this[method] || !this.mediaStage) {
            return false;
        }

        var output = this[method](modalData),
            previousButton = this.$element.find('[data-previous]'),
            nextButton = this.$element.find('[data-next]'),
            previousData = $(this.getPreviousSlide(true)).data('modal'),
            nextData = $(this.getNextSlide(true)).data('modal');

        if (typeof output === 'string') {
            output = {
                media: output,
                meta: false
            };
        }

        // Get the type of the item.
        this.mediaStage.html(output.media);

        nextButton.find('.teleport__label').text(nextData.title);
        previousButton.find('.teleport__label').text(previousData.title);

        if (output.meta) {
            this.metaStage.html(output.meta);
            this.metaStage.show();
        } else {
            this.metaStage.hide();
        }

        return true;
    };

    Gallery.prototype.closeModal = function () {
        // this.$element.css('visibility', 'hidden');
        this.$element.removeClass('gallery--active');
    };

    /**
     * This method will create a string used in the meta box.
     */
    Gallery.prototype._createMeta = function (information, data) {
        var meta = false;

        for (var index in information) {
            if (information.hasOwnProperty(index)) {
                var metaValue = getDataValue(information[index]);

                if (!metaValue) {
                    continue;
                }

                if (index == 'credit') {
                    metaValue = '© ' + this._translate(metaValue);
                }

                meta = meta || '';
                meta += '<p class="' + index + '">' + this._translate(metaValue) + '</p>'
            }
        }

        function getDataValue(key) {
            var parts = key.split('.');

            return parts.reduce(function (carry, part) {
                if (!carry.hasOwnProperty(part) || !carry[part] || !carry) {
                    return false;
                }

                return carry[part];
            }, data);
        }

        return meta;
    };

    Gallery.prototype._getType = function(data) {
        if(!data) {
            return false;
        }

        if(data.type) {
            return data.type;
        }

        return data.metadata && data.metadata.type ? data.metadata.type : false;
    };

    Gallery.prototype._translate = function(item) {
        if(typeof item === 'string') {
            return item;
        }

        if(item.hasOwnProperty(this.config.currentLocale)) {
            return item[this.config.currentLocale];
        }

        // Map the item and translate again.
        for(let key in this.locales) {
            if(this.locales.hasOwnProperty(key)) {
                let shortLocale = this.locales[key];

                if(item.hasOwnProperty(shortLocale)) {
                    item[key] = item[shortLocale];
                }
            }
        }

        return this._translate(item);
    };

    $.fn.gallery = function (config) {
        $(this).each(function () {
            if (!$(this).data('gallery')) {
                $(this).data('gallery', 'set');

                new Gallery(this, config);
            }
        });
    }
})(jQuery, window);