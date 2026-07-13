<?php

namespace App\Commands;

use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CreateUser extends BaseCommand
{
    protected $group       = 'ModalCalc';
    protected $name        = 'user:create';
    protected $description = 'Buat akun pengguna baru';
    protected $usage       = 'user:create <nama> <email> <password>';

    public function run(array $params)
    {
        $name     = $params[0] ?? CLI::prompt('Nama');
        $email    = $params[1] ?? CLI::prompt('Email');
        $password = $params[2] ?? CLI::prompt('Password', null, 'required');

        $model = new UserModel();

        if (! $model->insert([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
        ])) {
            foreach ($model->errors() as $error) {
                CLI::error($error);
            }

            return EXIT_ERROR;
        }

        CLI::write('Akun berhasil dibuat.', 'green');

        return EXIT_SUCCESS;
    }
}
