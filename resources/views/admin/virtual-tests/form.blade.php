@extends('layouts.admin')
@section('content')
<a href="{{ route('virtual-tests.index') }}">← Daftar paket</a><h1>{{ $template->exists?'Edit':'Tambah' }} mini virtual test</h1>
<form class="card material-form" method="post" action="{{ $template->exists?route('virtual-tests.update',$template):route('virtual-tests.store') }}">@csrf @if($template->exists) @method('PUT') @endif
<div class="form-grid">
<label>Judul<input name="title" value="{{ old('title',$template->title) }}" required></label>
<label>Slug<input name="slug" value="{{ old('slug',$template->slug) }}" required></label>
<label>Tingkat<select name="level">@foreach(['N5','N4','N3','N2','N1'] as $level)<option @selected(old('level',$template->level)===$level)>{{ $level }}</option>@endforeach</select></label>
<label>Status<select name="status">@foreach(['draft','published'] as $status)<option @selected(old('status',$template->status)===$status)>{{ $status }}</option>@endforeach</select></label>
<label>Durasi (menit)<input type="number" name="duration_minutes" min="1" max="180" value="{{ old('duration_minutes',$template->duration_seconds/60) }}" required></label>
<label class="wide">Penjelasan<textarea name="description" rows="3" required>{{ old('description',$template->description) }}</textarea></label>
</div><h2>Pilih soal</h2><p class="muted">Pilih soal dengan tingkat yang sama. Materi draft atau soal menyimak tanpa audio tidak boleh dimasukkan ke paket published. Urutan paket mengikuti pilihan ID soal. Paket ini untuk latihan, bukan penilaian JLPT resmi.</p>
@foreach($questions as $q)<label style="display:block;margin:12px 0"><input type="checkbox" name="question_ids[]" value="{{ $q->id }}" @checked(in_array($q->id,old('question_ids',$template->question_ids??[])))> #{{ $q->id }} · {{ $q->level??$q->lesson->level??'Fondasi' }} · {{ $q->skill??$q->type }} · {{ $q->lesson->status }} · {{ $q->prompt }} @if($q->type==='listening'&&!$q->audio_path) (audio belum tersedia) @endif</label>@endforeach
<button>Simpan paket</button>
</form>
@endsection
