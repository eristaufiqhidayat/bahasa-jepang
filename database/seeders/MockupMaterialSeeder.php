<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\Vocabulary;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MockupMaterialSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->call(BeginnerMaterialSeeder::class);
            $lessons = Lesson::whereIn('slug', ['salam', 'perkenalan', 'minuman'])->get()->keyBy('slug');
            $words = [
                ['ありがとう', 'Arigatō', 'Terima kasih', 'Sapaan', 'salam'],
                ['おはようございます', 'Ohayō gozaimasu', 'Selamat pagi', 'Sapaan', 'salam'],
                ['みず', 'Mizu', 'Air', 'Minuman', 'minuman'],
                ['コーヒー', 'Kōhī', 'Kopi', 'Minuman', 'minuman'],
                ['おちゃ', 'Ocha', 'Teh', 'Minuman', 'minuman'],
                ['ともだち', 'Tomodachi', 'Teman', 'Orang', 'perkenalan'],
                ['せんせい', 'Sensei', 'Guru', 'Orang', 'perkenalan'],
                ['がっこう', 'Gakkō', 'Sekolah', 'Tempat', 'perkenalan'],
                ['えき', 'Eki', 'Stasiun', 'Tempat', 'perkenalan'],
            ];
            foreach ($words as [$japanese,$romaji,$meaning,$category,$slug]) {
                $lesson = $lessons[$slug];
                $word = Vocabulary::firstOrCreate(['japanese' => $japanese], ['lesson_id' => $lesson->id, 'romaji' => $romaji, 'meaning' => $meaning, 'category' => $category]);
                // Convert the original sample categories only; keep administrator edits/audio.
                if ($word->category === $lesson->title) {
                    $word->update(['category' => $category]);
                }
            }
            $questions = [
                ['おはようございます', ['Selamat malam', 'Selamat pagi', 'Terima kasih'], 1, 'おはようございます adalah sapaan pagi yang sopan.', 'salam', 'meaning'],
                ['あ', ['a', 'i', 'u'], 0, 'Huruf hiragana あ dibaca a.', 'salam', 'reading'],
                ['コーヒー', ['Teh', 'Air', 'Kopi'], 2, 'コーヒー (kōhī) berarti kopi. Tanda ー menunjukkan bunyi panjang.', 'minuman', 'meaning'],
                ['わたしはアディです', ['Saya Adi', 'Adi adalah guru', 'Salam kenal'], 0, 'わたしはアディです berarti Saya Adi. Partikel は dibaca wa.', 'perkenalan', 'meaning'],
                ['ありがとう', ['Selamat siang', 'Terima kasih', 'Sampai besok'], 1, 'ありがとう berarti terima kasih. Bentuk yang lebih sopan: ありがとうございます.', 'salam', 'meaning'],
            ];
            foreach ($questions as $i => [$prompt,$options,$correct,$explanation,$slug,$type]) {
                Question::firstOrCreate(['lesson_id' => $lessons[$slug]->id, 'prompt' => $prompt], ['type' => $type, 'options' => $options, 'correct_index' => $correct, 'explanation' => $explanation, 'position' => 100 + $i]);
            }
        });
    }
}
