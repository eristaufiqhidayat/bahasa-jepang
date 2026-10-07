<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

Artisan::command('app:create-admin {email} {--name=Administrator}', function () {
    $email = $this->argument('email');
    $password = $this->secret('Kata sandi admin (minimal 12 karakter)');
    $v = Validator::make(['email' => $email, 'password' => $password], ['email' => 'required|email|unique:users,email', 'password' => 'required|string|min:12']);
    if ($v->fails()) {
        foreach ($v->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }
    $user = new User;
    $user->name = $this->option('name');
    $user->email = $email;
    $user->password = Hash::make($password);
    $user->is_admin = true;
    $user->save();
    $this->info('Admin berhasil dibuat.');
})->purpose('Membuat admin tanpa kata sandi bawaan');
