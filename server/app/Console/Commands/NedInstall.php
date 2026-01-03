<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class NedInstall extends Command
{
    protected $signature = 'ned:install';

    protected $description = 'First-run setup for Ned server monitoring';

    public function handle(): int
    {
        $this->newLine();
        $this->info('  ╔═══════════════════════════════════════╗');
        $this->info('  ║           Welcome to Ned              ║');
        $this->info('  ║     Server Monitoring Made Simple     ║');
        $this->info('  ╚═══════════════════════════════════════╝');
        $this->newLine();

        // Check if already set up
        if (User::count() > 0) {
            if (! confirm('Ned is already set up. Do you want to create another admin user?', false)) {
                $this->info('Setup cancelled.');

                return self::SUCCESS;
            }
        }

        // Check environment
        $this->checkEnvironment();

        // Create admin user
        $this->createAdminUser();

        $this->newLine();
        $this->info('  ✓ Ned is ready to go!');
        $this->newLine();
        $this->line('  Next steps:');
        $this->line('  1. Start the server: php artisan serve');
        $this->line('  2. Log in at: '.config('app.url').'/login');
        $this->line('  3. Add your first server from the dashboard');
        $this->newLine();

        return self::SUCCESS;
    }

    private function checkEnvironment(): void
    {
        $this->info('Checking environment...');

        // Check APP_KEY
        if (empty(config('app.key'))) {
            $this->warn('  → Generating application key...');
            $this->call('key:generate');
        } else {
            $this->line('  ✓ Application key set');
        }

        // Check database
        try {
            \DB::connection()->getPdo();
            $this->line('  ✓ Database connected');
        } catch (\Exception $e) {
            $this->error('  ✗ Database connection failed: '.$e->getMessage());
            $this->newLine();
            $this->warn('Please configure your database in .env and try again.');

            exit(1);
        }

        // Run migrations
        $this->info('Running migrations...');
        $this->call('migrate', ['--force' => true]);

        $this->newLine();
    }

    private function createAdminUser(): void
    {
        $this->info('Create admin account:');
        $this->newLine();

        $name = text(
            label: 'Name',
            placeholder: 'Admin',
            required: true
        );

        $email = text(
            label: 'Email',
            placeholder: 'admin@example.com',
            required: true,
            validate: fn (string $value) => filter_var($value, FILTER_VALIDATE_EMAIL)
                ? null
                : 'Please enter a valid email address.'
        );

        $password = password(
            label: 'Password',
            required: true,
            validate: fn (string $value) => strlen($value) >= 8
                ? null
                : 'Password must be at least 8 characters.'
        );

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $this->newLine();
        $this->info("  ✓ Admin user created: {$email}");
    }
}
