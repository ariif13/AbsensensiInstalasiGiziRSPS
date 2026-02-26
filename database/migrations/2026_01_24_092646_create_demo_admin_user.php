<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \App\Models\User::firstOrCreate([
            'email' => 'admin.demo@pandanteknik.com',
        ], [
            'nip' => 'ADMDEMO001',
            'name' => 'Demo Admin',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'group' => 'admin',
            'email_verified_at' => now(),
            'phone' => '081234567890',
            'gender' => 'male',
            'address' => 'Demo Address, Jakarta',
            'city' => 'Jakarta',
            'language' => 'id',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\User::where('email', 'admin.demo@pandanteknik.com')->delete();
    }
};
