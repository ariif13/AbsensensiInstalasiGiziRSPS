<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Delete old demo user
        User::where('email', 'admin.demo@pandanteknik.com')->forceDelete();

        // Create new Demo Admin
        User::firstOrCreate(
            ['email' => 'admin123@paspapan.com'],
            [
                'nip' => 'ADM001',
                'name' => 'Demo Admin',
                'password' => Hash::make('12345678'),
                'group' => 'admin',
                'email_verified_at' => now(),
                'phone' => '081234567801',
                'gender' => 'male',
                'address' => 'Demo Address Admin',
                'city' => 'Jakarta',
                'language' => 'id',
            ]
        );

        // Create new Demo User
        User::firstOrCreate(
            ['email' => 'user123@paspapan.com'],
            [
                'nip' => 'USR001',
                'name' => 'Demo User',
                'password' => Hash::make('12345678'),
                'group' => 'user',
                'email_verified_at' => now(),
                'phone' => '081234567802',
                'gender' => 'female',
                'address' => 'Demo Address User',
                'city' => 'Jakarta',
                'language' => 'id',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::whereIn('email', ['admin123@paspapan.com', 'user123@paspapan.com'])->forceDelete();
    }
};
