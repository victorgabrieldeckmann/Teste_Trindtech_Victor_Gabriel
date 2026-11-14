<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public const EMAIL = 'demo@trindtech.test';

    public const PASSWORD = 'Trindtech@2026';

    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Usuário de demonstração',
                'password' => Hash::make(self::PASSWORD),
            ],
        );
    }
}
