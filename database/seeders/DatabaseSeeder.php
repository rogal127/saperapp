<?php

namespace Database\Seeders;

use App\Models\Finding;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $mainUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $otherUsers = User::factory(9)->create();
        $allUsers = $otherUsers->prepend($mainUser);

        Finding::factory(120)->recycle($allUsers)->create();
    }
}
