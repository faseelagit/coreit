(function ($) {

    var PremiumNavMenuHandler = function ($scope, $) {
        var $menuContainer = $scope.find('.premium-mobile-menu'),
            $menuToggler = $scope.find('.premium-hamburger-toggle'),
            $hamMenuCloser = $scope.find('.premium-mobile-menu-close'),
            settings = $scope.find('.premium-nav-widget-container').data('settings');

        if ('slide' === settings.mobileLayout || 'slide' === settings.mainLayout) {
            $scope.addClass('premium-ver-hamburger-menu');
        }

        checkBreakPoint(settings);

        $hamMenuCloser.on('click', function () {
            $scope.find('.premium-mobile-menu-outer-container, .premium-nav-slide-overlay').removeClass('premium-vertical-toggle-open');
        });

        $menuToggler.on('click', function () {
            if ('slide' === settings.mobileLayout || 'slide' === settings.mainLayout) {
                $scope.find('.premium-mobile-menu-outer-container, .premium-nav-slide-overlay').addClass('premium-vertical-toggle-open');
            } else {
                $menuContainer.toggleClass('premium-active-menu');
            }

            $menuToggler.toggleClass('premium-toggle-opened');
        });

        $menuContainer.find('.premium-nav-menu-item.menu-item-has-children, .premium-mega-nav-item').on('click', function (e) {

            e.stopPropagation();
            e.preventDefault();

            if ($(this).hasClass('premium-active-menu')) {

                $(this).removeClass('premium-active-menu');

            } else {
                $menuContainer.find('.premium-active-menu').toggleClass('premium-active-menu');
                $(this).toggleClass('premium-active-menu');
            }

            // make sure the parent node is always open whenever the child node is opened.
            $(this).parents('.premium-nav-menu-item.menu-item-has-children').toggleClass('premium-active-menu');
        });

        // $(window).on('resize', function () {
        //     $menuToggler.removeClass('premium-toggle-opened');
        //     checkBreakPoint(settings);
        // });

        $(document).on('click', '.premium-nav-slide-overlay', function () {
            $scope.find('.premium-mobile-menu-outer-container, .premium-nav-slide-overlay').removeClass('premium-vertical-toggle-open');
        });

        function checkBreakPoint(settings) {
            if (settings.breakpoint >= $(window).width()) {

                $scope.addClass('premium-hamburger-menu');
                $scope.find('.premium-active-menu').removeClass('premium-active-menu');

                stretchDropdown( $scope.find('.premium-stretch-dropdown .premium-mobile-menu-container') );

            } else {
                $scope.removeClass('premium-hamburger-menu');
                $scope.find('.premium-vertical-toggle-open').removeClass('premium-vertical-toggle-open');
                $scope.find('.premium-nav-default').removeClass('premium-nav-default');
            }
        }

        function stretchDropdown( $menu ) {

            var $sectionContainer = $($scope).closest('.elementor-top-section'),
                width = $($sectionContainer).width(),
                top = $scope.find('.premium-nav-widget-container').outerHeight(),
                left = width - $scope.find('.premium-stretch-dropdown').width() - 10;

            $($menu).css({
                width: width + 'px',
                left: '-' + left + 'px',
                top: top + 'px',
            });
        }
    };

    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/premium-nav-menu.default', PremiumNavMenuHandler);
    });

})(jQuery);