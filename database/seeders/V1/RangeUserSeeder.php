<?php

namespace Database\Seeders\V1;

use App\Models\V1\Range;
use App\Models\V1\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RangeUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Desactivar revisión de claves foráneas para mejor performance
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('range_user')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $users = User::all();
        $ranges = Range::all()->sortBy('min_range');

        // Si no hay usuarios o rangos, crearlos
        if ($users->isEmpty()) {
            $users = User::factory()->count(10)->create();
        }

        if ($ranges->isEmpty()) {
            $this->call(RangeSeeder::class);
            $ranges = Range::all()->sortBy('min_range');
        }

        // Asignar rangos progresivos a los usuarios según su antigüedad
        $users->each(function ($user, $index) use ($ranges) {
            // Determinar cuántos rangos asignar (1-3)
            $rangesToAssign = min(3, max(1, $ranges->count() - $index));

            // Tomar los primeros rangos disponibles
            $userRanges = $ranges->take($rangesToAssign);

            // Asignar rangos al usuario
            $userRanges->each(function ($range) use ($user) {
                $user->ranges()->syncWithoutDetaching([$range->id]);
            });
        });

        // Asignar algunos rangos aleatorios adicionales
        for ($i = 0; $i < 10; $i++) {
            $user = $users->random();
            $range = $ranges->random();

            // Usar syncWithoutDetaching para evitar duplicados
            $user->ranges()->syncWithoutDetaching([$range->id]);
        }
    }
}
