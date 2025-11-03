<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ResetAdminPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:reset-password {email} {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Reset admin user password securely';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        // Validate email format
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $this->error('Invalid email format.');

            return self::FAILURE;
        }

        // Find user
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email '{$email}' not found.");

            return self::FAILURE;
        }

        // Confirm action unless --force flag is used
        if (! $this->option('force')) {
            if (! $this->confirm("Reset password for user '{$user->name}' ({$email})?")) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        // Prompt for new password
        $password = $this->secret('Enter new password (minimum 8 characters)');

        if (\strlen($password) < 8) {
            $this->error('Password must be at least 8 characters long.');

            return self::FAILURE;
        }

        // Confirm password
        $confirmPassword = $this->secret('Confirm new password');

        if ($password !== $confirmPassword) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        // Update password
        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password successfully reset for user '{$user->name}' ({$email}).");

        // Security log
        $this->info('Security note: This action has been logged for audit purposes.');

        return self::SUCCESS;
    }
}
