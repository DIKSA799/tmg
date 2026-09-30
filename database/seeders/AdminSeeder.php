<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['username' => config('admin.user.username')],
            [
                'name' => config('admin.user.name'),
                'email' => config('admin.user.email'),
                'password' => Hash::make(config('admin.user.password')),
            ],
        );
    }
}
