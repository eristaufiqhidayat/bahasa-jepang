@extends('layouts.admin') 
@section('content')<p><a class="button" href="{{ route('learning.preview') }}">Pratinjau halaman belajar ↗</a> <a href="{{ route('learning.home') }}">Halaman publik ↗</a></p><h1>Siapkan langkah pertama mereka.</h1>
<p class="muted">Kelola materi bahasa Jepang yang mudah diikuti oleh pemula Indonesia.</p>
<div class="stats">@foreach($counts as $label=>$count)<div class="card">
<span>{{ $label }}</span>
<strong>{{ $count }}</strong>
</div>@endforeach</div>
<section class="card">
<h2>Alur menambahkan materi</h2>
<ol>
<li>Buat pelajaran dengan status draft.</li>
<li>Tambahkan kosakata, audio, dan soal latihan.</li>
<li>Periksa tulisan Jepang, arti, dan pembahasan.</li>
<li>Ubah status menjadi published untuk ditampilkan melalui API.</li>
</ol>
<p>{{ $published }} pelajaran sudah dipublikasikan.</p>
<a class="button" href="{{ route('materials.create','lessons') }}">+ Buat pelajaran</a>
</section>
<p class="muted">Materi contoh masih berupa draft. Unggah audio dan periksa materi sebelum publikasi.</p>
@endsection

