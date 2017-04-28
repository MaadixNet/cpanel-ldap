$( ".form-signin" ).hide();
$( ".updating" ).hide();

function httpGetAsync(theUrl)
{
    var xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = function() { 
        if (xmlHttp.readyState == 4 && xmlHttp.status == 200){
            //console.log(xmlHttp.responseText);
            if (xmlHttp.responseText == 'ready'){
              //UNLOCK GUI
              //console.log('ready');
              $( ".form-signin" ).show();
              $( ".updating" ).hide();
            }else{
              //LOCK GUI
              //console.log('locked');
              $( ".form-signin" ).hide();
              $( ".updating" ).show();
            }
        }
    }
    xmlHttp.open("GET", theUrl, true); // true for asynchronous 
    xmlHttp.send(null);
}

window.setInterval(function () {
  httpGetAsync ("/cpanel/status.php");
}, 1000); // repeat forever, polling every 1 seconds

