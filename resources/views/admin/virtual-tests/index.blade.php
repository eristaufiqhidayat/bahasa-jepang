@extends('layouts.admin')
@section('content')
<h1>Virtual test</h1>
<p>Kelola paket mini test. Rancangan JLPT penuh tetap ditampilkan sebagai acuan; belum dapat dimainkan.</p>
<a class="button" href="{{ route('virtual-tests.create') }}">Tambah mini test</a>
@foreach($templates as $template)
<section class="card"><h2>{{ $template->title }}</h2><p>{{ $template->level }} · {{ $template->mode }} · {{ $template->status }} · {{ $template->duration_seconds / 60 }} menit · {{ count($template->question_ids) }} soal</p><p>{{ $template->description }}</p>
@if($template->mode==='mini')<a href="{{ route('virtual-tests.edit',$template) }}">Edit paket</a>
<form method="post" action="{{ route('virtual-tests.destroy',$template) }}" onsubmit="return confirm('Hapus paket ini? Tes yang telah dimulai tetap tersimpan.')">@csrf @method('DELETE')<button class="secondary">Hapus paket</button></form>
@else<p class="muted">@foreach($template->sections as $section){{ $section['name_id'] }}: {{ $section['minutes'] }} menit @if(!$loop->last) · @endif @endforeach</p>@endif
</section>
@endforeach
@endsection
