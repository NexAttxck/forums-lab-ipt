<?php
// $router, $d (DiscussionController) and $a (AuthController) come from public/index.php
use App\Support\View;

$router->get('/', [$d, 'index']);
// any segment is captured so the controller decides: malformed id = 400, missing = 404
$router->get('/discussions/([^/]+)', [$d, 'show']);
$router->post('/discussions/([^/]+)/comments', [$d, 'store']);
$router->post('/discussions/([^/]+)/comments/([^/]+)/replies', [$d, 'store']);
$router->get('/login', [$a, 'loginForm']);
$router->post('/login', [$a, 'login']);
$router->get('/register', [$a, 'registerForm']);
$router->post('/register', [$a, 'register']);
$router->post('/logout', [$a, 'logout']);
$router->set404(fn() => View::render('error', ['title' => 'Not found', 'message' => 'Page not found.'], 404));
