/*$(document).ready(function() {

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
*/

$(document).ready(function() {

     $(window).load(function() {
          $('#loading').hide();
      });



     var pgurl = window.location.href.substr(window.location.href
.lastIndexOf("/")+1);
      
     $("#sidebar-menu a").each(function(){
          var itemurl= $(this).attr("href").substr($(this).attr("href").lastIndexOf("/")+1);
          if( itemurl  == pgurl && itemurl != '' ){
            $(this).parent('li').addClass("active");
            $("#sidebar-menu ul:has(li.active)").addClass('collapse in');
            $("#sidebar-menu li:has(ul.in)").addClass('active');
          } else {
            if ( itemurl  == pgurl && itemurl == ''  || pgurl == 'index.php' || pgurl=='basic-info' || pgurl=='loading' || pgurl=='accounts' ){
              $("#sidebar-menu li.home").addClass('active');
            }
          }

});




  /* fix firefox autocomplete for password input
   * autocomplete="0ff" didn't work
   * setting input field to read only
   * and then remove the attribute
   * is working
   */

    $('#pswd1, #password, #mailnew, #oldpsw').removeAttr('readonly');


  var li=$("h5 a.active").closest("li");
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

      /* for forward checkbox. If is activeate
       * show input field for maildrop address
       * and set it as required field
       */
      if($('#forward').length){
        if(document.getElementById('forward').checked) {
              $("#hidden").show();
              $("input#maildrop").prop('required',true);
        } 
      }

    $('#forward').change(function () {
    if(this.checked) {
        $("#hidden").show();
        $('input#maildrop').prop('required', true);
    } else {
        $("#hidden").hide();
        $('input#maildrop').prop('required', false);
    }
    });

    if($('#vpn').length){
      if(document.getElementById('vpn').checked ) {
          $("#hidden").show();
      }
     $('#vpn').change(function () {
           if ($(this).prop('checked')) {
                // $('input').prop('checked', true);
                $('input#sendinstruction').prop('disabled', false);
           } else {
               $('input#sendinstruction').prop('checked', false);
               $('input#sendinstruction').prop('disabled', true);
           }
     });
     $('#vpn').trigger('change');
    }


});

$(document).ready(function() {
    $("td").on("click", ".showform", function() {
      var form = $(this).closest("form");
      $(this).toggleClass("active");
      $(this).next(".sub-form").slideToggle();
    return false;
  }); 

/* set language 
 * In footer.php */

  $(".set-language").change(function() {
      $("#lang").submit();
    });

  /* select user
   * Used in edomain.php
   */

  $('#seluser').change(function(){
      $('#new_user').hide();
      $('.' + $(this).val()).show();
      if($('#new_user.newuser').is(":visible")){
        console.log('visible');
        $('input#username').prop('required', true);
        $('input#usermail').prop('required', true);
        $('input#pswd1').prop('required', true);
        $('input#pswd2').prop('required', true);
      } else {
        console.log('invisible');
          $('input#username').prop('required', false);
          $('input#dwiusermail').prop('required', false);
          $('input#pswd1').prop('required', false);
          $('input#pswd2').prop('required', false);
      }
  });

 
  /*set some inpout field as required
   * if a new user is created 
   * when adding domain
   * TODO
   */

  /*Confrm button on delete Domain
   * in page edomain.php
   * 
   */

$('#exampleModal').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget); // Button that triggered the modal
  var domain= button.data('domain'); // Extract info from data-* attributes
  // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
  // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
  var modal = $(this);
  modal.find('.modal-title').text( domain);
  var body=modal.find('.modal-body');
            $.ajax({

                type : 'POST',
                url  : 'proc/confirm-deldomain.php',
                data : {domain: domain},
                success : function(data)
                          {
                            body.html(data);
                          }
                });
})

$('#mailModal').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget); // Button that triggered the modal
  var domain =  button.data('domain');
  var mail= button.data('email'); // Extract info from data-* attributes
  // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
  // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
  var modal = $(this);
  modal.find('.modal-title').text( mail );
  var body=modal.find('.modal-body');
            $.ajax({

                type : 'POST',
                url  : 'proc/confirm-delmail.php',
                data : {mail: mail, domain:domain},
                success : function(data)
                          { 
                            body.html(data);
                          }
                });
})

$('#userModal').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget); // Button that triggered the modal
  var superuser = button.data('superuser');
  var user = button.data('user'); // Extract info from data-* attributes
  // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
  // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
  var modal = $(this);
  modal.find('.modal-title').text( user );
  var body=modal.find('.modal-body');
            $.ajax({

                type : 'POST',
                url  : 'proc/confirm-deluser.php',
                data : {user: user, superuser: superuser},
                success : function(data)
                          {
                            body.html(data);
                          }
                });
})

$('#updateModal').on('show.bs.modal', function (event) {
  //groups
  var groups = [];
  $("input:checkbox[name=groups]:checked").each(function(){
    groups.push($(this).val());
  });

  //dependencies
  var inputs = $('.dependency').get();
  var dependencies  = { };
  for (i=0; i<inputs.length; i++){
    m = inputs[i].name.match(/\[(.*?)\]\[(\d+)\]/);
    if(!dependencies[m[1]]){
       dependencies[m[1]] = { };
    }
    dependencies[m[1]][m[2]]= inputs[i].value;
  }

  //release
  var button = $(event.relatedTarget); // Button that triggered the modal
  var release = button.data('release');

  //modal
  var modal = $(this);
  if (release=='pending'){
    modal.find('.modal-title').text( "Aplicar Cambios" );
  } else {
    modal.find('.modal-title').text( release );
  }
  var body=modal.find('.modal-body');
            $.ajax({

                type : 'POST',
                url  : 'proc/confirm-update.php',
                data : {release: release, groups: groups, dependencies: dependencies},
                success : function(data)
                          {
                            body.html(data);
                          }
                });

})

