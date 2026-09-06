<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('smartflat:create-admin {--email= : Пошта адміністратора} {--name= : Ім\'я адміністратора} {--password= : Пароль; якщо не вказано — буде згенеровано}')]
#[Description('Створює або оновлює користувача з роллю адміністратора.')]
class CreateAdminCommand extends Command
{
    public function handle(): int
    {
        $email = (string) ($this->option('email') ?: $this->ask('Пошта адміністратора'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->components->error('Вкажіть коректну пошту через --email.');

            return self::FAILURE;
        }

        $generated = ! $this->option('password');
        $password = (string) ($this->option('password') ?: Str::password(24));

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = (string) ($this->option('name') ?: $user->name ?: 'Адміністратор');
        $user->password = $password;
        $wasExisting = $user->exists;
        $user->save();

        $user->roles()->syncWithoutDetaching([$this->adminRole()->id]);

        $this->components->info($wasExisting
            ? "Користувача [{$email}] оновлено та призначено адміністратором."
            : "Адміністратора [{$email}] створено.");

        if ($generated) {
            $this->components->warn("Згенерований пароль: {$password}");
        }

        return self::SUCCESS;
    }

    private function adminRole(): Role
    {
        $role = Role::query()->firstWhere('name', Role::ADMIN);

        if ($role instanceof Role) {
            return $role;
        }

        $this->callSilent('db:seed', ['--class' => RoleSeeder::class, '--force' => true]);

        return Role::query()->where('name', Role::ADMIN)->sole();
    }
}
