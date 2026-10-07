@extends('layouts.admin')
@section('content')
<h1>Tanya Materi · Bank jawaban</h1><p>Pencarian berbasis kata kunci tanpa AI. Jawaban dengan sumber materi draft tidak tampil publik. Tinjau penjelasan sebelum publikasi.</p>
<a href="{{ route('material-answers.create') }}">Tambah jawaban</a>
@foreach($answers as $answer)<section class="card"><h3>{{ $answer->question }}</h3><p>{{ $answer->level }} · {{ $answer->status }} · {{ $answer->source_label }} · {{ $answer->review_status }}</p><a href="{{ route('material-answers.edit',$answer) }}">Edit</a><form method="post" action="{{ route('material-answers.destroy',$answer) }}" onsubmit="return confirm('Hapus bank jawaban ini?')">@csrf @method('DELETE')<button class="secondary">Hapus</button></form></section>@endforeach
{{ $answers->links() }}
<h2>Laporan jawaban keliru</h2>
@forelse($reports as $report)<section class="card"><p><strong>Pertanyaan:</strong> {{ $report->message->question }}</p><p style="white-space:pre-wrap">{{ $report->message->response['answer'] }}</p><p><strong>Laporan:</strong> {{ $report->reason }}</p>@if($report->message->material_answer_id)<a href="{{ route('material-answers.edit',$report->message->material_answer_id) }}">Perbaiki bank jawaban</a>@endif<form method="post" action="{{ route('material-chat.resolve',$report) }}">@csrf<label>Catatan tindak lanjut<textarea name="admin_note" required maxlength="2000"></textarea></label><button>Tandai selesai</button></form></section>@empty<p>Belum ada laporan terbuka.</p>@endforelse
<h2>Pertanyaan belum terjawab · 20 terakhir</h2>
@forelse($unanswered as $message)<section class="card"><p>{{ $message->level }} · {{ $message->question }}</p><a href="{{ route('material-answers.create',['question'=>$message->question]) }}">Buat jawaban</a></section>@empty<p>Belum ada pertanyaan belum terjawab.</p>@endforelse
@endsection
