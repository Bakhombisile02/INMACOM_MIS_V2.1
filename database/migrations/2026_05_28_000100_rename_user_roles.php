<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $forward = [
        'clerk_admin' => 'admin',
        'data_manager' => 'manager',
        'data_entry' => 'clerk',
    ];

    public function up(): void
    {
        foreach ($this->forward as $old => $new) {
            if (Schema::hasTable('users')) {
                DB::table('users')->where('role', $old)->update(['role' => $new]);
            }
            if (Schema::hasTable('registration_pins')) {
                DB::table('registration_pins')->where('role', $old)->update(['role' => $new]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->forward as $old => $new) {
            if (Schema::hasTable('users')) {
                DB::table('users')->where('role', $new)->update(['role' => $old]);
            }
            if (Schema::hasTable('registration_pins')) {
                DB::table('registration_pins')->where('role', $new)->update(['role' => $old]);
            }
        }
    }
};
