// stable behaviour for slider
// smooth infinite auto-scroll leftwards
jQuery(document).ready(function($){
    var isElementorEditor = $('body').hasClass('elementor-editor-active') ||
        (window.elementorFrontend && elementorFrontend.isEditMode && elementorFrontend.isEditMode());

    function ensureTooltipsRoot(){
        var root = $('#icons-slider-tooltips-root');
        if(!root.length){
            root = $('<div id="icons-slider-tooltips-root"></div>').css({
                position: 'fixed',
                top: 0,
                left: 0,
                pointerEvents: 'none',
                zIndex: 9999
            }).appendTo('body');
        }
        return root;
    }

    function initContainer(container){
        var $container = $(container);
        if($container.data('iconsSliderInit')) return;
        $container.data('iconsSliderInit', true);

        // wrap slides in a direct child track when needed
        if(!$container.children('.icons-slider-track').length){
            $container.children('.icons-slide').wrapAll('<div class="icons-slider-track"></div>');
        }

        var $track = $container.children('.icons-slider-track');
        var $originalSlides = $track.children('.icons-slide').not('.is-clone');
        if(!$originalSlides.length) return;

        function getMinSlidesForAnimation(){
            return window.matchMedia('(max-width: 767px)').matches ? 3 : 5;
        }

        var $tooltipsRoot = ensureTooltipsRoot();

        // move original tooltips to body-root once and keep index mapping on slides
        $originalSlides.each(function(idx){
            var $slide = $(this);
            $slide.attr('data-tooltip-index', idx);
            var $tip = $slide.find('.slide-tooltip-wrapper').first();
            if($tip.length){
                $tip.detach().appendTo($tooltipsRoot);
            }
        });

        function getOriginalWidth(){
            var width = 0;
            $originalSlides.each(function(){
                width += $(this).outerWidth(true);
            });
            return width;
        }

        function cloneEnoughSlides(){
            $track.children('.icons-slide.is-clone').remove();
            var originalWidth = getOriginalWidth() || 1;
            var slideCount = $originalSlides.length;
            var minSlidesForAnimation = getMinSlidesForAnimation();

            // Clone only when we should animate for current viewport.
            var cloneFactor = slideCount >= minSlidesForAnimation ? 3 : 0;

            for(var i = 0; i < cloneFactor; i++){
                $originalSlides.each(function(){
                    $(this).clone().addClass('is-clone').attr('aria-hidden', 'true').appendTo($track);
                });
            }

            return originalWidth;
        }

        var originalWidth = getOriginalWidth();

        // in Elementor editor keep a clean centered preview: no clones, no animation
        if(isElementorEditor){
            $track.children('.icons-slide.is-clone').remove();
            $container.addClass('icons-slider-editor-mode');
            $track.css('transform', 'translateX(0)');
            return;
        }

        var slideCount = $originalSlides.length;
        var cloneFactor = slideCount >= getMinSlidesForAnimation() ? 3 : 0;
        cloneEnoughSlides();

        // Measure the actual rendered width of ONE set of slides from the DOM.
        // This includes flexbox gaps which outerWidth(true) misses.
        if (cloneFactor > 0) {
            originalWidth = $track[0].scrollWidth / (1 + cloneFactor);
        }

        var speed = 0.5;
        var pos = 0;
        // Animate threshold is viewport-aware: mobile 3+, desktop 5+.
        var running = slideCount >= getMinSlidesForAnimation();

        function updateModeClass(){
            var minSlidesForAnimation = getMinSlidesForAnimation();
            $container.toggleClass('icons-slider-static', slideCount < minSlidesForAnimation);
            $container.toggleClass('icons-slider-running', slideCount >= minSlidesForAnimation);
        }

        updateModeClass();

        function step(){
            if(!running) return;
            pos += speed;
            // Reset after one set for seamless infinite loop
            if(pos >= originalWidth){
                pos -= originalWidth;
            }
            $track.css('transform', 'translateX(' + (-pos) + 'px)');
        }

        var interval = setInterval(step, 16);
        $container.data('iconsSliderInterval', interval);

        $container.on('mouseenter.iconsSlider touchstart.iconsSlider', function(){
            running = false;
        });
        $container.on('mouseleave.iconsSlider touchend.iconsSlider', function(){
            running = slideCount >= getMinSlidesForAnimation();
        });
        $container.on('touchmove.iconsSlider', function(e){
            e.preventDefault();
        });

        $(window).on('resize.iconsSlider', function(){
            slideCount = $originalSlides.length;
            cloneFactor = slideCount >= getMinSlidesForAnimation() ? 3 : 0;
            cloneEnoughSlides();
            if (cloneFactor > 0) {
                originalWidth = $track[0].scrollWidth / (1 + cloneFactor);
            } else {
                originalWidth = getOriginalWidth();
            }
            // update running state based on slide count
            running = slideCount >= getMinSlidesForAnimation();
            updateModeClass();
            if(pos >= originalWidth){
                pos = pos % originalWidth;
            }
        });
    }

    $('.icons-slider-container').each(function(){
        initContainer(this);
    });

    // on slide hover, position tooltip and show
    $(document)
        .off('mouseenter.iconsSliderTooltip mouseleave.iconsSliderTooltip', '.icons-slider-container .icons-slide')
        .on('mouseenter.iconsSliderTooltip', '.icons-slider-container .icons-slide', function(){
            var $slide = $(this);
            var idx = parseInt($slide.attr('data-tooltip-index'), 10);
            if(isNaN(idx)) return;

            var $tooltip = $('#icons-slider-tooltips-root .slide-tooltip-wrapper').eq(idx);
            if(!$tooltip.length) return;

            var slideOffset = $slide.offset();
            $tooltip.css({
                left: (slideOffset.left + $slide.outerWidth() / 2) + 'px',
                top: (slideOffset.top - $tooltip.outerHeight() - 12) + 'px',
                transform: 'translateX(-50%)',
                display: 'block',
                pointerEvents: 'none'
            });
        })
        .on('mouseleave.iconsSliderTooltip', '.icons-slider-container .icons-slide', function(){
            var idx = parseInt($(this).attr('data-tooltip-index'), 10);
            if(isNaN(idx)) return;
            var $tooltip = $('#icons-slider-tooltips-root .slide-tooltip-wrapper').eq(idx);
            if($tooltip.length) $tooltip.css('display', 'none');
        });
});
