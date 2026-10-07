<?php

namespace Database\Seeders;

use App\Models\CourseTrack;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\VirtualTestTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JlptCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $reference = json_decode(file_get_contents(database_path('data/japantest-minna-reference.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($reference) {
            $this->call(MockupMaterialSeeder::class);
            $tracks = [
                ['foundation', 'Fondasi', 'Kenali hiragana, katakana, bunyi, salam, angka, dan kosakata Haru.', ['Huruf & bunyi', 'Ungkapan sehari-hari', 'Kosakata pemula'], 'available'],
                ['N5', 'N5 · Pemula', 'Bangun kalimat sederhana dan pahami percakapan sehari-hari yang disampaikan pelan.', ['Kalimat dasar', 'Bacaan pendek', 'Percakapan sederhana'], 'partial'],
                ['N4', 'N4 · Dasar', 'Perluas konjugasi, pola kalimat, dan pemahaman konteks keseharian.', ['Konjugasi', 'Bacaan keseharian', 'Menyimak detail'], 'partial'],
                ['N3', 'N3 · Menengah', 'Jembatan menuju bahasa Jepang alami dan gagasan utama dalam informasi keseharian.', ['Pola menengah', 'Gagasan utama', 'Inti percakapan'], 'planned'],
                ['N2', 'N2 · Lanjutan', 'Pahami argumentasi dan percakapan dalam konteks yang lebih luas.', ['Nuansa & penggunaan', 'Argumen bacaan', 'Menyimak terpadu'], 'planned'],
                ['N1', 'N1 · Mahir', 'Baca teks kompleks dan abstrak, tangkap maksud penulis dan pembicara.', ['Nuansa lanjutan', 'Editorial', 'Intensi & logika'], 'planned'],
                ['pro', 'Profesional', 'Kembangkan bahasa bisnis, presentasi, menulis, dan penerjemahan setelah jalur N1.', ['Keigo & presentasi', 'Email bisnis', 'Diskusi & penerjemahan'], 'planned'],
            ];
            foreach ($tracks as $i => [$code, $name, $description, $focus, $status]) {
                CourseTrack::firstOrCreate(['code' => $code], compact('name', 'description', 'focus', 'status') + ['position' => $i + 1]);
            }
            $lessons = [];
            foreach ($reference['lessons'] as $row) {
                $lessons[$row['id']] = Lesson::firstOrCreate(['reference_id' => $row['id']], [
                    'slug' => 'minna-'.$row['chapter'], 'title' => 'Bab '.$row['chapter'].' · '.$row['title'],
                    'summary' => $row['objective'], 'content' => $row['summary_id'], 'japanese' => $row['example_ja'], 'translation' => $row['example_id'],
                    'level' => $row['suggested_jlpt_level'], 'chapter' => $row['chapter'], 'patterns' => $row['patterns'], 'source_reference' => $row['source_reference'],
                    'review_status' => $row['review_status'], 'position' => 100 + $row['chapter'], 'duration_minutes' => 10, 'status' => 'published',
                ]);
            }
            foreach ($reference['questions'] as $i => $row) {
                $lesson = $lessons[$row['lesson_ids'][0]];
                Question::firstOrCreate(['reference_id' => $row['id']], [
                    'lesson_id' => $lesson->id, 'prompt' => str_replace('\\u3000', '　', $row['stem_ja']), 'type' => $row['requires_audio'] ? 'listening' : ($row['skill'] === 'grammar' ? 'grammar' : ($row['skill'] === 'reading' ? 'reading' : 'meaning')),
                    'options' => $row['choices'], 'correct_index' => $row['correct_index'], 'explanation' => $row['explanation_id'],
                    'level' => $row['level'], 'section' => $row['section'], 'skill' => $row['skill'], 'item_type' => $row['item_type'],
                    'source_reference' => $row['source_reference'], 'review_status' => $row['review_status'], 'audio_script' => $row['audio_script_ja'] ?? null, 'position' => $i + 1,
                ]);
            }
            foreach (['N5', 'N4'] as $level) {
                $pool = Question::where('level', $level)->whereNotNull('reference_id')->where('type', '!=', 'listening')->whereHas('lesson', fn ($q) => $q->where('status', 'published'))->orderBy('id')->get();
                $selected = collect();
                foreach (['vocabulary' => 3, 'reading' => 2, 'grammar' => 5] as $skill => $count) {
                    $selected = $selected->concat($pool->where('skill', $skill)->take($count));
                }
                $selected = $selected->concat($pool->whereNotIn('id', $selected->pluck('id'))->take(10 - $selected->count()));
                VirtualTestTemplate::firstOrCreate(['slug' => 'mini-'.strtolower($level)], [
                    'title' => 'Mini virtual test '.$level, 'level' => $level, 'mode' => 'mini', 'status' => 'published',
                    'description' => '10 soal orisinal · 8 menit · kosakata, tata bahasa, dan membaca. Materi awal masih memerlukan tinjauan pengajar; paket ini bukan simulasi JLPT penuh.',
                    'duration_seconds' => 480, 'sections' => [['id' => 'mini', 'name_id' => 'Latihan campuran', 'minutes' => 8]], 'question_ids' => $selected->pluck('id')->values()->all(),
                ]);
            }
            $times = ['N5' => [20, 40, 30], 'N4' => [25, 55, 35], 'N3' => [30, 70, 40], 'N2' => [105, 50], 'N1' => [110, 55]];
            $pass = ['N5' => 80, 'N4' => 90, 'N3' => 95, 'N2' => 90, 'N1' => 100];
            foreach ($times as $level => $minutes) {
                $sections = $reference['virtual_test_blueprints'][$level]['sections'] ?? array_map(fn ($i, $m) => ['id' => count($minutes) === 2 ? ['language_reading', 'listening'][$i] : ['vocabulary', 'grammar_reading', 'listening'][$i], 'name_id' => count($minutes) === 2 ? ['Kosakata, tata bahasa & membaca', 'Menyimak'][$i] : ['Kosakata', 'Tata bahasa & membaca', 'Menyimak'][$i], 'minutes' => $m], array_keys($minutes), $minutes);
                VirtualTestTemplate::firstOrCreate(['slug' => 'jlpt-'.strtolower($level)], [
                    'title' => 'Rancangan simulasi JLPT '.$level, 'level' => $level, 'mode' => 'blueprint', 'status' => 'draft',
                    'description' => 'Struktur waktu mengacu JLPT. Paket penuh belum tersedia: bank soal bertinjau dan rekaman menyimak perlu dilengkapi.',
                    'duration_seconds' => array_sum($minutes) * 60, 'sections' => $sections, 'question_ids' => [],
                    'official_score_reference' => ['total' => 180, 'overall_pass_mark' => $pass[$level], 'sectional_note' => in_array($level, ['N5', 'N4']) ? 'Bahasa & membaca minimal 38/120; menyimak minimal 19/60.' : 'Setiap bagian penilaian minimal 19/60.'],
                ]);
            }
        });
    }
}
