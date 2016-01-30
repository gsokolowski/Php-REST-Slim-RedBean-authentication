<?php
// call this in url http://127.0.0.1:8080/slim/indexGetId.php/articles/2

require 'Slim/Slim.php';
require 'RedBean/rb.php';
use Slim\Slim; //use this to skip one \Slim
Slim::registerAutoloader();

// set up database connection
R::setup('mysql:host=localhost;dbname=articles','root','');
R::freeze(true);

// initialize app
$app = new Slim();

//$paramValue = $app->request()->params('paramName');
//$allGetVars = $app->request->get();
//$allPostVars = $app->request->post();
//$allPutVars = $app->request->put();
//var_dump($allGetVars);
//var_dump($allPostVars);
//var_dump($allPutVars);

class ResourceNotFoundException extends Exception {}

// handle GET requests for /articles/:id
$app->get('/articles/:id', function ($id) use ($app) {    
  try {
    // query database for single article
    $article = R::findOne('articles', 'id=?', array($id));
    
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

// run
$app->run();