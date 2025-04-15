<?php

namespace Database\Seeders\V1;

use App\Models\V1\Range;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;

class RangeSeeder extends Seeder
{
    public function run(): void
    {
        $ranges = [
            [
                'name' => 'Iniciado',
                'min_range' => 0,
                'max_range' => 49,
                'description' => '¡Bienvenido a la comunidad! Estás comenzando tu viaje como iniciado.',
                'image_url' => $this->getImageUrl('iniciado.png')
            ],
            [
                'name' => 'Novato',
                'min_range' => 50,
                'max_range' => 199,
                'description' => 'Has recibido tus primeros "Positivos". ¡Sigue participando!',
                'image_url' => $this->getImageUrl('novato.png')
            ],
            [
                'name' => 'Aprendiz',
                'min_range' => 200,
                'max_range' => 499,
                'description' => 'Tus comentarios están siendo valorados. ¡Vas por buen camino!',
                'image_url' => $this->getImageUrl('aprendiz.png')
            ],
            [
                'name' => 'Contribuyente',
                'min_range' => 500,
                'max_range' => 999,
                'description' => 'Eres un miembro activo y valorado en la comunidad.',
                'image_url' => $this->getImageUrl('contribuyente.png')
            ],
            [
                'name' => 'Veterano',
                'min_range' => 1000,
                'max_range' => 2499,
                'description' => 'Tus aportes son reconocidos y respetados por la comunidad.',
                'image_url' => $this->getImageUrl('veterano.png')
            ],
            [
                'name' => 'Experto',
                'min_range' => 2500,
                'max_range' => 4999,
                'description' => 'Eres una voz autorizada en la comunidad. ¡Felicidades!',
                'image_url' => $this->getImageUrl('experto.png')
            ],
            [
                'name' => 'Maestro',
                'min_range' => 5000,
                'max_range' => 9999,
                'description' => 'Tus comentarios son referencia para otros usuarios.',
                'image_url' => $this->getImageUrl('maestro.png')
            ],
            [
                'name' => 'Gran Maestro',
                'min_range' => 10000,
                'max_range' => 24999,
                'description' => 'Eres un pilar de la comunidad. ¡Tu experiencia es invaluable!',
                'image_url' => $this->getImageUrl('gran_maestro.png')
            ],
            [
                'name' => 'Leyenda',
                'min_range' => 25000,
                'max_range' => null,
                'description' => 'Has alcanzado el máximo reconocimiento. ¡Eres una leyenda!',
                'image_url' => $this->getImageUrl('leyenda.png')
            ],
        ];

        foreach ($ranges as $range) {
            Range::create($range);
        }
    }

    /**
     * Genera la URL completa para las imágenes que será consumida por Angular
     */
    protected function getImageUrl(string $filename): string
    {
        // Ruta base de tu API Laravel (debe coincidir con donde están las imágenes)
        $baseUrl = rtrim(config('app.url'), '/');

        // Ruta relativa donde se almacenan los iconos
        $iconsPath = '/assets/icons/';

        // Si estamos en consola (migraciones/seeding) y la URL no está configurada
        if (App::runningInConsole() && $baseUrl === '') {
            return $iconsPath . $filename;
        }

        return $baseUrl . $iconsPath . $filename;
    }
}
