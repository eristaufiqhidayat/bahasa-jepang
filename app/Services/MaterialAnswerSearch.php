<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\MaterialAnswer;

class MaterialAnswerSearch
{
    public function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    public function search(string $question, string $level, ?int $lessonId): array
    {
        $query = $this->normalize($question);
        $stop = ['apa', 'apakah', 'itu', 'yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'saya', 'tolong', 'jelaskan', 'bagaimana', 'cara', 'fungsi', 'penggunaan', 'arti', 'makna', 'berarti', 'partikel', 'dalam', 'bahasa', 'jepang', 'ya'];
        $tokens = array_values(array_diff(explode(' ', $query), $stop));
        $pool = MaterialAnswer::visible()->whereIn('level', array_unique(['foundation', $level]))->when($lessonId, fn ($q) => $q->where(fn ($q) => $q->where('lesson_id', $lessonId)->orWhereNull('lesson_id')))->get();
        $ranked = $pool->map(function ($answer) use ($query, $tokens) {
            $canonical = $this->normalize($answer->question);
            $score = $query === $canonical ? 100 : 0;
            foreach ($answer->keywords as $keyword) {
                $kw = $this->normalize($keyword);
                if ($kw !== '' && preg_match('/(?<![\p{L}\p{N}])'.preg_quote($kw, '/').'(?![\p{L}\p{N}])/u', $query)) {
                    $score = max($score, 35 + min(20, mb_strlen($kw)));
                }
            }
            $hay = $canonical.' '.$this->normalize(implode(' ', $answer->keywords));
            $hits = count(array_filter($tokens, fn ($t) => mb_strlen($t) > 1 && preg_match('/(?<![\p{L}\p{N}])'.preg_quote($t, '/').'(?![\p{L}\p{N}])/u', $hay)));
            if (count($tokens) > 0 && $hits === count($tokens)) {
                $score = max($score, 30 + $hits * 3);
            }

            return ['answer' => $answer, 'score' => $score];
        })->filter(fn ($r) => $r['score'] > 0)->sortByDesc('score')->values();
        $best = $ranked->first();
        $next = $ranked->get(1);
        // Ambiguous matches become suggestions; never combine unrelated answers.
        $matched = $best && $best['score'] >= 35 && (! $next || $best['score'] - $next['score'] >= 3 || $best['score'] === 100);
        $suggestions = $ranked->take(3)->map(fn ($r) => ['question' => $r['answer']->question, 'lesson_id' => $r['answer']->lesson_id])->all();
        if ($matched) {
            $a = $best['answer'];

            return ['answer_id' => $a->id, 'matched' => true, 'answer' => $a->answer, 'source' => ['label' => $a->source_label, 'lesson_id' => $a->lesson_id], 'review_status' => $a->review_status, 'suggestions' => $suggestions];
        }
        $lessons = Lesson::where('status', 'published')->whereIn('level', [$level])->when($lessonId, fn ($q) => $q->whereKey($lessonId))->orderBy('position')->take(3)->get(['id', 'title']);

        return ['answer_id' => null, 'matched' => false, 'answer' => 'Jawaban yang cocok belum ditemukan. Pilih pertanyaan terkait atau buka materi. Pertanyaanmu disimpan agar pengajar dapat melengkapi bank jawaban.', 'source' => null, 'suggestions' => $suggestions, 'related_lessons' => $lessons->map(fn ($l) => ['lesson_id' => $l->id, 'title' => $l->title])->all()];
    }
}
