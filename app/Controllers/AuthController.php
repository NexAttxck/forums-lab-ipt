<?php
namespace App\Controllers;

use App\Models\User;
use App\Support\{Auth, View};

class AuthController
{
    public function loginForm(): void { View::render('login'); }
    public function registerForm(): void { View::render('register'); }

    public function login(): void
    {
        if (!Auth::csrfOk()) { $this->forbidden(); return; }
        $name = trim((string) ($_POST['username'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $u = User::byUsername($name);
        // a null hash (seed users) never authenticates
        if ($u && $u['password_hash'] !== null && password_verify($pass, $u['password_hash'])) {
            Auth::login((int) $u['id'], $u['username']);
            View::redirect('/discussions/1');
            return;
        }
        View::render('login', ['error' => 'Wrong username or password.', 'username' => $name], 401);
    }

    public function register(): void
    {
        if (!Auth::csrfOk()) { $this->forbidden(); return; }
        $name = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');

        $err = null;
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $name)) $err = 'Username: 3 to 30 letters, numbers or underscore.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) $err = 'Enter a valid email.';
        elseif (strlen($pass) < 8 || strlen($pass) > 72) $err = 'Password must be 8 to 72 characters.';
        elseif (User::exists($name, $email)) $err = 'Username or email is already taken.';

        if ($err) {
            View::render('register', ['error' => $err, 'username' => $name, 'email' => $email], 422);
            return;
        }
        $id = User::create($name, $email, password_hash($pass, PASSWORD_DEFAULT));
        Auth::login($id, $name);
        View::redirect('/discussions/1');
    }

    public function logout(): void
    {
        if (!Auth::csrfOk()) { $this->forbidden(); return; }
        Auth::logout();
        View::redirect('/discussions/1');
    }

    private function forbidden(): void
    {
        View::render('error', ['title' => 'Forbidden', 'message' => 'Invalid CSRF token.'], 403);
    }
}
