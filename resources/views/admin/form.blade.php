@extends('layouts.admin') 
@section('content')<a href="{{ route('materials.index',$type) }}">← Kembali ke daftar</a>
<h1>{{ $item->exists?'Edit':'Tambah' }} {{ strtolower($config['label']) }}</h1>
<form class="card material-form" method="post" enctype="multipart/form-data" action="{{ $item->exists?route('materials.update',[$type,$item->id]):route('materials.store',$type) }}">@csrf @if($item->exists) @method('PUT') @endif<div class="form-grid">@foreach($config['fields'] as $field=>$label)@php
$value=old($field,str_starts_with($field,'option_')?($item->options[(int)substr($field,-1)]??''):($item->$field??match($field){'position'=>1,'duration_minutes'=>5,'status'=>'draft','category'=>'Umum','group'=>'Dasar',default=>''}));
$choices=match($field){'status'=>['draft'=>'Draft','published'=>'Published'],'script'=>['hiragana'=>'Hiragana','katakana'=>'Katakana'],'type'=>['meaning'=>'Arti','reading'=>'Membaca','listening'=>'Mendengarkan'],'correct_index'=>[0=>'A',1=>'B',2=>'C',3=>'D'],default=>null};
@endphp<label class="{{ in_array($field,['content','summary','explanation','prompt'])?'wide':'' }}">{{ $label }}@if($field==='lesson_id')<select name="{{ $field }}" required>
<option value="">Pilih pelajaran</option>@foreach($lessons as $lesson)<option value="{{ $lesson->id }}" @selected((string)$value===(string)$lesson->id)>{{ $lesson->title }}</option>@endforeach</select>@elseif($choices)<select name="{{ $field }}">@foreach($choices as $key=>$text)<option value="{{ $key }}" @selected((string)$value===(string)$key)>{{ $text }}</option>@endforeach</select>@elseif(in_array($field,['content','summary','explanation','prompt','translation','example','example_translation']))<textarea name="{{ $field }}" rows="{{ $field==='content'?10:3 }}">{{ $value }}</textarea>@else<input name="{{ $field }}" value="{{ $value }}" type="{{ in_array($field,['position','duration_minutes'])?'number':'text' }}" @if(in_array($field,['position','duration_minutes'])) min="1" @endif>@endif</label>@endforeach<label class="wide">Audio (opsional; wajib untuk soal mendengarkan)<input type="file" name="audio" accept=".mp3,.wav,.ogg,.m4a">
<small>Maksimal 10 MB. MP3, WAV, OGG, atau M4A.</small>
</label>@if($item->audio_path)<div class="wide">
<audio controls src="{{ Storage::disk('public')->url($item->audio_path) }}">
</audio>
<label>
<input type="checkbox" name="remove_audio" value="1"> Hapus audio saat ini</label>
</div>@endif</div>
<p class="muted">Isi materi menggunakan teks biasa. Periksa terjemahan dan pengucapan sebelum publikasi.</p>
<button>Simpan materi</button>
</form>
@endsection

