<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Category;
use App\Models\Manga;
use App\Models\Publisher;
use App\Models\Series;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Sample data for development; these titles and people are fictional.
        $publisher = Publisher::firstOrCreate(['name' => 'Akuma Press']);

        $adventure = Category::firstOrCreate(
            ['slug' => 'aventura'],
            ['name' => 'Aventura'],
        );
        $fantasy = Category::firstOrCreate(
            ['slug' => 'fantasia'],
            ['name' => 'Fantasia'],
        );
        $action = Category::firstOrCreate(
            ['slug' => 'acao'],
            ['name' => 'Ação'],
        );

        $aiko = Author::firstOrCreate(
            ['name' => 'Aiko Tanaka'],
            ['biography' => 'Autora fictícia criada para os dados de demonstração.'],
        );
        $ren = Author::firstOrCreate(
            ['name' => 'Ren Sato'],
            ['biography' => 'Autor fictício criado para os dados de demonstração.'],
        );

        $fireChronicles = Series::firstOrCreate(
            ['slug' => 'cronicas-da-chama'],
            [
                'publisher_id' => $publisher->id,
                'title' => 'As Crônicas da Chama',
                'description' => 'Uma jovem cartógrafa parte em busca da origem de uma chama misteriosa.',
                'status' => 'ongoing',
            ],
        );
        $tomorrowIslands = Series::firstOrCreate(
            ['slug' => 'ilhas-do-amanha'],
            [
                'publisher_id' => $publisher->id,
                'title' => 'Ilhas do Amanhã',
                'description' => 'Dois irmãos exploram ilhas que surgem e desaparecem no oceano.',
                'status' => 'ongoing',
            ],
        );

        $fireChronicles->authors()->syncWithoutDetaching([$aiko->id]);
        $fireChronicles->categories()->syncWithoutDetaching([$adventure->id, $fantasy->id]);
        $tomorrowIslands->authors()->syncWithoutDetaching([$ren->id]);
        $tomorrowIslands->categories()->syncWithoutDetaching([$adventure->id, $action->id]);

        $volumes = [
            [
                'series_id' => $fireChronicles->id,
                'volume_number' => 1,
                'title' => 'O Mapa das Cinzas',
                'description' => 'O primeiro passo de uma jornada que mudará o reino.',
                'price' => '39.90',
                'stock' => 12,
                'published_at' => '2026-01-15',
            ],
            [
                'series_id' => $fireChronicles->id,
                'volume_number' => 2,
                'title' => 'A Floresta Suspensa',
                'description' => 'Uma pista leva a equipe até uma floresta acima das nuvens.',
                'price' => '42.90',
                'stock' => 8,
                'published_at' => '2026-03-10',
            ],
            [
                'series_id' => $tomorrowIslands->id,
                'volume_number' => 1,
                'title' => 'A Ilha que Desaparece',
                'description' => 'Os irmãos encontram um mapa para uma ilha que não existe em nenhuma carta.',
                'price' => '36.90',
                'stock' => 15,
                'published_at' => '2026-02-20',
            ],
            [
                'series_id' => $tomorrowIslands->id,
                'volume_number' => 2,
                'title' => 'O Farol do Norte',
                'description' => 'Uma luz distante revela o próximo destino da viagem.',
                'price' => '38.50',
                'stock' => 6,
                'published_at' => '2026-04-05',
            ],
        ];

        foreach ([$fireChronicles->id, $tomorrowIslands->id] as $seriesId) {
            for ($volumeNumber = 3; $volumeNumber <= 15; $volumeNumber++) {
                $stock = 4 + (($volumeNumber * 3 + $seriesId) % 12);

                if ($seriesId === $fireChronicles->id && $volumeNumber === 7) {
                    $stock = 0;
                }

                $volumes[] = [
                    'series_id' => $seriesId,
                    'volume_number' => $volumeNumber,
                    'title' => null,
                    'description' => "Continuação da jornada no volume {$volumeNumber}.",
                    'price' => number_format(29.90 + (($volumeNumber * 13 + $seriesId) % 2400) / 100, 2, '.', ''),
                    'stock' => $stock,
                    'published_at' => sprintf('2025-%02d-01', (($volumeNumber - 1) % 12) + 1),
                    'is_active' => ! ($seriesId === $tomorrowIslands->id && $volumeNumber === 15),
                ];
            }
        }

        foreach ($volumes as $volume) {
            $seriesId = $volume['series_id'];
            $volumeNumber = $volume['volume_number'];

            $attributes = $volume + ['is_active' => true];

            if ($volumeNumber <= 2) {
                Manga::updateOrCreate(
                    ['series_id' => $seriesId, 'volume_number' => $volumeNumber],
                    $attributes,
                );
            } else {
                Manga::firstOrCreate(
                    ['series_id' => $seriesId, 'volume_number' => $volumeNumber],
                    $attributes,
                );
            }
        }
    }
}
