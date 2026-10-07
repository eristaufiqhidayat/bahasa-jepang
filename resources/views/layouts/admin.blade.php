<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title','Panel Materi') · Belajar Jepang</title>
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
<aside>
<a class="brand" href="{{ route('dashboard') }}">
<span>あ</span> Belajar Jepang</a>
<p class="muted">Panel materi & kurikulum</p>
<nav>
<a href="{{ route('dashboard') }}">Ringkasan</a>@foreach(\App\Support\MaterialTypes::all() as $key=>$nav)<a class="{{ request()->is('admin/'.$key.'*')?'active':'' }}" href="{{ route('materials.index',$key) }}">{{ $nav['label'] }}</a>@endforeach<a href="{{ route('virtual-tests.index') }}">Virtual test</a><a href="{{ route('material-answers.index') }}">Tanya Materi & laporan</a></nav>
<form method="post" action="{{ route('logout') }}">@csrf<button class="secondary">Keluar</button>
</form>
</aside>
<main>
<header>
<span>KONTEN PEMBELAJARAN</span>
<small>{{ auth()->user()->name }}</small>
</header>@if(session('success'))<div class="notice">{{ session('success') }}</div>@endif @if($errors->any())<div class="errors">
<strong>Periksa isian berikut:</strong>
<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>@endif @yield('content')</main>
</body>
</html>

