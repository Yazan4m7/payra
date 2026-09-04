<?php

namespace Database\Seeders;

use App\Models\CentralUser;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('OPERATOR_EMAIL');
        $password = env('OPERATOR_PASSWORD');

        if ($email && $password) {
            CentralUser::on('central')->updateOrCreate(
                ['email' => $email],
                [
                    'name' => env('OPERATOR_NAME', 'SaaS Operator'),
                    'password' => $password,
                    'is_super_admin' => true,
                ]
            );
        }
    }
}
