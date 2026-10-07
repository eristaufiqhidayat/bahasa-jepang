<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\CourseTrack;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Vocabulary;
use Illuminate\Http\Request;

class LearningApiController extends Controller
{
    public function tracks()
    {
        return CourseTrack::orderBy('position')->get();
    }

    public function lessons(Request $request)
    {
        $filter = $request->validate(['level' => 'nullable|in:foundation,N5,N4,N3,N2,N1,pro']);

        return Lesson::when($filter['level'] ?? null, fn ($q, $level) => $level === 'foundation' ? $q->where(fn ($q) => $q->whereNull('level')->orWhere('level', 'foundation')) : $q->where('level', $level))->where('status', 'published')->orderBy('position')->orderBy('id')->paginate(20);
    }

    public function lesson(Lesson $lesson)
    {
        abort_unless($lesson->status === 'published', 404);

        return $lesson->load(['vocabularies', 'questions' => fn ($q) => $q->where(fn ($q) => $q->where('type', '!=', 'listening')->orWhereNotNull('audio_path'))]);
    }

    public function characters(Request $r)
    {
        $data = $r->validate(['script' => 'nullable|in:hiragana,katakana']);

        return Character::when($data['script'] ?? null, fn ($q, $v) => $q->where('script', $v))->orderBy('position')->orderBy('id')->paginate(120);
    }

    public function vocabularies()
    {
        return Vocabulary::whereHas('lesson', fn ($q) => $q->where('status', 'published'))->orderBy('id')->paginate(30);
    }

    public function answer(Request $r, Question $question)
    {
        abort_unless($question->lesson->status === 'published', 404);
        $data = $r->validate(['answer_index' => 'required|integer|min:0|max:'.(count($question->options) - 1)]);

        return ['correct' => (int) $data['answer_index'] === $question->correct_index, 'correct_index' => $question->correct_index, 'explanation' => $question->explanation];
    }
}
