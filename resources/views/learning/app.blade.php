<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>Haru · Belajar Minna no Nihongo</title><link rel="stylesheet" href="{{ asset('css/learning.css') }}?v={{ filemtime(public_path('css/learning.css')) }}"></head><body><div id="jt-upgrade">
  <div class="shell">
    <aside class="rail">
      <div class="brand"><div class="logo"><span class="logo-mark">は</span>haru.<span class="muted" style="font-size:12px">japantest.id</span></div><small>Belajar Minna no Nihongo</small></div>
      <nav class="nav" aria-label="Menu utama">
        <button data-nav="home" class="active"><i data-lucide="house" aria-hidden="true"></i>Beranda</button>
        <button data-nav="kana"><span aria-hidden="true">あ</span>Huruf</button>
        <button data-nav="words"><span aria-hidden="true">文</span>Kosakata</button>
        <button data-nav="path"><i data-lucide="route" aria-hidden="true"></i>Kurikulum</button>
        <button data-nav="learn"><i data-lucide="book-open" aria-hidden="true"></i>Belajar</button>
        <button data-nav="practice"><i data-lucide="list-checks" aria-hidden="true"></i>Latihan</button>
        <button data-nav="exam"><i data-lucide="timer" aria-hidden="true"></i>Virtual Test</button>
        <button data-nav="chat"><span aria-hidden="true">?</span>Tanya Materi</button>
        <button data-nav="progress"><i data-lucide="chart-no-axes-column" aria-hidden="true"></i>Profil</button>
      </nav>
      <a class="textbtn" href="{{ route('dashboard') }}" target="_blank" rel="noopener">Panel admin ↗</a><div class="rail-note"><strong>Belajar Minna no Nihongo</strong><p style="margin-top:6px">Fondasi yang kuat. Jalur bertahap hingga N1 dan bahasa Jepang profesional.</p></div>
    </aside>
    <div class="stage">
      <header class="top"><label class="level-picker">Jalur belajar <select aria-label="Pilih tingkat" id="jt-level"><option value="foundation">Fondasi</option><option value="N5" selected>N5 · Pemula</option><option value="N4">N4 · Dasar</option><option value="N3">N3 · Menengah</option><option value="N2">N2 · Lanjutan</option><option value="N1">N1 · Mahir</option><option value="pro">Profesional</option></select></label><div class="top-right"><span class="tag warm">Belajar Jepang</span><span class="avatar">H</span></div></header>
      @if($preview)<div class="preview-banner"><strong>Pratinjau admin</strong> · Termasuk materi draft. <a href="{{ route('dashboard') }}">Kembali ke panel</a></div>@endif
      <main class="main" id="jt-content" aria-live="polite"></main>
      <footer class="footer"><span>Haru · Materi dikelola pengajar · Progres belajar tersimpan pada browser ini</span><span><a href="https://www.jlpt.jp/sp/e/guideline/testsections.html" target="_blank" rel="noopener">Referensi JLPT ↗</a></span></footer>
    </div>
  </div>
</div><script>window.learningData = {{ Illuminate\Support\Js::from($data) }};</script><script src="{{ asset('js/learning.js') }}?v={{ filemtime(public_path('js/learning.js')) }}" defer></script></body></html>