<?php

namespace App\Services;

use App\Models\Question;
use App\Models\VirtualTestAttempt;
use App\Models\VirtualTestTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VirtualTestService
{
    public function start(VirtualTestTemplate $template, string $owner): VirtualTestAttempt
    {
        abort_unless($template->mode === 'mini' && $template->status === 'published', 409, 'Paket ujian ini belum tersedia.');
        $ids = array_values(array_unique($template->question_ids));
        $questions = Question::whereIn('id', $ids)->whereHas('lesson', fn ($q) => $q->where('status', 'published'))->get()->keyBy('id');
        abort_unless(count($ids) > 0 && $questions->count() === count($ids), 409, 'Soal paket perlu diperbarui oleh pengajar.');
        foreach ($questions as $q) {
            abort_if($q->type === 'listening' && ! $q->audio_path, 409, 'Rekaman menyimak belum tersedia.');
            abort_unless(($q->level ?? $q->lesson->level ?? 'foundation') === $template->level, 409, 'Pemetaan tingkat soal perlu diperbarui.');
        }
        $active = VirtualTestAttempt::where('owner_hash', $owner)->where('virtual_test_template_id', $template->id)->where('status', 'in_progress')->latest()->first();
        if ($active) {
            $active = $this->mutate($active, $owner, []);
            if ($active->status === 'in_progress') {
                return $active;
            }
        }
        $snapshots = array_map(function ($id) use ($questions) {
            $q = $questions[$id];

            return ['id' => $q->id, 'prompt' => $q->prompt, 'options' => $q->options, 'correct_index' => $q->correct_index, 'explanation' => $q->explanation, 'skill' => $q->skill ?? $q->type, 'type' => $q->type, 'audio_url' => $q->audio_path ? route('learning.audio', ['questions', $q->id]) : null];
        }, $ids);

        return VirtualTestAttempt::create(['status' => 'in_progress', 'id' => (string) Str::uuid(), 'virtual_test_template_id' => $template->id, 'owner_hash' => $owner, 'title' => $template->title, 'level' => $template->level, 'questions' => $snapshots, 'answers' => [], 'started_at' => now(), 'expires_at' => now()->addSeconds($template->duration_seconds)]);
    }

    public function mutate(VirtualTestAttempt $attempt, string $owner, array $answers, bool $finish = false): VirtualTestAttempt
    {
        return DB::transaction(function () use ($attempt, $owner, $answers, $finish) {
            $attempt = VirtualTestAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            abort_unless(hash_equals($attempt->owner_hash, $owner), 404);
            if ($attempt->status !== 'in_progress') {
                return $attempt;
            }
            $expired = now()->greaterThanOrEqualTo($attempt->expires_at);
            if (! $expired) {
                $byId = collect($attempt->questions)->keyBy('id');
                $saved = $attempt->answers;
                foreach ($answers as $id => $answer) {
                    if (! $byId->has($id) || ! is_int($answer) || $answer < 0 || $answer >= count($byId[$id]['options'])) {
                        throw ValidationException::withMessages(['answers' => 'Jawaban harus berasal dari soal dan pilihan pada paket ini.']);
                    }
                    $saved[$id] = $answer;
                }
                $attempt->answers = $saved;
            }
            if ($finish || $expired) {
                $right = 0;
                $skills = [];
                $reviews = [];
                foreach ($attempt->questions as $q) {
                    $answer = $attempt->answers[$q['id']] ?? null;
                    $correct = $answer !== null && $answer === $q['correct_index'];
                    $right += (int) $correct;
                    $skill = $q['skill'];
                    $skills[$skill] ??= ['correct' => 0, 'total' => 0];
                    $skills[$skill]['total']++;
                    $skills[$skill]['correct'] += (int) $correct;
                    $reviews[] = $q + ['answer_index' => $answer, 'correct' => $correct];
                }
                $attempt->result = ['correct' => $right, 'total' => count($attempt->questions), 'accuracy' => round(100 * $right / count($attempt->questions)), 'skills' => $skills, 'reviews' => $reviews, 'recommendation' => $right === count($attempt->questions) ? 'Lanjutkan materi berikutnya dan perluas latihan.' : 'Ulangi pembahasan soal yang salah atau belum dijawab.'];
                $attempt->status = $expired ? 'timed_out' : 'completed';
                $attempt->completed_at = now();
            }
            $attempt->save();

            return $attempt;
        });
    }

    public function payload(VirtualTestAttempt $attempt): array
    {
        return ['id' => $attempt->id, 'title' => $attempt->title, 'level' => $attempt->level, 'status' => $attempt->status, 'expires_at' => $attempt->expires_at->toIso8601String(), 'remaining_seconds' => max(0, (int) now()->diffInSeconds($attempt->expires_at, false)), 'questions' => array_map(fn ($q) => array_diff_key($q, array_flip(['correct_index', 'explanation'])), $attempt->questions), 'answers' => (object) $attempt->answers, 'result' => $attempt->result];
    }
}
