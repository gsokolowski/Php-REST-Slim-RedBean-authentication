<!doctype html>
<html>
  <head>
    <script src="jquery/jquery.min.js"></script>
    <script>
    $(document).ready(function(){
      $("#buttonPost").click(function(){
  	    $.ajax({
	        url: "index.php/articles",
	        type: "POST",
	        contentType: 'application/json',
	        data:  JSON.stringify({
	        	title:"Mike",
	        	url:"http://www.mike.com",
	        	date:"2014-02-14"
            }),
	        dataType: "JSON",
	        success: function (jsonStr) {
	            console.log("Ajax POST successul");
	        }  
          });
        });

      $("#buttonPut").click(function(){
  	    $.ajax({
	        url: "index.php/articles/2",
	        type: "PUT",
	        contentType: 'application/json',
	        data:  JSON.stringify({
	        	title:"PIOTR",
	        	url:"http://www.aaaa.com",
	        	date:"2013-09-10"
            }),
	        dataType: "JSON",
	        success: function (jsonStr) {
	            console.log("Ajax PUT successul");
	        }  
          });
        });


      $("#buttonDelete").click(function(){
  	    $.ajax({
	        url: "index.php/articles/34",
	        type: "DELETE",
	        contentType: 'application/json',
	        data:  JSON.stringify({
	        	title:"Donald Duck 5",
	        	url:"http://www.Duckburg.com",
	        	date:"2013-09-10"
            }),
	        dataType: "JSON",
	        success: function (jsonStr) {
	            console.log("Ajax DELETE successul");
	        }  
          });
        });
      
      $("#buttonLogin").click(function(){
  	    $.ajax({
	        url: "index.php/login",
	        type: "POST",
	        contentType: 'application/json',
	        data:  JSON.stringify({
	        	username:"Greg",
	        	password:"letMeIn"
            }),
	        dataType: "JSON",
	        success: function (jsonStr) {
	            console.log("Ajax POST autentyfication successul");
	        }  
          });
        });
      
      });   
    </script>
  </head>
  <body>
  <div id="result"></div>
	<button id="buttonPost">HTTP POST request to a page and get the result back</button>
	<button id="buttonPut">HTTP PUT request to a page and get the result back</button>
	<button id="buttonDelete">HTTP DELETE request to a page and get the result back</button>
	<button id="buttonLogin">HTTP POST user name and pass</button>
  </body>
</html>

