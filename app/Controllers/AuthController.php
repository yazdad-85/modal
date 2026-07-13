<?php

namespace App\Controllers;

use App\Models\LoginAttemptModel;
use App\Models\UserModel;

class AuthController extends BaseController
{
    public function loginForm()
    {
        if (session('user_id')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    public function login()
    {
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $ip       = $this->request->getIPAddress();

        $attempts = new LoginAttemptModel();

        if ($attempts->tooManyAttempts($ip, $email)) {
            return redirect()->back()->withInput()
                ->with('error', 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit.');
        }

        $userModel = new UserModel();
        $user      = $userModel->findByEmail($email);

        if (! $user || ! password_verify($password, $user['password'])) {
            $attempts->record($ip, $email);

            return redirect()->back()->withInput()
                ->with('error', 'Email atau password salah');
        }

        session()->regenerate(true);
        session()->set(['user_id' => $user['id'], 'user_name' => $user['name']]);

        return redirect()->to('/dashboard');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login');
    }
}
