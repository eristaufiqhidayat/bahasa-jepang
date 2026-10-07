@extends('layouts.admin') 
@section('title',$config['label']) 
@section('content')<div class="heading">
<div>
<h1>{{ $config['label'] }}</h1>
<p class="muted">Tambah, periksa, dan perbarui materi belajar.</p>
</div>
<a class="button" href="{{ route('materials.create',$type) }}">+ Tambah</a>
</div>
<form class="search" method="get">
<input name="q" value="{{ $search }}" placeholder="Cari {{ strtolower($config['label']) }}" aria-label="Cari materi">
<button>Cari</button>
</form>
<div class="card table-wrap">
<table>
<thead>
<tr>
<th>Materi</th>
<th>Detail</th>
<th>Audio</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>@forelse($items as $item)<tr>
<td>
<strong>{{ $item->title ?? $item->japanese ?? $item->symbol ?? $item->prompt }}</strong>
</td>
<td>{{ $item->status ?? $item->meaning ?? $item->romaji ?? $item->type }}</td>
<td>@if($item->audio_path)<audio controls preload="none" src="{{ Storage::disk('public')->url($item->audio_path) }}">
</audio>@else<span class="muted">Belum ada</span>@endif</td>
<td>
<div class="actions">
<a href="{{ route('materials.edit',[$type,$item->id]) }}">Edit</a>
<form method="post" action="{{ route('materials.destroy',[$type,$item->id]) }}" onsubmit="return confirm('Hapus materi ini? Menghapus pelajaran juga menghapus kosakata dan soalnya.')">@csrf @method('DELETE')<button class="danger">Hapus</button>
</form>
</div>
</td>
</tr>@empty<tr>
<td colspan="4">Belum ada materi yang sesuai. Tambahkan materi pertama.</td>
</tr>@endforelse</tbody>
</table>
</div>
<div class="pagination">@if($items->previousPageUrl())<a href="{{ $items->previousPageUrl() }}">← Sebelumnya</a>@endif<span>Halaman {{ $items->currentPage() }} / {{ $items->lastPage() }}</span>@if($items->nextPageUrl())<a href="{{ $items->nextPageUrl() }}">Berikutnya →</a>@endif</div>
@endsection

