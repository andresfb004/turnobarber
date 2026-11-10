<?php

namespace Database\Seeders;

use App\Models\Profesional;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    /**
     * El dueño (barbero) y su esposa (belleza) administran todo; el bot solo usa la API.
     * Los nombres son provisionales hasta tener los reales.
     */
    public function run(): void
    {
        $adminPassword = env('SEED_ADMIN_PASSWORD', 'password');

        $barbero = User::factory()->conRol(Role::ADMINISTRADOR)->create([
            'name' => 'Barbero',
            'email' => 'barbero@turnobarber.test',
            'password' => $adminPassword,
        ]);

        $estilista = User::factory()->conRol(Role::ADMINISTRADOR)->create([
            'name' => 'Estilista',
            'email' => 'estilista@turnobarber.test',
            'password' => $adminPassword,
        ]);

        Profesional::create([
            'user_id' => $barbero->id,
            'nombre' => 'Barbero',
            'especialidad' => Profesional::BARBERIA,
        ]);

        Profesional::create([
            'user_id' => $estilista->id,
            'nombre' => 'Estilista',
            'especialidad' => Profesional::BELLEZA,
        ]);

        User::factory()->conRol(Role::BOT)->create([
            'name' => 'Bot de Telegram',
            'email' => env('SEED_BOT_EMAIL', 'bot@turnobarber.test'),
            'password' => env('SEED_BOT_PASSWORD', 'password'),
        ]);
    }
}
