$(document).ready(function() {

     $(window).load(function() {
          $('#loading').hide();
      });



     var pgurl = window.location.href.substr(window.location.href
.lastIndexOf("/")+1);
    // console.log('pg url: ' +pgurl); 
     $("#sidebar-menu a").each(function(){
          var itemurl= $(this).attr("href").substr($(this).attr("href").lastIndexOf("/")+1);
     //     console.log('itemurl: '+ itemurl);

          if( itemurl  == pgurl && itemurl != '' ){
            $(this).parent('li').addClass("active");
            $("#sidebar-menu ul:has(li.active)").addClass('collapse in');
            $("#sidebar-menu li:has(ul.in)").addClass('active');
          } else {
            if ( itemurl  == pgurl && itemurl == ''  || pgurl == 'index.php' || pgurl=='basic-info' || pgurl=='loading' || pgurl=='accounts' ){
              $("#sidebar-menu li.home").addClass('active');
              $("#sidebar-menu li.home ul").addClass('collapse in');
              $("#sidebar-menu li.dashboard").addClass('active');
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

      /* for vacationactive checkbox. If is activeate
       * show input field for maildrop address
       * and set it as required field
       */
      if($('#vacationactive').length){
        if(document.getElementById('vacationactive').checked) {
              $("#hiddenreply").show();
              $("input#vacationinfo").prop('required',true);
        }
      } 
    $('#vacationactive').change(function () {
    if(this.checked) {
        $("#hiddenreply").show();
        $('input#vacationinfo').prop('required', true);
    } else {
        $("#hiddenreply").hide();
        $('input#vacationinfo').prop('required', false);
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
        $('input#username').prop('required', true);
        $('input#usermail').prop('required', true);
        $('input#pswd1').prop('required', true);
        $('input#pswd2').prop('required', true);
      } else {
          $('input#username').prop('required', false);
          $('input#usermail').prop('required', false);
          $('input#pswd1').prop('required', false);
          $('input#pswd2').prop('required', false);
      }
  });

 
  /*set some inpout field as required
   * if a new user is created 
   * when adding domain
   * TODO
   */
/* Confirm deactivate or reactivate gorups in services.php */
$('#changeStatusModal').on('show.bs.modal', function (event) {
  //groups
  var activategroups = [];
  var deactivategroups= [];
  //$("input:checkbox[name=gorups]:checked").each(function(){
  $("input[name='activateGroup\[\]']").each(function(){
    var appName = $(this).parent('div').attr('data-groupname');
    activategroups.push(appName);
  });
  $("input[name='deactivateGroup\[\]']").each(function(){
    var appName = $(this).parent('div').attr('data-groupname');
    deactivategroups.push(appName);
  });


  var button = $(event.relatedTarget); // Button that triggered the modal
  var modal = $(this);
  var body=modal.find('#modal-response');
            $.ajax({

                type : 'POST',
                url  : 'proc/confirm-changeAppStatus.php',
                data : {activategroups: activategroups, deactivategroups: deactivategroups},
                success : function(data)
                          { 
                            body.html(data);
                          }
                });
})

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
$('#rebootModal').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget); // Button that triggered the modal
  // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
  // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
  var modal = $(this);
  var body=modal.find('.modal-body');
            $.ajax({

                type : 'POST',
                url  : 'proc/confirm-reboot.php',
                data : {},
                success : function(data)
                          {
                            body.html(data);
                          }
                });
})

$('#updateModal').on('show.bs.modal', function (event) {

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
                data : {release: release },
                success : function(data)
                          {
                            body.html(data);
                          }
                });

})

$('#installModal').on('show.bs.modal', function (event) {
  //groups
  var groups = [];
  var deps = [];
  //$("input:checkbox[name=gorups]:checked").each(function(){
  $("input[name='installGroup\[\]']").not('.dependency').each(function(){
     var appName = $(this).parent('div').attr('data-groupname');
    groups.push(appName);
  });
  $("input.dependency[name='installGroup\[\]']").each(function(){
      var newItem = $(this).val();

      if(deps.indexOf(newItem) === -1){
        deps.push(newItem);
      }
  });
  //release
  var button = $(event.relatedTarget); // Button that triggered the modal
  var release = button.data('release');

  //modal
  var modal = $(this);
  modal.find('.modal-title').text( 'Instalar Aplicaciones' );
  var body=modal.find('#modal-response');
            $.ajax({

                type : 'POST',
                url  : 'proc/confirm-install.php',
                data : {release: release, groups: groups,deps:deps },
                success : function(data)
                          {
                            body.html(data);
                          }
                });

})

$('#fqdnModal').on('show.bs.modal', function (event) {
  var modal = $(this);
  var customlogmail = false;
  var body=modal.find('#modal-body');
  body.html('');
  var domain = $("#domain_new").val();
  if(document.getElementById('logmailctive').checked)customlogmail=true;   
  var button = $(event.relatedTarget); // Button that triggered the modal

            $.ajax({

                type : 'POST',
                url  : 'proc/domain-check-fqdn.php',
                data : {domain: domain, customlogmail:customlogmail},
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


/* Modals for groups activation and desactivation */
$('input.deactivateGroup:checkbox').on('change', function(e){
  var appmodal= $(this).parents('.col-md-6').find('input[name="groupname"]').val();
  if(e.target.checked){
    $('<input type="hidden" name="deactivateGroup\[\]" value="' + appmodal + '">').prependTo("#deactivate-group-" +appmodal);
  } else {
  $('#deactivate-group-' +appmodal +' input').remove();
  }
});

/* Modal for groups activation and desactivation */
$('input.activateGroup:checkbox').on('change', function(e){
  var appmodal= $(this).parents('.col-md-6').find('input[name="groupname"]').val();
  if(e.target.checked){
    $('<input type="hidden" name="activateGroup\[\]" value="' + appmodal + '">').prependTo("#activate-group-" +appmodal);
  } else {
  $('#activate-group-' +appmodal +' input').remove();
  }
});

//////      Modal frames required fields apps     //////////////////////////////////////////////////////
//doc: https://stackoverflow.com/questions/27554027/pass-data-to-parent-window-from-modal-using-bootstrap
//doc: https://stackoverflow.com/questions/35754776/checkbox-change-state-of-checkbox-by-ajax

//current app modal var
//var appmodal;

$('input.installGroups:checkbox').on('change', function(e){
  var appmodal = $(this).parents('.col-md-6').find('input[name="groups"]').val();
  if(e.target.checked){
    //get values
    var groupname = $(this).parents('.col-md-6').find('input[name="groupname"]').val();
    // If is a user input clone it into the modal window
    // Also populate modal window wuth hidden inputs, so we have all in one place
    var username= $(this).parents('.col-md-6').find('input[name="sysuser"]').val(); 
    /* If the application needs a user to be created, check that it is not present in system yet
     */
    var usererror=0;
    if (username){
    var usererror = function () {
     var error=0; 
                 $.ajax({
                async: false,
                type : 'POST',
                url  : 'proc/check-username.php',
                data : {username: username },
                // Si usamos json habrá que actualizar php
                success : function(data)

                  {
                    if (data.indexOf("error") >= 0) {
                        error=1;
                        console.log(username); 
                    }else {
                      error=0; 
                    }
                  }
                });
                return error;

              }(); 
    }
    /* TODO: change proc/check-username.php to return errornumber and texts so to avoiud writing string
    * in this function. When doing so usuarios.php and add-domain need to be updated to in order to print
    * correctly the error sytring in case a already existing user is created
    */
    if (usererror !=0){
        $(this).prop('checked', false);
        $('#fieldsModal h4.modal-title').html('Error');
        $('#fieldsModal .modal-body').html('No se puede instalar la aplicación porque necesita crear el nombre de usuario ' + username + ' que ya está presente en el sistema');
        $( '#fieldsModal #fieldsSavei').addClass('hide');
        $('#fieldsModal').modal();
        
    }else if ($(this).hasClass('depNeedsInput') && usererror==0){
      //set modal title
      $("span.appnameSpan").text(groupname);
      //leave chekbox unchanged
      $(this).prop('checked', false);
      //clone all requiered inputs of this app to modal, except groups and dependency fields
      $(this).parents('.col-md-6').find('.modalfield, .noinput').not('input[name="groups"],.dependency').clone().removeClass( "hide").prop('required',true).prependTo( "#fieldsModal form .form-group" );
      $(this).parents('.col-md-6').find('.noinput').clone().prependTo( "#fieldsModal form .form-group" ); 
      //Also add an input name=gorups type hidden, to use in final form for install submission
      $('<input type="hidden" name="installGroup\[\]" value="' + appmodal + '">').prependTo( "#fieldsModal form .form-group" );
      //open modal
      $('#fieldsModal').modal();
    } else {
      // If no user input is required just populate the main page with the input hidden fields
      // Each of them is placed in his div inside the final form for installing
       $('<input type="hidden" name="installGroup\[\]" value="' + appmodal + '">').prependTo("#install-group-" +appmodal); 
      var depNoInput =  $(this).parents('.col-md-6').find('.noinput').val();
      if (depNoInput)$('<input type="hidden" class="dependency" name="installGroup\[\]" value="' + depNoInput+ '">').prependTo("#install-group-" +appmodal);
    }
  } else {
    // If a ceckbox is uncheckek remove all input field from the form. Using a div for each app make it easy
    // To remove all the inputs for one unchecked app in one line.
    $('#install-group-' +appmodal +' input').remove();
  }
});

/* Function to check valididty for input fields wheun istalling apps with dependencies
 * */

$("#fieldset").submit(function(e) {
  var appmodal = $(this).parents('#fieldsModal').find(':input[name="installGroup\[\]"]').val();
  //var appmodal = $('#fieldsModal').find(':input[name="installGroup"]').val();
    var depNoInput = $(this).parents('#fieldsModal').find(':input[name="depNoInput"]').val();
  e.preventDefault();

  var checkInput='';

  //Clean previous errors in divs  
  $('div#error-' +appmodal +'-domain' ).html();
  $("#fields-info").html();

  // Get all inputs inserted by user in dependency domain field 
  var fields = {};
  $("#fieldsModal").find(":input.modalfield").each(function() {
  // Create an indexed aray filedId: fieldValue 
  // eg.Array [fields] Object { domain: mydomain.com, mail: me@example.com}...

      fields[$(this).attr('data-dependency')] = $(this).val();
  });
  //This give us the name of the group to be installed
  var appmodal = $('#fieldsModal').find(':input[name*="installGroup\[\]"]').val();
  /* check if another group has the same domain assigned*/
  //get all inputs type=hidden, with class="dependency", name=domain[* and value=last user inserted  domain 
    checkInput=$('input[type="hidden"][name*="domain"][value="' + fields.domain + '"]');

  //If this domain has been assigned to another aplication to be installed, print an error
  if(checkInput.length > 0) {
    //get the name of the other aplicaction using same domain
    var usedby = $(checkInput).parents('.group-inputs').attr('data-groupname');

    // Notfy user that the domain has just been assigned to another aplication to be installed
    // The message will be printed above the input field
    $('div#error-' +appmodal +'-domain' ).html('<span class="has-error">Dominio ya asignado a ' + usedby + '</span>');
  } else {
    /* If the inserted domain is not selected for another aplication to be installed
    * check other fields and domain validity against ldap, and DNS
    * sending AJAX request to proc/check-fields.php
    */
    //check values
    var modal = $('#fieldsModal');
    var fieldsinfo=modal.find('.fields-info');
            $.ajax({

                type : 'POST',
                url  : 'proc/check-fields.php',
                data : fields,
                // Si usamos json habrá que actualizar php
                success : function(data)
                    
                    {
                    data=$.parseJSON(data);
                    console.log(data);
                    if(data.totErros== 0){
                         $("#fieldsModal").modal('hide');
                          $(this).off('submit').submit();
                          $('input:checkbox[name="groups"][value=' + appmodal + ']').prop('checked', true);
                          // Populate all the inputs for groups to be installed
                          $('<input type="hidden" name="installGroup[]" value="' + appmodal + '">').prependTo( "#install-group-" +appmodal );
                          //Add all dependencies that have no input field to the final form, for install submission 
                          if(depNoInput){
                              $('<input type="hidden" name="installGroup[]" class="dependency" value="' + depNoInput + '">').prependTo( "#install-group-" +appmodal );
                        }
                          /* Loop through all dependencies with a user input (domains, emal, custom text)
                           * and print them in the final form
                           * Format is name=inputDep[aplication][dependency-name] 
                           * element is the array, coming from proc/check-fields.php
                           * element.fieldId = the name of the dependency field
                           * appmodal = the name of the aplication 
                           */
                          $.each(data.inputs, function(index, element) {
                           $('<input type="hidden" name="inputDep['+ appmodal + '][' + element.fieldId +']" value="' + element.fieldValue +  '">').prependTo( '#install-group-' +appmodal+ '');
                          });

                    } else  {

                      //Generic error in modal footer
                      $("#fields-info").html(data.formError);
                      // detailed errors, each one above their correspondent input field
                      // errors are  an idexed Array [errors] of Objects {fieldId: doamin, fieldValue: example.comi, msg: invalid value for field, error: integer of error id}....
                         $.each(data.errors, function(index, errval) {
                              var mydiv = $('div#error-' +appmodal +'-'+ errval.fieldId + '' ); 
                              $('div#error-' +appmodal +'-'+ errval.fieldId + '' ).addClass('red').html(errval.msg);
                          });

                      }
                    }
                });
      } //End if existsDomain>0
});

/* Funtion to call the domain validator only for dependencies updates
*/
$("#checkFieldsUpdate").click(function(e){
    form=$("#updateDependencies");
    var app = $(form).find(':input[name*="application"]').val();
    $('#loading').show();
    $(form).find(".errorrecords").each(function() {
      $(this).html('');
    });
  // Get all inputs inserted by user in dependency domain field 
  var fields = {};
  $(form).find(":input.boxed").each(function() {
    // Check if vañue has changed, If not, don't do any check
    if ($(this).val() != $(this).attr('data-oldvalue')){
 
      // Create an indexed aray filedId: fieldValue 
      // eg.Array [fields] Object { domain: mydomain.com, mail: me@example.com}...
      fields[$(this).attr('data-dependency')] = $(this).val();
    } 
  });
  //Clean previous errors in divs  
//  $('div#error-' +appmodal +'-domain' ).html();
//  $("#fields-info").html();
    var modal = $('#updateGroupDepsModal');
//    var fieldsinfo=modal.find('.fields-info');
    var body=$('#updateDependencies').find('.modal-body');

    /* If no changes are made , pass over
    */
    if(jQuery.isEmptyObject( fields )){
    /*TODO: pront some message?
    */
      $('#loading').hide();
    
    } else {
    /* 
    * check other fields and domain validity against ldap, and DNS
    * sending AJAX request to proc/check-fields.php
    */
    //check values
            $.ajax({

                type : 'POST',
                url  : 'proc/check-fields.php',
                data : fields,
                // Si usamos json habrá que actualizar php
                success : function(data)

                    {
                    data=$.parseJSON(data);
                    if(data.totErros== 0){
                          $('#loading').hide();
                          $(form).find('div#lastConfirm').removeClass("hide");
                          $(form).find('div#form-elements').addClass("hide");  
                    } else  {
                      //return error from ajaz function
                      // detailed errors, each one above their correspondent input field
                      // errors are  an idexed Array [errors] of Objects {fieldId: doamin, fieldValue: example.comi, msg: invalid value for field, error: integer of error id}....
                      /* TODO: if a groups has multiple custom deps, and a user wants to 
                      * chande a txt value preserving the domain, it gives error, because the domain is
                      * is already in use by itself. Maybe check after the data response if is this case and 
                      * remove te error
                      */
                         $.each(data.errors, function(index, errval) {
                              $('div#error-' +errval.fieldId+ '' ).addClass('red').html(errval.msg);
                          });
                        $('#loading').hide();
 
                    }
                  }
                });
      }//end if field is empty
});
$("#checkBack").click(function(e){
    $("#updateDependencies").find('div#lastConfirm').toggleClass("hide");
    $("#updateDependencies").find('div#form-elements').removeClass("hide");

});

$('#fieldsModal').on('hide.bs.modal', function (event) {
  //clear all input forms from modal and div info
  $('#fieldsModal form .form-group').children().not(':button, :submit, :reset').remove();
  $('#fieldsModal #fields-info, .has-error').empty();
});