$('#installModal').on('show.bs.modal', function (event) {
  //groups
  var groups = [];
  $("input:checkbox[name=groups]:checked").each(function(){
    groups.push($(this).val());
  });

  //dependencies
  var inputs = $('.dependency').get();
  var dependencies  = { };
  for (i=0; i<inputs.length; i++){
    m = inputs[i].name.match(/\[(.*?)\]\[(\d+)\]/);
    if(!dependencies[m[1]]){
       dependencies[m[1]] = { };
    }
    dependencies[m[1]][m[2]]= inputs[i].value;
  }

  //release
  var button = $(event.relatedTarget); // Button that triggered the modal
  var release = button.data('release');

  //modal
  var modal = $(this);
  modal.find('.modal-title').text( 'Instalar Aplicaciones' );
  var body=modal.find('.modal-body');
            $.ajax({

                type : 'POST',
                url  : 'proc/confirm-install.php',
                data : {release: release, groups: groups, dependencies: dependencies},
                success : function(data)
                          {
                            body.html(data);
                          }
                });

})


/* Check password strenght*/
    $("#pswd2").blur(function()
    {
        var pass2 = $(this).val();
        var pass1 = $("#pswd1").val();
        if(pass2!=pass1)
        {
            $("#pswresult").html('<span class="error"><i class="fa fa-exclamation-triangle icon checkko alert-danger"></i> <em>Las contraseñas no coinciden</em>');
        }else {
            $("#pswresult").html('');
        }

     });

    $("#pswd1, #password").blur(function()
    {
        var pass1 = $(this).val();
        if(!pass1.match(/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[0-9a-zA-Z\w\W]{8,}$/) && pass1.length > 0){
              $("#pswcheck").html('<span class="error"><i class="fa fa-exclamation-triangle icon checkko alert-danger"></i> <em>La contraseña debe ser de mínimo 8 cáracteres y debe contener almenos una cifra, una letra mayúscula y una minúscula</em>');        
        }else {
            $("#pswcheck").html('');
        }
     });

/* Check password match*/
    /*$("#pswd1").blur(function()
    {   
        var pass1 = $(this).val();
        var pass2 = $("#pswd2").val();
        if(pass2!=pass1)
        {   
            $("#pswresult").html('<span class="error"><i class="fa fa-exclamation-triangle icon checkko alert-danger"></i> <em>Las contraseñas no coinciden</em>');
        }else {
            $("#pswresult").html('');
        }   

     });*/ 

/* Check password match*/
    $("#pswd4").blur(function()
    {
        var pass1 = $(this).val();
        var pass2 = $("#pswd3").val();
        if(pass2!=pass1)
        {   
            $("#pswresultsudo").html('<span class="error"><i class="fa fa-exclamation-triangle icon checkko alert-danger"></i> <em>Las contraseñas no coinciden</em>');
        }else {
            $("#pswresultsudo").html('');
        }
     
     });

/* Check password match*/
    $("#pswd3").blur(function()
    {   
        var pass1 = $(this).val();
        if(!pass1.match(/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[0-9a-zA-Z\W]{8,}$/)){
              $("#pswchecksudo").html('<span class="error"><i class="fa fa-exclamation-triangle icon checkko alert-danger"></i> <em>La contraseña debe ser de mínimo 8 cáracteres y debe contener almenos una cifra, una letra mayúscula y una minúscula</em>');
        }else {
            $("#pswchecksudo").html('');
        }

        var pass2 = $("#pswd4").val();
        if(pass2!=pass1)
        {
            $("#pswresultsudo").html('<span class="error"><i class="fa fa-exclamation-triangle icon checkko alert-danger"></i> <em>Las contraseñas no coinciden</em>');
        }else {
            $("#pswresultsudo").html('');
        }

     }); 




/* Check valid email */
    $(".usermail").blur(function()
    {

        var email = $(this).val();
        var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
        var checkresult = pattern.test(email);
        if (!checkresult) {
        $("#emailresult").html('<span class="error"><i class="fa fa-exclamation-triangle icon checkko alert-danger"></i> <em>Email no válido</em>');
        }else {
          $("#emailresult").html('');
        }

     });

/*Check username availability with ajax*/
    $("#username").blur(function()
    {
        /* minim 3 characters*/
        var name = $(this).val();
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
                //return false;

    });
    $('select#selmail').change(function(){
          $('input#usermail').val($(this).val());
          $('input#usermail').focus().blur();
    });

/*check errors in regitration form */
    $( ".jquery-check" ).submit(function( event ) {
        var count = $(".jquery-check .error:visible").length;
        console.log(count);
        if (count != 0 ) event.preventDefault();
    });

/* Check password matches in forms
This would be a better way...but for noww still use blur
for password fields */

/*  $ (".jquery-check" ).submit(function( event ) {
      var hasError = false;
      
    if ($('#pswd2').length && $('#pswd1').length ){
        var pass2 = $("#pswd2").val();
        var pass1 = $("#pswd1").val();
        if(pass2!=pass1)
        {
            $("#pswresult").html('<span class="error"><i class="fa fa-exclamation-triangle icon checkko alert-danger"></i> <em>Las contraseñas no coinciden</em>');
            hasError = true;
        }else {
            $("#pswresult").html('');
             hasError = false;
        }
      }
      if(hasError == true) {return false;}
     });
*/


});
//Only used in index.php
$(document).ready(function() {
    //in index.php get used space bar width from data-width
    $('.used').each(function(){
    var bar= $(this);
    var barwidth=bar.data('width');
    bar.css('width',barwidth);
    });
});
