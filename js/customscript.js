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
  
      $(".togglehidden").click(function(){
              $("#hidden").toggle();
              });

});

$(document).ready(function() {
    $("td").on("click", ".showform", function() {
      var form = $(this).closest("form");
      $(this).toggleClass("active");
      $(this).next(".sub-form").slideToggle();
    return false;
  }); 

/* Check password match*/
    $("#pswd2").blur(function()
    {
        var pass2 = $(this).val();
        var pass1 = $("#pswd1").val();
        if(pass2!=pass1)
        {
            $("#pswresult").html('<span class="error"><i class="fa fa-exclamation-triangle icon checkko alert-danger"></i> <em>Las contraseÃ±as no coinciden</em>');
        }else {
            $("#pswresult").html('');
        }

     });

/* Check valid email */
    $("#usermail").blur(function()
    {
        var email = $(this).val();
        var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
        var checkresult = pattern.test(email);
        if (!checkresult) {
        $("#emailresult").html('<span class="error"><i class="fa fa-exclamation-triangle icon checkko alert-danger"></i> <em>Email no vÃ¡lido</em>');
        }else {
          $("#emailresult").html('');
        }

     });

/*Check username availability with ajax*/
    $("#username").blur(function()
    {
        /* minim 3 characters*/
        var name = $(this).val();
        console.log(name);
            $("#result").html('<i class="fa fa-spinner fa-pulse fa-2x fa-fw margin-bottom"></i>');

            /*$.post("username-check.php", $("#reg-form").serialize())
                .done(function(data){
                $("#result").html(data);
            });*/

            $.ajax({

                type : 'POST',
                url  : 'proc/check-username.php',
                data : $(this).serialize(),
                success : function(data)
                          {
                             $("#result").html(data);
                            console.log(data);
                          }
                });
                return false;

    });
    $('select#selmail').change(function(){
          $('input#usermail').val($(this).val());
          $('input#usermail').focus().blur();
    });

/*check errors in regitration form */
    $( "#adduser-form" ).submit(function( event ) {
        var count = $("#adduser-form .error:visible").length;
        console.log(count);
        if (count != 0 ) event.preventDefault();
    });


});


