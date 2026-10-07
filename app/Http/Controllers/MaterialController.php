<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Vocabulary;
use App\Support\MaterialTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', ['counts' => ['Pelajaran' => Lesson::count(), 'Kosakata' => Vocabulary::count(), 'Huruf' => Character::count(), 'Soal' => Question::count()], 'published' => Lesson::where('status', 'published')->count()]);
    }

    public function index(Request $r, string $type)
    {
        $config = MaterialTypes::get($type);
        $q = $config['model']::query();
        $search = trim((string) $r->query('q', ''));
        if ($search !== '') {
            $field = match ($type) {
                'lessons' => 'title','vocabularies' => 'japanese','characters' => 'symbol','questions' => 'prompt'
            };
            $q->where($field, 'like', '%'.$search.'%');
        }

        return view('admin.index', compact('config', 'type', 'search') + ['items' => $q->orderBy('id', 'desc')->paginate(15)->withQueryString()]);
    }

    public function create(string $type)
    {
        $config = MaterialTypes::get($type);

        return view('admin.form', compact('config', 'type') + ['item' => new $config['model'], 'lessons' => Lesson::orderBy('position')->get()]);
    }

    public function edit(string $type, int $id)
    {
        $config = MaterialTypes::get($type);

        return view('admin.form', compact('config', 'type') + ['item' => $config['model']::findOrFail($id), 'lessons' => Lesson::orderBy('position')->get()]);
    }

    private function rules(string $type, ?int $id): array
    {
        $rules = match ($type) {
            'lessons' => ['title' => 'required|string|max:255', 'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('lessons')->ignore($id)], 'summary' => 'required|string|max:2000', 'content' => 'required|string|max:50000', 'japanese' => 'nullable|string|max:255', 'romaji' => 'nullable|string|max:255', 'translation' => 'nullable|string|max:5000', 'position' => 'required|integer|min:1|max:10000', 'duration_minutes' => 'required|integer|min:1|max:120', 'status' => 'required|in:draft,published'],
            'vocabularies' => ['lesson_id' => 'required|exists:lessons,id', 'japanese' => 'required|string|max:255', 'reading' => 'nullable|string|max:255', 'romaji' => 'required|string|max:255', 'meaning' => 'required|string|max:255', 'category' => 'required|string|max:255', 'example' => 'nullable|string|max:5000', 'example_translation' => 'nullable|string|max:5000'],
            'characters' => ['script' => 'required|in:hiragana,katakana', 'symbol' => ['required', 'string', 'max:10', Rule::unique('characters')->where('script', request('script'))->ignore($id)], 'romaji' => 'required|string|max:30', 'group' => 'required|string|max:255', 'position' => 'required|integer|min:1|max:10000'],
            'questions' => ['lesson_id' => 'required|exists:lessons,id', 'prompt' => 'required|string|max:5000', 'type' => 'required|in:meaning,reading,listening', 'option_0' => 'required|string|max:255', 'option_1' => 'required|string|max:255', 'option_2' => 'required|string|max:255', 'option_3' => 'nullable|string|max:255', 'correct_index' => 'required|integer|between:0,3', 'explanation' => 'required|string|max:5000', 'position' => 'required|integer|min:1|max:10000'],
        };

        return $rules + ['audio' => 'nullable|file|mimes:mp3,wav,ogg,m4a|max:10240', 'remove_audio' => 'nullable|boolean'];
    }

    private function save(Request $r, string $type, ?int $id = null)
    {
        $config = MaterialTypes::get($type);
        $item = $id ? $config['model']::findOrFail($id) : new $config['model'];
        $data = $r->validate($this->rules($type, $id));
        if ($type === 'questions') {
            $data['options'] = array_map(fn ($i) => $data['option_'.$i], range(0, 2));
            if (isset($data['option_3']) && trim($data['option_3']) !== '') {
                $data['options'][] = $data['option_3'];
            }
            if ((int) $data['correct_index'] >= count($data['options'])) {
                return back()->withErrors(['correct_index' => 'Jawaban benar harus menunjuk pilihan yang terisi.'])->withInput();
            }
            foreach (range(0, 3) as $i) {
                unset($data['option_'.$i]);
            }
            if ($data['type'] === 'listening' && ! $r->hasFile('audio') && (! $item->audio_path || $r->boolean('remove_audio'))) {
                return back()->withErrors(['audio' => 'Soal mendengarkan memerlukan berkas audio.'])->withInput();
            }
        }
        $old = $item->audio_path;
        $new = null;
        unset($data['audio'],$data['remove_audio']);
        if ($r->hasFile('audio')) {
            $new = $r->file('audio')->store('audio/'.$type, 'public');
            $data['audio_path'] = $new;
        } elseif ($r->boolean('remove_audio')) {
            $data['audio_path'] = null;
        }
        try {
            $item->fill($data)->save();
        } catch (\Throwable $e) {
            if ($new) {
                Storage::disk('public')->delete($new);
            }
            throw $e;
        }
        if ($old && ($new || $r->boolean('remove_audio'))) {
            Storage::disk('public')->delete($old);
        }

        return redirect()->route('materials.index', $type)->with('success', 'Materi berhasil disimpan.');
    }

    public function store(Request $r, string $type)
    {
        return $this->save($r, $type);
    }

    public function update(Request $r, string $type, int $id)
    {
        return $this->save($r, $type, $id);
    }

    public function destroy(string $type, int $id)
    {
        $c = MaterialTypes::get($type);
        $item = $c['model']::findOrFail($id);
        $paths = [$item->audio_path];
        if ($type === 'lessons') {
            $paths = array_merge($paths, $item->vocabularies()->pluck('audio_path')->all(), $item->questions()->pluck('audio_path')->all());
        }
        $item->delete();
        Storage::disk('public')->delete(array_values(array_filter($paths)));

        return back()->with('success', 'Materi berhasil dihapus.');
    }
}
