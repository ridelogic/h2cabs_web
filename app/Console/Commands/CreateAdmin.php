<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates (or promotes) an admin account for the dashboard. There is no
 * public registration; this command is the only way to grant admin access.
 */
class CreateAdmin extends Command
{
    protected $signature = 'h2cabs:create-admin {email : Admin email address} {--name= : Display name}';

    protected $description = 'Create an H2 CABS admin user, or promote an existing user to admin';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $name = $this->option('name') ?: $this->ask('Name', 'Administrator');
        $password = $this->secret('Password (min. 10 characters)');
        $confirm = $this->secret('Confirm password');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password, 'password_confirmation' => $confirm],
            [
                'email' => ['required', 'email', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', Password::min(10)],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->password = $password;
        $user->is_admin = true;
        $user->save();

        $this->info(($user->wasRecentlyCreated ? 'Created' : 'Updated').' admin account for '.$email.'.');

        return self::SUCCESS;
    }
}
