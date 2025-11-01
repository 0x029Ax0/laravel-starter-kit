<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->delete();

        $test = User::create([
            'name' => 'Test Account',
            'email' => 'test@test.nl',
            'email_verified_at' => now(),
            'password' => bcrypt('engeland'),
            'avatar_url' => 'storage/images/avatars/default.jpg',
        ]);
    }
}
