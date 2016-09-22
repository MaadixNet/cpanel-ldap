$(document).ready(function() {

    $('.mobileSlider').flexslider({
        animation: "slide",
        slideshowSpeed: 3000,
        controlNav: false,
        directionNav: true,
        prevText: "&#171;",
        nextText: "&#187;"
    });
    $('.flexslider').flexslider({
        animation: "slide",
        directionNav: false
    });
        
    $('a[href*=#]:not([href=#])').click(function() {
        if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') || location.hostname == this.hostname) {
            var target = $(this.hash);
            target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
            if ($(window).width() < 768) {
                if (target.length) {
                    $('html,body').animate({
                        scrollTop: target.offset().top - $('.navbar-header').outerHeight(true) + 1
                    }, 1000);
                    return false;
                }
            }
            else {
                if (target.length) {
                    $('html,body').animate({
                        scrollTop: target.offset().top - $('.nav').outerHeight(true) + 1
                    }, 1000);
                    return false;
                }
            }

        }
    });
    
    $('#toTop').click(function() {
        $('html,body').animate({
            scrollTop: 0
        }, 1000);
    });
    
    var timer;
    $(window).bind('scroll',function () {
        clearTimeout(timer);
        timer = setTimeout( refresh , 50 );
    });
    var refresh = function () {
        if ($(window).scrollTop()>100) {
            $(".tagline").fadeTo( "slow", 0 );
        }
        else {
            $(".tagline").fadeTo( "slow", 1 );
        }
    };
        
});

$(document).ready(function() {
	var li=$("h4 a.active").closest("li");
      li.find(".sub-menu").slideToggle();
  	$("#menu-main-menu-m").on("click", ".arrow", function() {  
      var li = $(this).closest("li");
      li.toggleClass("active");
      li.find(".sub-menu").slideToggle();
    return false;
  });

});
$(document).ready(function(){
	    $(".togglevisibility").click(function(){
			        $("#change").toggle();
					    });
});

$(document).ready(function() {
    $("td").on("click", ".showform", function() {
      var form = $(this).closest("form");
      $(this).toggleClass("active");
      $(this).next(".sub-form").slideToggle();
    return false;
  }); 

});

