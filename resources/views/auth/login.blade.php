<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk Admin</title>
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="login">
<section class="card">
<div class="brand">
<span>あ</span> Belajar Jepang</div>
<h1>Masuk panel materi</h1>
<p class="muted">Kelola pelajaran pertama hingga latihan pemula.</p>@foreach($errors->all() as $error)<p class="errors">{{ $error }}</p>@endforeach<form method="post" action="/login">@csrf<label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
</label>
<label>Kata sandi<input type="password" name="password" autocomplete="current-password" required>
</label>
<button>Masuk</button>
</form>
</section>
</body>
</html>

