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
        $panini = Publisher::firstOrCreate(['name' => 'Panini']);
        $jbc = Publisher::firstOrCreate(['name' => 'JBC']);

        $shonen = Category::firstOrCreate(['slug' => 'shonen'], ['name' => 'Shōnen']);
        $adventure = Category::firstOrCreate(['slug' => 'aventura'], ['name' => 'Aventura']);
        $action = Category::firstOrCreate(['slug' => 'acao'], ['name' => 'Ação']);
        $suspense = Category::firstOrCreate(['slug' => 'suspense'], ['name' => 'Suspense']);

        $oda = Author::firstOrCreate(['name' => 'Eiichiro Oda']);
        $kishimoto = Author::firstOrCreate(['name' => 'Masashi Kishimoto']);
        $ohba = Author::firstOrCreate(['name' => 'Tsugumi Ohba']);
        $obata = Author::firstOrCreate(['name' => 'Takeshi Obata']);

        $onePiece = Series::firstOrCreate(
            ['slug' => 'one-piece'],
            [
                'publisher_id' => $panini->id,
                'title' => 'One Piece',
                'description' => 'Luffy parte em busca de um tesouro lendário e reúne uma tripulação de piratas.',
                'status' => 'ongoing',
            ],
        );
        $naruto = Series::firstOrCreate(
            ['slug' => 'naruto'],
            [
                'publisher_id' => $panini->id,
                'title' => 'Naruto',
                'description' => 'Um jovem ninja busca reconhecimento e sonha em liderar sua vila.',
                'status' => 'completed',
            ],
        );
        $deathNote = Series::firstOrCreate(
            ['slug' => 'death-note'],
            [
                'publisher_id' => $jbc->id,
                'title' => 'Death Note',
                'description' => 'Um estudante encontra um caderno capaz de matar pessoas e passa a ser perseguido por um detetive.',
                'status' => 'completed',
            ],
        );

        $onePiece->authors()->syncWithoutDetaching([$oda->id]);
        $onePiece->categories()->syncWithoutDetaching([$shonen->id, $adventure->id]);
        $naruto->authors()->syncWithoutDetaching([$kishimoto->id]);
        $naruto->categories()->syncWithoutDetaching([$shonen->id, $action->id]);
        $deathNote->authors()->syncWithoutDetaching([$ohba->id, $obata->id]);
        $deathNote->categories()->syncWithoutDetaching([$suspense->id]);

        $catalog = [
            ['series' => $onePiece, 'price' => '39.90', 'stock' => 12],
            ['series' => $naruto, 'price' => '42.90', 'stock' => 8],
            ['series' => $deathNote, 'price' => '36.90', 'stock' => 10],
        ];

        foreach ($catalog as $item) {
            for ($volumeNumber = 1; $volumeNumber <= 10; $volumeNumber++) {
                $series = $item['series'];
                $stock = $series->id === $deathNote->id && $volumeNumber === 7 ? 0 : $item['stock'];
                $isActive = ! ($series->id === $naruto->id && $volumeNumber === 10);

                Manga::firstOrCreate(
                    ['series_id' => $series->id, 'volume_number' => $volumeNumber],
                    [
                        'description' => "Volume {$volumeNumber} de {$series->title}.",
                        'price' => $item['price'],
                        'stock' => $stock,
                        'is_active' => $isActive,
                    ],
                );
            }
        }
    }
}
