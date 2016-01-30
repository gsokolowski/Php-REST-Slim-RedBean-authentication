<?php
// from https://gist.github.com/shiyaz/5990220
// z autentyfikacja
require 'Slim/Slim.php';
require 'RedBean/rb.php';
 
\Slim\Slim::registerAutoloader();
 
class ResourceNotFoundException extends Exception { }
 
class AuthenticateFailedException extends Exception { }
 
$db_host = 'localhost';
$db_name = 'cmgr';
$db_user = 'root';
$db_password = '';
 
R::setup("mysql:host=$db_host;dbname=$db_name", $db_user, $db_password);
R::freeze(true);
 
$app = new \Slim\Slim(array(
    'debug' => true,
    'mode' => 'development',
    'cookies.secret_key' => 'o_O o_o O_o',
    'cookies.lifetime' => '30 minutes'
));
 
// Globally ensure if `id` is used as a route parameter, it is numeric,
// so handlers do not have to check an `id` parameter before mapping it
// to a numeric `id` field on the Contacts database table.
\Slim\Route::setDefaultConditions(array(
    'id' => '[0-9]{1,}',
));
 
// stubbed for demo
function isValidLogin($username, $password)
{
//    return true;
    return ($username == 'demo' && $password == 'password');
}
 
$checkLoggedOn = function ($app) {
    return function () use ($app) {
        if (!isValidLogin($app->getEncryptedCookie('username'),
            $app->getEncryptedCookie('password'))
        ) {
            $app->halt(401); // Unauthorized access
        }
    };
};
 
$app->post('/login', function () use ($app) {
    try {
        $username = $app->request()->post('username');
        $password = $app->request()->post('password');
 
        if (isValidLogin($username, $password)) {
            $app->setEncryptedCookie('username', $username, '1 day');
            $app->setEncryptedCookie('password', $password, '1 day');
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
 
 
// API for CRUD operations on Contacts
 
$app->get('/contacts', $checkLoggedOn($app), function () use ($app) {
    try {
        $contacts = R::find('contacts');
        $app->response()->header('Content-Type', 'application/json');
        echo json_encode(R::exportAll($contacts));
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});
 
$app->get('/contacts/:id', $checkLoggedOn($app), function ($id) use ($app) {
    try {
        $contact = R::findOne('contacts', 'id=?', array($id));
        if ($contact) {
            $app->response()->header('Content-Type', 'application/json');
            echo json_encode(R::exportAll($contact));
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
 
$app->put('/contacts/:id', $checkLoggedOn($app), function ($id) use ($app) {
    try {
        $request = $app->request();
        $body = $request->getBody();
        $input = json_decode($body);
 
        $contact = R::findOne('contacts', 'id=?', array($id));
 
        // only the fields passed in the request body are updated
        if ($contact) {
            $contact->title = isset($input->title) ? (string)$input->title : $contact->title;
            $contact->forename = isset($input->forename) ? (string)$input->forename : $contact->title;
            $contact->surname = isset($input->surname) ? (string)$input->surname : $contact->surname;
            $contact->company = isset($input->company) ? (string)$input->company : $contact->company;
            $contact->email = isset($input->email) ? (string)$input->email : $contact->email;
            $contact->phone = isset($input->phone) ? (string)$input->phone : $contact->phone;
            $contact->address = isset($input->address) ? (string)$input->address : $contact->address;
 
            R::store($contact);
 
            $app->response()->header('Content-Type', 'application/json');
            echo json_encode(R::exportAll($contact));
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
 
$app->post('/contacts', $checkLoggedOn($app), function () use ($app) {
    try {
        $request = $app->request();
        $body = $request->getBody();
        $input = json_decode($body);
 
        $contact = R::dispense('contacts');
 
        $contact->title = (string)$input->title;
        $contact->forename = (string)$input->forename;
        $contact->surname = (string)$input->surname;
        $contact->company = (string)$input->company;
        $contact->email = (string)$input->email;
        $contact->phone = (string)$input->phone;
        $contact->address = (string)$input->address;
 
        $id = R::store($contact);
 
        $app->response()->header('Content-Type', 'application/json');
        echo json_encode(R::exportAll($contact));
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});
 
$app->delete('/contacts/:id', $checkLoggedOn($app), function ($id) use ($app) {
    try {
        $request = $app->request();
 
        $contact = R::findOne('contacts', 'id=?', array($id));
 
        if ($contact) {
            R::trash($contact);
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
 
$app->run();
 
?>