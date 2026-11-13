<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\{AuthController, DiscussionController};
use App\Support\{Auth, View};

Auth::start();

$router = new \Bramus\Router\Router();
$d = new DiscussionController();
$a = new AuthController();

require __DIR__ . '/../config/routes.php';

$router->run();
