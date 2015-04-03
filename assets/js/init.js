$(document).ready(function() {
    // side nav
    $('.js-menu-trigger,.js-menu-screen').on('click touchstart',function (e) {
        $('.js-menu,.js-menu-screen').toggleClass('is-visible');
        e.preventDefault();
    });


    // modal for sign up , log in
    $(".modal-fade-screen").on("click", function() {
        $(".modal-state:checked").prop("checked", false).change();
    });

    $(".modal-inner").on("click", function(e) {
        e.stopPropagation();
    });


    // accordion tabs for photo gallery
    var accordTabs = $('.accordion-tabs-minimal');

    accordTabs.each(function(index) {
        $(this).children('li').first().children('a').addClass('is-active').next().addClass('is-open').show();
    });

    accordTabs.on('click', 'li > a', function(event) {
        if (!$(this).hasClass('is-active')) {
            event.preventDefault();
            var accordionTabs = $(this).closest('.accordion-tabs-minimal');
            accordionTabs.find('.is-open').removeClass('is-open').hide();

            $(this).next().toggleClass('is-open').toggle();
            accordionTabs.find('.is-active').removeClass('is-active');
            $(this).addClass('is-active');
        } else {
            event.preventDefault();
        }
    });



    // vertical accordian tabs for features
    $(".js-vertical-tab-content").hide();
    $(".js-vertical-tab-content:first").show();

    /* if in tab mode */
    $(".js-vertical-tab").click(function(event) {
        event.preventDefault();

        $(".js-vertical-tab-content").hide();
        var activeTab = $(this).attr("rel");
        $("#"+activeTab).show();

        $(".js-vertical-tab").removeClass("is-active");
        $(this).addClass("is-active");

        $(".js-vertical-tab-accordion-heading").removeClass("is-active");
        $(".js-vertical-tab-accordion-heading[rel^='"+activeTab+"']").addClass("is-active");
    });

    /* if in accordion mode */
    $(".js-vertical-tab-accordion-heading").click(function(event) {
        event.preventDefault();

        $(".js-vertical-tab-content").hide();
        var accordion_activeTab = $(this).attr("rel");
        $("#"+accordion_activeTab).show();

        $(".js-vertical-tab-accordion-heading").removeClass("is-active");
        $(this).addClass("is-active");

        $(".js-vertical-tab").removeClass("is-active");
        $(".js-vertical-tab[rel^='"+accordion_activeTab+"']").addClass("is-active");
    });

});