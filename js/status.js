$( ".form-signin" ).hide();
$( ".updating" ).hide();
var theUrl = "/cpanel/status.php";

function httpGetAsync(theUrl)

{

fetch(theUrl) // Call the fetch function passing the url of the API as a parameter
.then(function(response) {
    console.log(response);
    // Your code for handling the data you get from the API
  if(response.status == 200){
      return response.text();
  }
  })
  .then(function(text) {
          if(text=='ready') {
           setTimeout(function() {
                $( ".form-signin" ).show();
                $( ".updating" ).hide();
           }, 3000);             //UNLOCK GUI

            }else{
              //LOCK GUI
              //console.log('locked');
              $( ".form-signin" ).hide();
              $( ".updating" ).show();
               setTimeout(function() {
                     httpGetAsync(theUrl);
               }, 2000);

            }
  })

.catch(function(error) {
   clearInterval(httpGetAsync);
   console.log('Fetch Error:', error);
   setTimeout(function() {
        $( ".form-signin" ).show();
        $( ".updating" ).hide();
   }, 5000);
});
}
httpGetAsync(theUrl);
