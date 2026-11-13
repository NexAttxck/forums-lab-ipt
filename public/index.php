<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\{AuthController, DiscussionController};
use App\Support\{Auth, View};

Auth::start();

$router = new \Bramus\Router\Router();
$d = new DiscussionController();
$a = new AuthController();

$router->get('/', fn() => View::redirect('/discussions/1'));
// a non-numeric id does not match the route, so the router answers 404 (set404 below)
$router->get('/discussions/(\d+)', [$d, 'show']);
$router->post('/discussions/(\d+)/comments', [$d, 'store']);
$router->get('/login', [$a, 'loginForm']);
$router->post('/login', [$a, 'login']);
$router->get('/register', [$a, 'registerForm']);
$router->post('/register', [$a, 'register']);
$router->post('/logout', [$a, 'logout']);
$router->set404(fn() => View::render('error', ['title' => 'Not found', 'message' => 'Page not found.'], 404));

$router->run();
