<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\MaterialAnswer;
use App\Models\Vocabulary;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialChatSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(file_get_contents(database_path('data/material-chat-answers.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $chapter = $row['chapter'];
                unset($row['chapter']);
                $lesson = $chapter ? Lesson::where('chapter', $chapter)->whereNotNull('reference_id')->first() : null;
                if ($chapter && ! $lesson) {
                    throw new \RuntimeException('Jalankan JlptCurriculumSeeder terlebih dahulu.');
                }
                MaterialAnswer::firstOrCreate(['slug' => $row['slug']], $row + [
                    'lesson_id' => $lesson?->id, 'status' => 'published', 'review_status' => 'needs_japanese_teacher_review',
                ]);
            }
            foreach (Vocabulary::with('lesson')->whereHas('lesson', fn ($q) => $q->where('status', 'published'))->get() as $word) {
                $answer = $word->japanese.' ('.$word->romaji.') berarti '.$word->meaning.'.';
                if ($word->reading) {
                    $answer .= "\nBacaan: ".$word->reading;
                }
                if ($word->example) {
                    $answer .= "\nContoh: ".$word->example."\n".$word->example_translation;
                }
                MaterialAnswer::firstOrCreate(['slug' => 'kosakata-'.$word->id], [
                    'question' => 'Apa arti '.$word->japanese.' ('.$word->romaji.')?', 'answer' => $answer,
                    'keywords' => array_values(array_unique(array_filter([$word->japanese, $word->reading, $word->romaji, strtr(mb_strtolower($word->romaji ?? ''), ['ā' => 'aa', 'ī' => 'ii', 'ū' => 'uu', 'ē' => 'ee', 'ō' => 'oo']), strtr(mb_strtolower($word->romaji ?? ''), ['ā' => 'a', 'ī' => 'i', 'ū' => 'u', 'ē' => 'e', 'ō' => 'ou']), $word->meaning]))),
                    'level' => 'foundation', 'lesson_id' => $word->lesson_id,
                    'source_label' => 'Japantest · Kosakata · '.$word->lesson->title,
                    'status' => 'published', 'review_status' => 'needs_japanese_teacher_review',
                ]);
            }
        });
    }
}
