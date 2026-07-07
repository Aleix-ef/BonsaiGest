<?php

namespace Database\Seeders;

use App\Models\Bonsai;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'demo@bonsaigest.test'],
            [
                'name' => 'Aiko Tanaka',
                'password' => 'password',
            ],
        );

        $trees = [
            [
                'name' => 'Kumo',
                'species' => 'Juniperus chinensis',
                'age' => 18,
                'origin' => 'Compra',
                'style' => 'Moyogi',
                'acquired_date' => '2021-03-12',
                'water_level' => 'Media',
                'location' => 'Exterior',
                'substrate' => 'Akadama, kiryuzuna y pomice',
                'fertilizer' => 'Orgánico sólido en primavera y otoño',
                'main_image' => '/images/bonsai-hero.png',
                'description' => 'Junípero de movimiento suave, trabajado poco a poco para conservar un frente sereno y ramas ligeras.',
                'events' => [
                    ['date' => '2026-03-08', 'type' => 'Trasplante', 'notes' => 'Cambio a akadama y kiryu. Raíces finas sanas y buen drenaje.'],
                    ['date' => '2026-04-18', 'type' => 'Pinzado', 'notes' => 'Pinzado ligero para compactar brotes exteriores sin debilitar el interior.'],
                    ['date' => '2026-05-25', 'type' => 'Diseño', 'notes' => 'Revisión de masas verdes y limpieza de madera seca.'],
                ],
                'tasks' => [
                    ['date' => '2026-07-18', 'title' => 'Revisar alambre', 'description' => 'Comprobar marcas en la primera rama y retirar si empieza a morder.'],
                    ['date' => '2026-09-12', 'title' => 'Abonado de otoño', 'description' => 'Reanudar abono orgánico cuando bajen las temperaturas.'],
                ],
                'treatments' => [
                    ['date' => '2026-05-02', 'problem' => 'Araña roja inicial', 'product' => 'Jabón potásico', 'result' => 'Controlado', 'notes' => 'Dos aplicaciones separadas por siete días.'],
                ],
            ],
            [
                'name' => 'Haru',
                'species' => 'Acer palmatum',
                'age' => 11,
                'origin' => 'Esqueje',
                'style' => 'Chokkan',
                'acquired_date' => '2022-10-04',
                'water_level' => 'Alta',
                'location' => 'Exterior',
                'substrate' => 'Akadama con pomice fina',
                'fertilizer' => 'Líquido suave cada quince días',
                'main_image' => '/images/bonsai-hero.png',
                'description' => 'Arce de hoja fina, pensado para una silueta vertical limpia y otoños muy expresivos.',
                'events' => [
                    ['date' => '2026-02-28', 'type' => 'Poda', 'notes' => 'Selección de yemas y reducción de ramas cruzadas.'],
                    ['date' => '2026-04-03', 'type' => 'Abonado', 'notes' => 'Inicio de abonado suave tras apertura completa de hojas.'],
                ],
                'tasks' => [
                    ['date' => '2026-07-10', 'title' => 'Protección del sol', 'description' => 'Mover a semisombra durante ola de calor.'],
                ],
                'treatments' => [],
            ],
            [
                'name' => 'Sora',
                'species' => 'Olea europaea sylvestris',
                'age' => 24,
                'origin' => 'Yamadori',
                'style' => 'Shakan',
                'acquired_date' => '2020-11-21',
                'water_level' => 'Baja',
                'location' => 'Exterior',
                'substrate' => 'Pomice, grava volcánica y akadama',
                'fertilizer' => 'Orgánico de liberación lenta',
                'main_image' => '/images/bonsai-hero.png',
                'description' => 'Acebuche resistente con madera vieja y carácter mediterráneo. El objetivo es compactar la brotación secundaria.',
                'events' => [
                    ['date' => '2026-01-16', 'type' => 'Alambrado', 'notes' => 'Colocación de ramas primarias respetando el movimiento natural.'],
                    ['date' => '2026-06-07', 'type' => 'Poda', 'notes' => 'Reducción fuerte de brotes largos y limpieza interior.'],
                ],
                'tasks' => [
                    ['date' => '2026-08-03', 'title' => 'Tratamiento preventivo', 'description' => 'Revisión de cochinilla y aplicación preventiva si hay señales.'],
                ],
                'treatments' => [
                    ['date' => '2026-04-20', 'problem' => 'Cochinilla aislada', 'product' => 'Aceite de parafina', 'result' => 'Sin recurrencia', 'notes' => 'Retirada manual previa y seguimiento semanal.'],
                ],
            ],
        ];

        foreach ($trees as $treeData) {
            $events = $treeData['events'];
            $tasks = $treeData['tasks'];
            $treatments = $treeData['treatments'];
            unset($treeData['events'], $treeData['tasks'], $treeData['treatments']);

            $bonsai = Bonsai::query()->updateOrCreate(
                ['user_id' => $user->id, 'name' => $treeData['name']],
                [...$treeData, 'user_id' => $user->id],
            );

            $bonsai->images()->updateOrCreate(
                ['date' => $bonsai->acquired_date, 'description' => 'Foto de llegada'],
                ['image' => $bonsai->main_image],
            );

            foreach ($events as $event) {
                $bonsai->events()->updateOrCreate(
                    ['date' => $event['date'], 'type' => $event['type']],
                    ['notes' => $event['notes']],
                );
                $bonsai->images()->updateOrCreate(
                    ['date' => $event['date'], 'description' => $event['type']],
                    ['image' => $bonsai->main_image],
                );
            }

            foreach ($tasks as $task) {
                $user->calendarTasks()->updateOrCreate(
                    ['bonsai_id' => $bonsai->id, 'date' => $task['date'], 'title' => $task['title']],
                    ['description' => $task['description']],
                );
            }

            foreach ($treatments as $treatment) {
                $bonsai->treatments()->updateOrCreate(
                    ['date' => $treatment['date'], 'problem' => $treatment['problem']],
                    [
                        'product' => $treatment['product'],
                        'result' => $treatment['result'],
                        'notes' => $treatment['notes'],
                    ],
                );
            }
        }
    }
}
