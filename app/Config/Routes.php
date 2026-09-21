<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Routes that need a logged in user
$auth = ['filter' => 'auth'];

/*
|--------------------------------------------------------------------------
| Landing page: logged in -> /tasks, otherwise -> /login
|--------------------------------------------------------------------------
*/
$routes->get('/', 'Home::index');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::authenticate');

$routes->match(['get', 'post'], 'register', 'AccountController::register');
$routes->match(['get', 'post'], 'forgot-password', 'AccountController::forgotPassword');
$routes->match(['get', 'post'], 'reset-password', 'AccountController::resetPassword');
$routes->get('logout', 'AccountController::logout');

/*
|--------------------------------------------------------------------------
| Task board
|--------------------------------------------------------------------------
*/
$routes->get('tasks', 'TaskController::index', $auth);
$routes->get('tasks/add', 'TaskController::add', $auth);
$routes->post('tasks/create', 'TaskController::create', $auth);
$routes->get('tasks/view/(:num)', 'TaskController::show/$1', $auth);
$routes->match(['get', 'post'], 'tasks/edit/(:num)', 'TaskController::edit/$1', $auth);
$routes->get('tasks/delete/(:num)', 'TaskController::delete/$1', $auth);
$routes->get('tasks/toggle/(:num)', 'TaskController::toggle/$1', $auth);
$routes->post('tasks/update-field', 'TaskController::updateField', $auth);
$routes->post('tasks/update-progress', 'TaskController::updateProgress', $auth);

/*
|--------------------------------------------------------------------------
| Comments
|--------------------------------------------------------------------------
*/
$routes->get('tasks/comments/(:num)', 'CommentController::index/$1', $auth);
$routes->post('tasks/comments/add', 'CommentController::add', $auth);

/*
|--------------------------------------------------------------------------
| Attachments
|--------------------------------------------------------------------------
*/
$routes->get('tasks/attachments/(:num)', 'AttachmentController::index/$1', $auth);
$routes->post('tasks/attachments/upload', 'AttachmentController::upload', $auth);
$routes->post('tasks/attachments/delete', 'AttachmentController::delete', $auth);
$routes->get('tasks/attachments/view/(:num)', 'AttachmentController::show/$1', $auth);

/*
|--------------------------------------------------------------------------
| Boards
|--------------------------------------------------------------------------
*/
$routes->post('boards/create', 'BoardController::create', $auth);
$routes->post('boards/delete', 'BoardController::delete', $auth);
