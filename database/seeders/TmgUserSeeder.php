<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TmgUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'tmguser'],
            [
                'name' => 'TMG User',
                'email' => 'tmguser@tmg.local',
                'password' => Hash::make('passwd20'),
            ],
        );
    }
}
