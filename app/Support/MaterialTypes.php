<?php

namespace App\Support;

use App\Models\Character;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Vocabulary;

class MaterialTypes
{
    public static function all(): array
    {
        return [
            'lessons' => ['model' => Lesson::class, 'label' => 'Pelajaran', 'fields' => ['title' => 'Judul', 'slug' => 'Slug unik', 'summary' => 'Ringkasan', 'content' => 'Isi materi (penjelasan Indonesia)', 'japanese' => 'Contoh Jepang', 'romaji' => 'Romaji', 'translation' => 'Arti Indonesia', 'position' => 'Urutan', 'duration_minutes' => 'Durasi (menit)', 'status' => 'Status']],
            'vocabularies' => ['model' => Vocabulary::class, 'label' => 'Kosakata', 'fields' => ['lesson_id' => 'Pelajaran', 'japanese' => 'Kata Jepang', 'reading' => 'Cara baca / furigana', 'romaji' => 'Romaji', 'meaning' => 'Arti Indonesia', 'category' => 'Kategori', 'example' => 'Contoh kalimat Jepang', 'example_translation' => 'Arti contoh']],
            'characters' => ['model' => Character::class, 'label' => 'Huruf Jepang', 'fields' => ['script' => 'Jenis huruf', 'symbol' => 'Huruf', 'romaji' => 'Romaji', 'group' => 'Kelompok', 'position' => 'Urutan']],
            'questions' => ['model' => Question::class, 'label' => 'Soal latihan', 'fields' => ['lesson_id' => 'Pelajaran', 'prompt' => 'Pertanyaan', 'type' => 'Jenis soal', 'option_0' => 'Pilihan A', 'option_1' => 'Pilihan B', 'option_2' => 'Pilihan C', 'option_3' => 'Pilihan D (opsional)', 'correct_index' => 'Jawaban benar', 'explanation' => 'Pembahasan', 'position' => 'Urutan']],
        ];
    }

    public static function get(string $type): array
    {
        abort_unless(isset(self::all()[$type]), 404);

        return self::all()[$type];
    }
}
