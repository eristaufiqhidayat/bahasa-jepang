<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\CourseTrack;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\VirtualTestTemplate;
use App\Models\Vocabulary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LearningWebController extends Controller
{
    public function index()
    {
        return $this->page(false);
    }

    public function preview()
    {
        return $this->page(true);
    }

    private function page(bool $preview)
    {
        $lessons = Lesson::with(['vocabularies', 'questions'])->when(! $preview, fn ($q) => $q->where('status', 'published'))->orderBy('position')->orderBy('id')->get();
        $audio = function ($item, string $type) use ($preview) {
            return $item->audio_path ? route($preview ? 'learning.preview.audio' : 'learning.audio', [$type, $item->id]) : null;
        };
        $words = $lessons->flatMap(fn ($l) => $l->vocabularies)->map(fn ($v) => [...$v->toArray(), 'audio_url' => $audio($v, 'vocabularies')])->values();
        $questions = $lessons->flatMap(fn ($l) => $l->questions)->map(fn ($q) => [...$q->toArray(), 'lesson_title' => $q->lesson->title, 'audio_url' => $audio($q, 'questions')])->values();
        $data = ['preview' => $preview, 'answer_base' => url($preview ? 'pratinjau/questions' : 'belajar/questions'), 'lessons' => $lessons->map(fn ($l) => [...$l->toArray(), 'audio_url' => $audio($l, 'lessons'), 'vocabularies' => $words->where('lesson_id', $l->id)->values()])->values(), 'words' => $words, 'questions' => $questions, 'characters' => Character::orderBy('position')->orderBy('id')->get()->map(fn ($c) => [...$c->toArray(), 'audio_url' => $audio($c, 'characters')])];

        $data['tracks'] = CourseTrack::orderBy('position')->get();
        $data['templates'] = VirtualTestTemplate::where(fn ($q) => $q->where('status', 'published')->orWhere('mode', 'blueprint'))->get()->makeHidden(['question_ids']);
        $data['exam_base'] = url('belajar/virtual-tests');

        return view('learning.app', compact('data', 'preview'));
    }

    public function answer(Request $request, Question $question)
    {
        return $this->grade($request, $question, false);
    }

    public function previewAnswer(Request $request, Question $question)
    {
        return $this->grade($request, $question, true);
    }

    private function grade(Request $request, Question $question, bool $preview)
    {
        abort_unless($preview || $question->lesson->status === 'published', 404);
        $data = $request->validate(['answer_index' => 'required|integer|min:0|max:'.(count($question->options) - 1)]);

        return response()->json(['correct' => (int) $data['answer_index'] === $question->correct_index, 'correct_index' => $question->correct_index, 'explanation' => $question->explanation]);
    }

    public function audio(string $type, int $id)
    {
        return $this->file($type, $id, false);
    }

    public function previewAudio(string $type, int $id)
    {
        return $this->file($type, $id, true);
    }

    private function file(string $type, int $id, bool $preview)
    {
        $models = ['lessons' => Lesson::class, 'vocabularies' => Vocabulary::class, 'characters' => Character::class, 'questions' => Question::class];
        abort_unless(isset($models[$type]), 404);
        $item = $models[$type]::findOrFail($id);
        if (! $preview && $type !== 'characters') {
            $lesson = $type === 'lessons' ? $item : $item->lesson;
            abort_unless($lesson->status === 'published', 404);
        }
        abort_unless($item->audio_path && str_starts_with($item->audio_path, 'audio/') && ! str_contains($item->audio_path, '..') && Storage::disk('public')->exists($item->audio_path), 404);

        return response()->file(Storage::disk('public')->path($item->audio_path), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => $preview ? 'private, no-store' : 'public, max-age=3600']);
    }
}
