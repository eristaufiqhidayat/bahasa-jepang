<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\VirtualTestTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VirtualTestAdminController extends Controller
{
    public function index()
    {
        return view('admin.virtual-tests.index', ['templates' => VirtualTestTemplate::orderBy('level')->get()]);
    }

    public function create()
    {
        return $this->form(new VirtualTestTemplate(['level' => 'N5', 'status' => 'draft', 'duration_seconds' => 480, 'question_ids' => []]));
    }

    public function edit(VirtualTestTemplate $virtualTest)
    {
        abort_unless($virtualTest->mode === 'mini', 404);

        return $this->form($virtualTest);
    }

    private function form(VirtualTestTemplate $template)
    {
        return view('admin.virtual-tests.form', ['template' => $template, 'questions' => Question::with('lesson')->orderBy('level')->orderBy('id')->get()]);
    }

    private function save(Request $r, VirtualTestTemplate $template)
    {
        $data = $r->validate(['title' => 'required|string|max:255', 'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('virtual_test_templates')->ignore($template->id)], 'level' => 'required|in:N5,N4,N3,N2,N1', 'description' => 'required|string|max:3000', 'status' => 'required|in:draft,published', 'duration_minutes' => 'required|integer|min:1|max:180', 'question_ids' => 'required|array|min:1|max:100', 'question_ids.*' => 'required|integer|distinct|exists:questions,id']);
        $questions = Question::with('lesson')->whereIn('id', $data['question_ids'])->get();
        foreach ($questions as $q) {
            if (($q->level ?? $q->lesson->level ?? 'foundation') !== $data['level']) {
                throw ValidationException::withMessages(['question_ids' => 'Semua soal harus sesuai tingkat paket.']);
            }
            if ($data['status'] === 'published' && ($q->lesson->status !== 'published' || ($q->type === 'listening' && ! $q->audio_path))) {
                throw ValidationException::withMessages(['question_ids' => 'Paket published memerlukan materi published dan rekaman untuk soal menyimak.']);
            }
        }
        $data['question_ids'] = array_map('intval', $data['question_ids']);
        $data['duration_seconds'] = $data['duration_minutes'] * 60;
        $data['mode'] = 'mini';
        $data['sections'] = [['id' => 'mini', 'name_id' => 'Latihan campuran', 'minutes' => $data['duration_minutes']]];
        unset($data['duration_minutes']);
        $template->fill($data)->save();

        return redirect()->route('virtual-tests.index')->with('success', 'Paket mini virtual test disimpan.');
    }

    public function store(Request $r)
    {
        return $this->save($r, new VirtualTestTemplate);
    }

    public function update(Request $r, VirtualTestTemplate $virtualTest)
    {
        abort_unless($virtualTest->mode === 'mini', 404);

        return $this->save($r, $virtualTest);
    }

    public function destroy(VirtualTestTemplate $virtualTest)
    {
        abort_unless($virtualTest->mode === 'mini', 404);
        $virtualTest->delete();

        return back()->with('success', 'Paket dihapus. Snapshot tes yang sudah dimulai tetap disimpan.');
    }
}
