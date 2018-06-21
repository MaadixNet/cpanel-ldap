$( ".form-signin" ).hide();
$( ".updating" ).hide();
var thenewUrl='';
if (document.getElementById("newUrl")) {

  var div= document.getElementById("newUrl");
  thenewUrl = div.getAttribute("data-url");
}

function httpGetAsync(theUrl, thenewUrl)
{
    var xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = function() { 
        if (xmlHttp.readyState == 4 && xmlHttp.status == 200){
            //console.log(xmlHttp.responseText);
            if (xmlHttp.responseText == '' && thenewUrl !='' ){
              theUrl=thenewUrl;

            }else if (xmlHttp.responseText == 'ready'){
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
        return theUrl;
    }
    xmlHttp.open("GET", theUrl, true); // true for asynchronous 
    xmlHttp.send(null);
}

window.setInterval(function () {
  httpGetAsync ("/cpanel/status.php", "https://usavm2.gadix.net");
}, 1000); // repeat forever, polling every 1 seconds

