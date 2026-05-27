<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PromoteUserRole extends Command
{
    protected $signature = 'user:role {email} {role}';

    protected $description = 'Set the role of a user by email. Allowed roles: admin, manager, clerk.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $role = $this->argument('role');

        $validator = Validator::make(
            ['role' => $role],
            ['role' => [Rule::in([User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_CLERK])]]
        );

        if ($validator->fails()) {
            $this->error('Invalid role. Must be one of: admin, manager, clerk.');

            return self::INVALID;
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("No user found with email {$email}.");

            return self::FAILURE;
        }

        $user->role = $role;
        $user->save();

        $this->info("Set {$user->email} role to {$role}.");

        return self::SUCCESS;
    }
}
