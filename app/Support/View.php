<?php
namespace App\Support;

class View
{
    public static function render(string $tpl, array $data = [], int $status = 200): void
    {
        $dir = __DIR__ . '/../Views';
        $loader = new \Mustache_Loader_FilesystemLoader($dir, ['extension' => '.mustache']);
        $m = new \Mustache_Engine([
            'loader' => $loader,
            'partials_loader' => $loader,
            'entity_flags' => ENT_QUOTES,
        ]);
        $data += [
            'csrf' => Auth::csrf(),
            'loggedIn' => Auth::userId() !== null,
            'me' => $_SESSION['name'] ?? '',
        ];
        // the page body is already escaped output from the engine
        $data['content'] = $m->render($tpl, $data);
        http_response_code($status);
        echo $m->render('layout', $data);
    }

    public static function redirect(string $to): void
    {
        header('Location: ' . $to, true, 303);
    }
}
