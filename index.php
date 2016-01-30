<?php
// call this http://127.0.0.1:8080/php-slim/index.php/articles or
// call http://127.0.0.1:8080/php-slim/indexGetId.php/articles/2

require 'Slim/Slim.php';
require 'RedBean/rb.php';
use Slim\Slim; //use this to skip one \Slim
Slim::registerAutoloader();

// extend this class to call constructor to have access to getMessage()
class ResourceNotFoundException extends Exception {}
class AuthenticateFailedException extends Exception { }

$dbHost = 'localhost';
$dbName = 'articles';
$dbUser = 'root';
$dbPassword = '';

R::setup("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPassword);
R::freeze(true);

$app = new Slim(array(
    'debug' => true,
    'mode' => 'development',
    'cookies.secret_key' => 'secretKey',
    'cookies.lifetime' => '30 minutes'
));
//var_dump($app);
// Globally ensure if `id` is used as a route parameter, it is numeric,
// so handlers do not have to check an `id` parameter before mapping it
// to a numeric `id` field on the Contacts database table.
\Slim\Route::setDefaultConditions(array(
    'id' => '[0-9]{1,}',
));

// Autentification 
// POST sends username and password na route /login
// route /login catches POST request and sets setEncryptedCookie('username', $username, '1 day');

// When you call get(/article .... $checkLoggedOn($app) is called
// $checkLoggedOn = function ($app) validates and if user password is valid will return true
// and rest of get(/article route will be triggered

function isValidLogin($username, $password){
//    return true;
    return ($username == 'Greg' && $password == 'letMeIn');
}

$authenticateUser = function ($app) {
    return function () use ($app) {
        if ( !isValidLogin( $app->getCookie('username'), $app->getCookie('password') )) 
        {
            $app->halt(401); // Unauthorized access
        }
    };
};

$app->post('/login', function () use ($app) {
    try {
    	// get user and pass from post if from form as dataType=html 
        //$username = $app->request->post('username');
        //$password = $app->request->post('password');
        
	    // get user and pass from post - get and decode JSON request body
	    $body = $app->request()->getBody();
	    $input = json_decode($body); 
	    $username = (string)$input->username;
	    $password = (string)$input->password;
        // this is how you can check what has been passed. Look into responds from ajaxPost.php
 		//var_dump($password);
        if (isValidLogin($username, $password)) {
        	// if username and pass are valid set Cookie
            $app->setCookie('username', $username, '1 day');
            $app->setCookie('password', $password, '1 day');
            $app->response()->header('Content-Type', 'application/json');
            $app->response()->status(200); // OK
            echo json_encode(array('operation' => 'login', 'status' => 'ok'));
        } else {
            throw new AuthenticateFailedException();
        }
    } catch (AuthenticateFailedException $e) {
        $app->response()->status(401);
        $app->response()->header('X-Status-Reason', 'Login failure');
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});
 
$app->get('/logout', function () use ($app) {
    try {
        $app->deleteCookie('username');
        $app->deleteCookie('password');
        $app->response()->header('Content-Type', 'application/json');
        $app->response()->status(200); // OK
        echo json_encode(array('operation' => 'logout', 'status' => 'ok'));
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

// API for CRUD operations on articles
// handle GET requests for /index.php/articles

$app->get('/articles', $authenticateUser($app), function () use ($app) {  
  // query database for all articles
  $articles = R::find('articles'); 
  
  // send response header for JSON content type
  $app->response()->header('Content-Type', 'application/json');
  
  // return JSON-encoded response body with query results
  echo json_encode(R::exportAll($articles));
});


// handle GET requests for /articles/:id
// callback function () is passed through get()
$app->get('/articles/:id', $authenticateUser($app), function ($id) use ($app) {    
  try {
    // query database for single article
    $article = R::findOne('articles', 'id=?', array($id));
    //var_dump($article);
    if ($article) {
      // if found, return JSON response
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode(R::exportAll($article));
    } else {
      // else throw exception
      throw new ResourceNotFoundException();
    }
  } catch (ResourceNotFoundException $e) {
    // return 404 server error
    $app->response()->status(404);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});


// handle POST requests to /articles
$app->post('/articles', $authenticateUser($app), function () use ($app) {    
  try {
    // get and decode JSON request body
    $body = $app->request()->getBody();
    $input = json_decode($body); 
    
    // store article record
    $article = R::dispense('articles');
    $article->title = (string)$input->title;
    $article->url = (string)$input->url;
    $article->date = (string)$input->date;
    $id = R::store($article);    
    
    // return JSON-encoded response body
    $app->response()->header('Content-Type', 'application/json');
    echo json_encode(R::exportAll($article));
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

// handle PUT requests to /articles/:id
$app->put('/articles/:id', $authenticateUser($app), function ($id) use ($app) {    
  try {
    // get and decode JSON request body
    $body = $app->request()->getBody();
    $input = json_decode($body); 
    
    // query database for single article
    $article = R::findOne('articles', 'id=?', array($id));  
    
    // store modified article
    // return JSON-encoded response body
    if ($article) {      
      $article->title = (string)$input->title;
      $article->url = (string)$input->url;
      $article->date = (string)$input->date;
      R::store($article);    
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode(R::exportAll($article));
    } else {
      throw new ResourceNotFoundException();    
    }
  } catch (ResourceNotFoundException $e) {
    $app->response()->status(404);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

// handle DELETE requests to /articles/:id
$app->delete('/articles/:id', $authenticateUser($app), function ($id) use ($app) {    
  try {

    $article = R::findOne('articles', 'id=?', array($id));  
    
    // delete article
    if ($article) {
      R::trash($article);
      $app->response()->status(204);
    } else {
      throw new ResourceNotFoundException();
    }
  } catch (ResourceNotFoundException $e) {
    $app->response()->status(404);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});
// run
$app->run();


