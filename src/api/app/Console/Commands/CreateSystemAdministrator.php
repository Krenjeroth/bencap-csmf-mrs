<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the first System Administrator account.
 *
 * The password is never read from a file or environment variable: it is
 * typed at a hidden prompt, or passed with --password for scripted setups
 * (the value then lives only in that shell's history, so prefer the prompt).
 */
class CreateSystemAdministrator extends Command
{
    protected $signature = 'csmf:create-sysadmin
        {--name= : Full name (defaults to SYSADMIN_NAME)}
        {--email= : Login email (defaults to SYSADMIN_EMAIL)}
        {--password= : Password; omit to be prompted without echo}';

    protected $description = 'Create the first System Administrator account';

    public function handle(): int
    {
        $this->callSilently('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        $role = Role::where('title', Role::SYSTEM_ADMINISTRATOR)->firstOrFail();

        $existing = $role->users()->where('is_active', true)->count();
        if ($existing > 0) {
            $this->error("An active System Administrator already exists ({$existing}). Add more from the Users page instead.");

            return self::FAILURE;
        }

        $name = $this->option('name') ?: (config('csmf.sysadmin.name') ?: $this->ask('Full name'));
        $email = $this->option('email') ?: (config('csmf.sysadmin.email') ?: $this->ask('Email'));
        $password = $this->option('password') ?: $this->secret('Password (min 12 chars, upper and lower case, a number and a symbol)');

        if (! $this->option('password')) {
            $confirmation = $this->secret('Confirm password');
            if (! hash_equals((string) $password, (string) $confirmation)) {
                $this->error('The passwords do not match.');

                return self::FAILURE;
            }
        }

        $validator = Validator::make(
            ['name' => $name, 'email' => Str::lower((string) $email), 'password' => $password],
            [
                'name' => ['required', 'string', 'max:150'],
                'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', Password::default()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $data = $validator->validated();

        $user = DB::transaction(function () use ($data, $role) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                // They chose this password themselves.
                'must_change_password' => false,
                'is_active' => true,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->roles()->attach($role);

            return $user;
        });

        $this->info("System Administrator created: {$user->email}");
        $this->line('On first sign-in you will be asked to turn on two-factor login.');

        return self::SUCCESS;
    }
}
