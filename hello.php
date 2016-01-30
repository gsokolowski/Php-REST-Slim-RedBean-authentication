<?php
//http://127.0.0.1:8080/slim/hello.php/hello/Greg?zmienna=423423
require 'Slim/Slim.php';
use Slim\Slim; //use this to skip one \Slim
Slim::registerAutoloader();

$app = new Slim();
$taZmienna = $app->request()->get();
var_dump($taZmienna);


$app->get('/hello/:name', function ($name) {
    echo "Hello, $name";
    
});
$app->run();