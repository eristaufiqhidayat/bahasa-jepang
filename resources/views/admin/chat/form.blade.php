@extends('layouts.admin')
@section('content')
<a href="{{ route('material-answers.index') }}">← Bank jawaban</a><h1>{{ $answer->exists?'Edit':'Tambah' }} jawaban</h1>
<form class="card" method="post" action="{{ $answer->exists?route('material-answers.update',$answer):route('material-answers.store') }}">@csrf @if($answer->exists) @method('PUT') @endif
<div class="form-grid">
@foreach(['slug'=>'Slug unik','question'=>'Pertanyaan utama','source_label'=>'Label sumber','keywords'=>'Kata kunci / sinonim (pisahkan koma)'] as $field=>$label)<label>{{ $label }}<input name="{{ $field }}" value="{{ old($field,$field==='keywords'?implode(', ',$answer->keywords??[]):$answer->$field) }}" required></label>@endforeach
<label class="wide">Jawaban, contoh Jepang, cara baca, dan terjemahan<textarea name="answer" rows="10" required>{{ old('answer',$answer->answer) }}</textarea></label>
<label>Tingkat<select name="level">@foreach(['foundation','N5','N4'] as $level)<option @selected(old('level',$answer->level)===$level)>{{ $level }}</option>@endforeach</select></label>
<label>Materi sumber<select name="lesson_id"><option value="">Fondasi umum (tanpa bab)</option>@foreach($lessons as $lesson)<option value="{{ $lesson->id }}" @selected((string)old('lesson_id',$answer->lesson_id)===(string)$lesson->id)>{{ $lesson->title }} · {{ $lesson->status }}</option>@endforeach</select></label>
<label>Status<select name="status">@foreach(['draft','published'] as $status)<option @selected(old('status',$answer->status)===$status)>{{ $status }}</option>@endforeach</select></label>
<label>Tinjauan<select name="review_status">@foreach(['unreviewed','needs_japanese_teacher_review','reviewed'] as $status)<option @selected(old('review_status',$answer->review_status)===$status)>{{ $status }}</option>@endforeach</select></label>
</div><p>Hanya jawaban published dan materi sumber published yang digunakan chat. Isi menggunakan teks biasa.</p><button>Simpan</button></form>
@endsection
