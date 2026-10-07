<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\MaterialAnswer;
use App\Models\MaterialChatMessage;
use App\Models\MaterialChatReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaterialAnswerAdminController extends Controller
{
    public function index()
    {
        return view('admin.chat.index', ['answers' => MaterialAnswer::with('lesson')->orderByDesc('id')->paginate(20), 'reports' => MaterialChatReport::with('message')->where('status', 'open')->latest()->take(20)->get(), 'unanswered' => MaterialChatMessage::where('response->matched', false)->latest()->take(20)->get()]);
    }

    public function create(Request $r)
    {
        return $this->form(new MaterialAnswer(['level' => 'N5', 'question' => $r->query('question', ''), 'status' => 'draft', 'keywords' => []]));
    }

    public function edit(MaterialAnswer $materialAnswer)
    {
        return $this->form($materialAnswer);
    }

    private function form(MaterialAnswer $answer)
    {
        return view('admin.chat.form', ['answer' => $answer, 'lessons' => Lesson::orderBy('position')->get()]);
    }

    private function save(Request $r, MaterialAnswer $answer)
    {
        $d = $r->validate(['slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('material_answers')->ignore($answer->id)], 'question' => 'required|string|max:255', 'answer' => 'required|string|max:10000', 'keywords' => 'required|string|max:2000', 'level' => 'required|in:foundation,N5,N4', 'lesson_id' => 'nullable|integer|exists:lessons,id', 'source_label' => 'required|string|max:255', 'status' => 'required|in:draft,published', 'review_status' => 'required|in:unreviewed,needs_japanese_teacher_review,reviewed']);
        $d['keywords'] = array_values(array_filter(array_map('trim', explode(',', $d['keywords']))));
        $answer->fill($d)->save();

        return redirect()->route('material-answers.index')->with('success', 'Bank jawaban disimpan.');
    }

    public function store(Request $r)
    {
        return $this->save($r, new MaterialAnswer);
    }

    public function update(Request $r, MaterialAnswer $materialAnswer)
    {
        return $this->save($r, $materialAnswer);
    }

    public function destroy(MaterialAnswer $materialAnswer)
    {
        $materialAnswer->delete();

        return back()->with('success', 'Jawaban dihapus.');
    }

    public function resolve(Request $r, MaterialChatReport $report)
    {
        $d = $r->validate(['admin_note' => 'required|string|max:2000']);
        $report->update($d + ['status' => 'resolved']);

        return back()->with('success', 'Laporan ditandai selesai.');
    }
}
