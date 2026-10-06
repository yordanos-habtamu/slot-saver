<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slotsaver:make-admin
                            {email : The admin email address}
                            {--name= : The admin name}
                            {--password= : The admin password, prompted for when omitted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or promote a platform admin account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = (string) ($this->option('name') ?: $this->ask('Name'));
        $password = (string) ($this->option('password') ?: $this->secret('Password'));

        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            if (! $this->confirm("{$email} already exists. Promote them to admin?", default: false)) {
                $this->components->warn('Nothing was changed.');

                return self::SUCCESS;
            }

            $existing->role = UserRole::Admin;
            $existing->save();

            $this->components->info("{$email} is now an admin.");

            return self::SUCCESS;
        }

        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $user->role = UserRole::Admin;
        $user->email_verified_at = now();
        $user->save();

        $this->components->info("Admin {$user->email} created.");

        return self::SUCCESS;
    }
}
