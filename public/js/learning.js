(() => {
  'use strict';
  const data = window.learningData;
  const root = document.querySelector('#jt-upgrade');
  const out = root.querySelector('#jt-content');
  const picker = root.querySelector('#jt-level');
  const key = 'haru-learning-v1' + (data.preview ? '-preview' : '');
  let stored = {};
  try { stored = JSON.parse(localStorage.getItem(key) || '{}') || {}; } catch (_) {}
  const progress = {done: [], known: [], scores: [], romaji: true, target: 10, ...stored};
  for (const field of ['done', 'known', 'scores']) if (!Array.isArray(progress[field])) progress[field] = [];
  const state = {page: 'home', level: 'foundation', learnView: 'current', lessonId: null, script: 'hiragana', search: '', category: 'Semua', practiceView: 'current', practiceLesson: '', qi: 0, selected: null, feedback: null, busy: false, exam: null, examIndex: 0, examBusy: false, examDeadline: 0};
  const chatState = {loaded: false, loading: false, busy: false, level: 'foundation', lessonId: null, messages: [], quota: null, draft: '', error: '', pending: null, reporting: null};
  let generation = 0, timer = null, audioPlayer = null;
  const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
  const title = (small, big, desc = '') => `<div class="stack"><span class="eyebrow">${esc(small)}</span><h1>${esc(big)}</h1>${desc ? `<p class="muted">${esc(desc)}</p>` : ''}</div>`;
  const button = (text, action, cls = '') => `<button class="btn ${cls}" data-action="${action}">${esc(text)}</button>`;
  const romaji = text => progress.romaji && text ? `<small>${esc(text)}</small>` : '';
  const levelOf = lesson => lesson.level || 'foundation';
  const track = () => data.tracks.find(t => t.code === state.level) || {name: 'Fondasi', description: 'Mulai dari huruf dan kosakata.'};
  const currentLessons = () => data.lessons.filter(l => !l.reference_id);
  const completed = () => data.lessons.filter(l => progress.done.includes(l.id)).length;
  const countKnown = () => data.words.filter(w => progress.known.includes(w.id)).length;
  function persist() { try { localStorage.setItem(key, JSON.stringify(progress)); } catch (_) { toast('Browser tidak dapat menyimpan progres lokal.'); } }
  function toast(message) {
    let el = root.querySelector('#toast');
    if (!el) { el = document.createElement('div'); el.id = 'toast'; el.className = 'notice'; el.setAttribute('role', 'status'); el.style.cssText = 'position:fixed;bottom:20px;left:20px;right:20px;z-index:80;max-width:700px;margin:auto;box-shadow:0 6px 20px #0002'; root.append(el); }
    el.textContent = message; clearTimeout(toast.timeout); toast.timeout = setTimeout(() => el.remove(), 5000);
  }
  async function request(url, body) {
    const options = {headers: {'Accept': 'application/json'}, credentials: 'same-origin'};
    if (body !== undefined) { options.method = 'POST'; options.headers['Content-Type'] = 'application/json'; options.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content; options.body = JSON.stringify(body); }
    const response = await fetch(url, options);
    let result; try { result = await response.json(); } catch (_) { throw Error('Respons server belum dapat dibaca. Muat ulang halaman.'); }
    if (!response.ok) throw Error(response.status === 419 ? 'Sesi halaman berakhir. Muat ulang lalu coba kembali.' : (result.message || 'Permintaan belum berhasil. Coba kembali.'));
    return result;
  }
  function play(text, url) {
    if (audioPlayer) audioPlayer.pause();
    if (window.speechSynthesis) speechSynthesis.cancel();
    if (url) { audioPlayer = new Audio(url); audioPlayer.play().catch(() => toast('Rekaman belum dapat diputar.')); return; }
    if (!window.speechSynthesis) { toast('Suara Jepang belum tersedia di browser ini.'); return; }
    const u = new SpeechSynthesisUtterance(text); u.lang = 'ja-JP'; u.rate = .8; speechSynthesis.speak(u);
    toast('Menggunakan suara Jepang sintetis perangkat.');
  }
  function go(page) { generation++; state.busy = false; state.page = page; render(); if (page === 'chat' && !chatState.loaded) loadChat(); }
  function lessonCards(list) {
    return list.map(l => `<article class="panel stack"><div class="row"><span class="tag">${esc(levelOf(l) === 'foundation' ? 'Pemula Haru' : levelOf(l))} · ${l.duration_minutes} menit</span><small>${progress.done.includes(l.id) ? '✓ Dipelajari' : ''}</small></div><h2>${esc(l.title)}</h2><p class="muted">${esc(l.summary)}</p>${l.japanese ? `<p class="jp" lang="ja">${esc(l.japanese)}</p>` : ''}${l.review_status === 'needs_japanese_teacher_review' ? '<small>Materi awal · perlu tinjauan pengajar</small>' : ''}<button class="btn primary" data-lesson="${l.id}">Buka materi →</button></article>`).join('') || '<section class="panel"><p class="empty">Materi jalur ini sedang disiapkan oleh pengajar.</p></section>';
  }
  function home() {
    const next = data.lessons.find(l => !progress.done.includes(l.id)) || data.lessons[0];
    return `${title('Belajar Minna no Nihongo', 'Bahasa baru. Kesempatan baru.', 'Huruf dan kosakata tetap menjadi fondasi. Lanjutkan bertahap dengan materi, latihan, dan virtual test.')}<section class="hero"><div><span class="tag">Haru · Belajar harian</span><h2 style="margin-top:13px">Pelan-pelan, pasti bisa 🌱</h2><p>Luangkan ${progress.target} menit hari ini. ${next ? 'Materi berikutnya: ' + esc(next.title) : 'Materi akan tampil setelah diterbitkan oleh pengajar.'}</p><div class="quick-links">${next ? `<button class="btn primary" data-lesson="${next.id}">Lanjutkan belajar →</button>` : button('Lihat materi', 'learn', 'primary')}${button('Kenali huruf', 'kana')}</div></div><div class="hero-art" aria-hidden="true"><span>学ぶ</span></div></section><div class="skills"><button class="skill statlink" data-action="kana"><small>Hiragana & katakana</small><strong>${data.characters.length} huruf</strong></button><button class="skill statlink" data-action="words"><small>Kosakata dikenal</small><strong>${countKnown()} / ${data.words.length}</strong></button><button class="skill statlink" data-action="learn"><small>Materi dipelajari</small><strong>${completed()} / ${data.lessons.length}</strong></button><button class="skill statlink" data-action="progress"><small>Target harian</small><strong>${progress.target} menit</strong></button></div><div class="row"><h2>Materi pemula Haru</h2>${button('Lihat semua materi', 'learn')}</div><div class="grid2">${lessonCards(currentLessons())}</div><section class="panel stack"><span class="eyebrow">Jalur upgrade</span><h2>Fondasi → N5 → N4 → N3 → N2 → N1 → Profesional</h2><p class="muted">Rangkuman Bab 1–50 tersedia sebagai materi awal pada N5/N4. Pemetaan bersifat editorial; jalur lanjut akan diisi bertahap.</p><div class="quick-links">${button('Jelajahi kurikulum', 'path', 'soft')}${button('Mini virtual test', 'exam')}</div></section>`;
  }
  function path() {
    return `${title('Dari pemula hingga mahir', 'Satu jalur. Banyak kemungkinan.', 'Pemetaan Japantest mengacu konsep materi, bukan daftar resmi atau kesetaraan bab dengan level JLPT.')}<div class="level-cards">${data.tracks.map(t => { const count = data.lessons.filter(l => levelOf(l) === t.code).length; return `<section class="panel level-card ${t.code === state.level ? 'selected' : ''}"><div class="row"><span class="level-code">${esc(t.code === 'foundation' ? 'あいう' : t.code === 'pro' ? 'PRO' : t.code)}</span><span class="tag ${count ? '' : 'warm'}">${count ? count + ' materi' : 'Sedang disiapkan'}</span></div><h3>${esc(t.name)}</h3><p class="muted">${esc(t.description)}</p><small>${t.focus.map(esc).join(' · ')}</small><button class="btn ${t.code === state.level ? 'primary' : ''}" data-track="${esc(t.code)}">Lihat jalur →</button></section>`; }).join('')}</div><div class="notice">Fondasi dan Profesional merupakan jalur Japantest. Materi awal N5/N4 masih memerlukan tinjauan pengajar; belum mencakup seluruh kompetensi ujian.</div>`;
  }
  function wordCard(w) {
    return `<article class="panel word-card ${progress.known.includes(w.id) ? 'known' : ''}"><div class="row"><span class="badge">${esc(w.category)}</span><button class="textbtn" data-word-audio="${w.id}" aria-label="Dengarkan ${esc(w.japanese)}">▶ Audio</button></div><p class="jp" lang="ja">${esc(w.japanese)}</p>${w.reading ? `<p lang="ja">${esc(w.reading)}</p>` : ''}${romaji(w.romaji)}<h3>${esc(w.meaning)}</h3>${w.example ? `<div class="lesson-example"><p lang="ja">${esc(w.example)}</p><small>${esc(w.example_translation)}</small></div>` : ''}<button class="btn ${progress.known.includes(w.id) ? 'soft' : ''}" data-known="${w.id}" aria-pressed="${progress.known.includes(w.id)}">${progress.known.includes(w.id) ? '✓ Sudah dikenal' : 'Tandai sudah dikenal'}</button></article>`;
  }
  function learn() {
    const tabs = `<div class="tabs"><button data-learn-view="current" class="${state.learnView === 'current' ? 'active' : ''}">Materi Haru</button><button data-learn-view="track" class="${state.learnView === 'track' ? 'active' : ''}">Jalur ${esc(track().name)}</button><button data-learn-view="all" class="${state.learnView === 'all' ? 'active' : ''}">Semua materi</button></div>`;
    const l = data.lessons.find(l => l.id === state.lessonId);
    if (l) return `${tabs}${title('Belajar · ' + (l.chapter ? 'Bab ' + l.chapter : 'Pemula'), l.title, l.summary)}<div class="grid2"><section class="panel stack"><div class="row"><span class="tag">${esc(levelOf(l))} · ${l.duration_minutes} menit</span><button class="textbtn" data-back-lessons>← Semua materi</button></div>${l.patterns?.length ? `<div class="tabs">${l.patterns.map(p => `<span class="badge">${esc(p)}</span>`).join('')}</div>` : ''}<div class="lesson-example"><p class="jp" lang="ja">${esc(l.japanese)}</p>${romaji(l.romaji)}<p>${esc(l.translation)}</p></div><p class="lesson-text">${esc(l.content)}</p><div class="quick-links"><button class="btn" data-lesson-audio="${l.id}">▶ Dengarkan contoh</button><button class="btn soft" data-complete="${l.id}">${progress.done.includes(l.id) ? '✓ Sudah dipelajari' : 'Tandai dipelajari'}</button></div><div class="quick-links"><button class="btn primary" data-lesson-practice="${l.id}">Latihan materi ini →</button><button class="btn" data-chat-lesson="${l.id}">Tanyakan materi ini</button></div>${l.review_status === 'needs_japanese_teacher_review' ? '<div class="notice">Rangkuman dan contoh orisinal Japantest, masih memerlukan tinjauan pengajar.</div>' : ''}${l.source_reference ? `<small>Acuan konsep: Minna no Nihongo ${l.chapter <= 25 ? 'I' : 'II'} · Bab ${l.chapter}. Pemetaan tingkat bersifat editorial.</small>` : ''}</section><section class="stack"><h2>Kosakata materi</h2>${data.words.filter(w => w.lesson_id === l.id).map(wordCard).join('') || '<section class="panel"><p class="muted">Kosakata khusus bab ini akan dilengkapi pengajar. Kata pemula tetap tersedia di menu Kosakata.</p>' + button('Buka Kosakata', 'words') + '</section>'}</section></div>`;
    const list = state.learnView === 'current' ? currentLessons() : state.learnView === 'all' ? data.lessons : data.lessons.filter(l => levelOf(l) === state.level);
    return `${tabs}${title('Belajar', state.learnView === 'current' ? 'Materi Haru yang sudah kamu kenal.' : state.learnView === 'all' ? 'Semua materi belajar.' : track().name, `${list.length} materi tersedia. Pilih satu bab, pahami konsepnya, lalu coba latihan.`)}<div class="grid2">${lessonCards(list)}</div>`;
  }
  function kana() {
    const chars = data.characters.filter(c => c.script === state.script);
    const map = new Map(chars.map(c => [c.romaji, c]));
    const rows = [['a','i','u','e','o'],['ka','ki','ku','ke','ko'],['sa','shi','su','se','so'],['ta','chi','tsu','te','to'],['na','ni','nu','ne','no'],['ha','hi','fu','he','ho'],['ma','mi','mu','me','mo'],['ya',null,'yu',null,'yo'],['ra','ri','ru','re','ro'],['wa',null,null,null,'wo'],['n',null,null,null,null]].flat();
    const tile = c => `<button class="kana-tile" data-kana="${c.id}" aria-label="${esc(c.symbol + ' dibaca ' + c.romaji)}"><span lang="ja">${esc(c.symbol)}</span>${romaji(c.romaji)}</button>`;
    return `${title('Huruf · Fondasi', 'Kenali huruf, kenali bunyinya.', 'Hiragana dan katakana tetap tersedia pada semua jalur belajar. Ketuk satu huruf untuk melihat cara bacanya.')}<div class="row"><div class="tabs">${['hiragana', 'katakana'].map(s => `<button data-script="${s}" class="${s === state.script ? 'active' : ''}">${s === 'hiragana' ? 'Hiragana' : 'Katakana'} · ${data.characters.filter(c => c.script === s).length}</button>`).join('')}</div><label><input type="checkbox" data-setting="romaji" ${progress.romaji ? 'checked' : ''}> Tampilkan romaji</label></div><section class="panel"><div class="kana-grid">${rows.map(r => map.has(r) ? tile(map.get(r)) : '<span aria-hidden="true"></span>').join('')}${chars.filter(c => !rows.includes(c.romaji)).map(tile).join('')}</div></section><div class="notice">を / ヲ lazim dilafalkan o; romaji wo membedakannya dari お / オ. Pelajari dakuten dan gabungan bunyi setelah huruf dasar.</div>${button('Lanjut ke kosakata →', 'words', 'primary')}`;
  }
  function wordResults() {
    const list = data.words.filter(w => (state.category === 'Semua' || w.category === state.category) && [w.japanese, w.reading, w.romaji, w.meaning, w.category, w.example, w.example_translation].join(' ').toLowerCase().includes(state.search.toLowerCase()));
    return `<p class="word-count muted">${list.length} dari ${data.words.length} kosakata · ${countKnown()} sudah dikenal</p><div class="grid3">${list.map(wordCard).join('') || '<p class="empty">Kosakata belum ditemukan. Coba pencarian lain.</p>'}</div>`;
  }
  function words() {
    return `${title('Kosakata · Haru', 'Satu kata baru setiap hari.', 'Cari Jepang, romaji, atau arti. Dengarkan lalu tandai kata yang sudah dikenal.')}<div class="tools"><label class="screen-reader" for="word-search">Cari kosakata</label><input id="word-search" type="search" placeholder="Cari Jepang, romaji, atau arti…" value="${esc(state.search)}"><label class="screen-reader" for="word-category">Kategori</label><select id="word-category">${['Semua', ...new Set(data.words.map(w => w.category))].map(c => `<option ${c === state.category ? 'selected' : ''}>${esc(c)}</option>`).join('')}</select><label><input type="checkbox" data-setting="romaji" ${progress.romaji ? 'checked' : ''}> Romaji</label></div><div id="word-results">${wordResults()}</div>`;
  }
  function questionPool() {
    return data.questions.filter(q => {
      const l = data.lessons.find(l => l.id === q.lesson_id);
      return (state.practiceView === 'current' ? !l?.reference_id : (q.level || levelOf(l || {})) === state.level) && (!state.practiceLesson || String(q.lesson_id) === state.practiceLesson) && (q.type !== 'listening' || q.audio_url);
    });
  }
  function practice() {
    const list = questionPool(); const q = list[state.qi % list.length];
    const tabs = `<div class="tabs"><button data-practice-view="current" class="${state.practiceView === 'current' ? 'active' : ''}">Latihan Haru</button><button data-practice-view="track" class="${state.practiceView === 'track' ? 'active' : ''}">Latihan ${esc(track().name)}</button></div>`;
    const lessons = data.lessons.filter(l => state.practiceView === 'current' ? !l.reference_id : levelOf(l) === state.level || data.questions.some(q => q.lesson_id === l.id && q.level === state.level));
    const filter = `<label>Materi <select id="practice-lesson"><option value="">Semua materi</option>${lessons.map(l => `<option value="${l.id}" ${String(l.id) === state.practiceLesson ? 'selected' : ''}>${esc(l.title)}</option>`).join('')}</select></label>`;
    if (!q) return `${tabs}${title('Latihan', 'Soal sedang disiapkan.', 'Pilih materi lain atau pelajari konsepnya terlebih dahulu.')}${filter}<section class="panel stack"><p class="muted">Belum ada soal yang dapat dimainkan pada pilihan ini. Soal menyimak tersedia setelah rekamannya diunggah.</p>${button('Lihat materi', 'learn', 'primary')}</section>`;
    return `${tabs}${title('Latihan', 'Pahami, coba jawab, ulangi.', 'Pembahasan muncul setelah jawaban diperiksa server.')}${filter}<section class="panel stack"><div class="row"><span class="tag">${esc(q.lesson_title)}</span><small>Soal ${state.qi % list.length + 1} / ${list.length}</small></div><p class="jp" lang="ja">${esc(q.prompt)}</p>${q.audio_url ? `<button class="btn" data-question-audio="${q.id}">▶ Dengarkan soal</button>` : ''}${q.options.map((a, i) => `<button class="btn answer ${state.selected === i ? 'chosen' : ''}" data-answer="${i}" ${state.feedback || state.busy ? 'disabled' : ''}>${i + 1}. ${esc(a)}</button>`).join('')}${state.feedback ? `<div class="feedback ${state.feedback.correct ? '' : 'wrong'}"><strong>${state.feedback.correct ? 'Benar!' : 'Mari pelajari jawabannya.'}</strong><p>${esc(state.feedback.explanation)}</p><p>Jawaban: ${esc(q.options[state.feedback.correct_index])}</p></div>` : ''}<div class="quick-links"><button class="btn primary" data-action="check" ${state.selected === null || state.feedback || state.busy ? 'disabled' : ''}>${state.busy ? 'Memeriksa…' : 'Periksa jawaban'}</button><button class="btn" data-action="next-question" ${state.busy ? 'disabled' : ''}>Soal berikutnya →</button></div>${q.review_status === 'needs_japanese_teacher_review' ? '<small>Soal orisinal · materi awal perlu tinjauan pengajar</small>' : ''}</section>`;
  }
  async function check() {
    if (state.busy || state.feedback || state.selected === null) return;
    const q = questionPool()[state.qi % questionPool().length]; const answer = state.selected; const g = generation;
    state.busy = true; render();
    try {
      const result = await request(data.answer_base + '/' + q.id + '/answer', {answer_index: answer});
      if (g !== generation) return;
      state.feedback = result;
      progress.scores.push({score: result.correct ? 1 : 0, total: 1, date: new Date().toISOString(), title: q.prompt, level: q.level || 'Pemula Haru'}); progress.scores = progress.scores.slice(-100); persist();
    } catch (e) { if (g === generation) toast(e.message); }
    finally { if (g === generation) { state.busy = false; render(); } }
  }
  function exam() {
    const a = state.exam;
    if (a?.result) return examResult(a);
    if (a?.status === 'in_progress') return examActive(a);
    const list = data.templates.filter(t => t.level === state.level); const mini = list.filter(t => t.mode === 'mini' && t.status === 'published'); const blueprint = list.find(t => t.mode === 'blueprint');
    return `${title('Virtual test · ' + track().name, 'Latih fokus. Ukur pemahamanmu.', 'Mini test memakai timer server, menyimpan jawaban, dan dapat dilanjutkan setelah halaman dimuat ulang pada sesi browser yang sama.')}<div class="grid2">${mini.map(t => `<section class="panel stack"><span class="tag">Mini test · ${t.duration_seconds / 60} menit</span><h2>${esc(t.title)}</h2><p class="muted">${esc(t.description)}</p><button class="btn primary" data-start-test="${t.id}" ${state.examBusy ? 'disabled' : ''}>${state.examBusy ? 'Menyiapkan…' : 'Mulai mini test →'}</button></section>`).join('') || '<section class="panel stack"><h2>Evaluasi sedang disiapkan</h2><p class="muted">' + (state.level === 'foundation' ? 'Mulai dengan huruf dan latihan pemula, lalu pilih N5 untuk mini test.' : state.level === 'pro' ? 'Jalur Profesional akan memakai proyek presentasi, email, dan rubrik keterampilan.' : 'Bank soal tingkat ini akan dilengkapi sebelum virtual test tersedia.') + '</p>' + button('Buka latihan', 'practice', 'soft') + '</section>'}${blueprint ? `<section class="panel stack"><div class="row"><h2>${esc(blueprint.title)}</h2><span class="tag warm">Belum tersedia</span></div><div class="exam-sections">${blueprint.sections.map((s, i) => `<div class="exam-section"><span><small>Sesi ${i + 1}</small><br>${esc(s.name_id)}</span><strong>${s.minutes} mnt</strong></div>`).join('')}</div><p class="muted">${esc(blueprint.description)}</p><small>Waktu jeda belum termasuk. Durasi menyimak dapat berbeda menurut rekaman.</small>${blueprint.official_score_reference ? `<div class="notice">Acuan resmi: ambang total ${blueprint.official_score_reference.overall_pass_mark}/180. ${esc(blueprint.official_score_reference.sectional_note)} Skor resmi berskala; akurasi mini test tidak dikonversi menjadi skor atau keputusan lulus JLPT.</div>` : ''}</section>` : ''}</div><section class="panel stack"><h3>Riwayat mini virtual test</h3>${(progress.exams || []).slice(-5).reverse().map(r => `<div class="list-row"><div class="grow">${esc(r.title)}<br><small>${new Date(r.date).toLocaleDateString('id-ID')} · ${r.correct}/${r.total} benar</small></div><button class="textbtn" data-result-test="${esc(r.id)}">Pembahasan →</button></div>`).join('') || '<p class="muted">Belum ada tes selesai di browser ini.</p>'}</section>`;
  }
  function examActive(a) {
    const q = a.questions[state.examIndex] || a.questions[0]; const answer = a.answers[q.id];
    return `${title(a.title, 'Kerjakan dengan tenang.', 'Jawaban dan waktu tersimpan di server. Pembahasan terbuka setelah seluruh mini test selesai.')}<section class="panel stack"><div class="row"><span class="tag">Soal ${state.examIndex + 1} / ${a.questions.length}</span><span class="timer" id="exam-timer" role="timer">${timeLeft()}</span></div><div class="question-nav">${a.questions.map((q, i) => `<button class="btn ${i === state.examIndex ? 'primary' : a.answers[q.id] != null ? 'soft' : ''}" data-exam-index="${i}" ${state.examBusy ? 'disabled' : ''} aria-label="Soal ${i + 1}${a.answers[q.id] != null ? ', sudah dijawab' : ''}">${i + 1}${a.answers[q.id] != null ? ' ✓' : ''}</button>`).join('')}</div><p class="jp" lang="ja">${esc(q.prompt)}</p>${q.audio_url ? `<button class="btn" data-exam-audio>▶ Dengarkan</button>` : ''}${q.options.map((c, i) => `<button class="btn answer ${answer === i ? 'chosen' : ''}" data-exam-answer="${i}" ${state.examBusy ? 'disabled' : ''}>${i + 1}. ${esc(c)}</button>`).join('')}<div class="row"><small>${Object.keys(a.answers).length} / ${a.questions.length} dijawab · ${state.examBusy ? 'Menyimpan…' : 'Tersimpan di server'}</small><button class="btn primary" data-action="finish-test" ${state.examBusy ? 'disabled' : ''}>Selesaikan mini test</button></div></section>`;
  }
  function examResult(a) {
    const r = a.result;
    return `${title(a.title, a.status === 'timed_out' ? 'Waktu selesai. Lihat pembahasanmu.' : 'Selesai. Saatnya belajar dari hasil.')}<section class="panel stack"><span class="result">${r.correct}/${r.total}</span><h2>${r.accuracy}% jawaban benar</h2><p>${esc(r.recommendation)}</p><div class="skills">${Object.entries(r.skills).map(([skill, s]) => `<div class="skill"><small>${esc(({grammar: 'Tata bahasa', vocabulary: 'Kosakata', reading: 'Membaca', listening: 'Menyimak'})[skill] || skill)}</small><strong>${s.correct}/${s.total}</strong></div>`).join('')}</div><div class="notice">Hasil ini adalah akurasi mini test. Bukan skor 0–180, prediksi lulus, atau sertifikat JLPT.</div><button class="btn" data-action="close-test">Kembali ke daftar tes</button></section><section class="stack"><h2>Pembahasan soal</h2>${r.reviews.map((q, i) => `<article class="panel stack"><span class="tag">Soal ${i + 1} · ${q.correct ? 'Benar' : q.answer_index == null ? 'Belum dijawab' : 'Perlu pengulangan'}</span><p class="jp" lang="ja">${esc(q.prompt)}</p><p>Jawabanmu: ${q.answer_index == null ? '—' : esc(q.options[q.answer_index])}</p><p><strong>Jawaban benar: ${esc(q.options[q.correct_index])}</strong></p><p class="muted">${esc(q.explanation)}</p></article>`).join('')}</section>`;
  }
  const attemptKey = 'haru-virtual-test-active';
  function acceptAttempt(a) {
    state.exam = a; state.examDeadline = Date.now() + a.remaining_seconds * 1000;
    try { if (a.status === 'in_progress') localStorage.setItem(attemptKey, a.id); else localStorage.removeItem(attemptKey); } catch (_) {}
    if (a.result) { progress.exams ||= []; if (!progress.exams.some(r => r.id === a.id)) { progress.exams.push({id: a.id, title: a.title, correct: a.result.correct, total: a.result.total, date: new Date().toISOString()}); progress.exams = progress.exams.slice(-100); persist(); } }
    clearInterval(timer);
    if (a.status === 'in_progress') timer = setInterval(() => { const el = root.querySelector('#exam-timer'); if (el) el.textContent = timeLeft(); if (Date.now() >= state.examDeadline && !state.examBusy) finishTest(true); }, 1000);
  }
  function timeLeft() { const seconds = Math.max(0, Math.ceil((state.examDeadline - Date.now()) / 1000)); return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`; }
  async function startTest(id) {
    if (state.examBusy) return; state.examBusy = true; render();
    try { const a = await request(data.exam_base + '/templates/' + id + '/start', {}); state.examIndex = 0; acceptAttempt(a); state.page = 'exam'; }
    catch (e) { toast(e.message); } finally { state.examBusy = false; render(); }
  }
  async function saveExamAnswer(index) {
    if (state.examBusy || !state.exam) return;
    const a = state.exam; const q = a.questions[state.examIndex]; state.examBusy = true; render();
    try { acceptAttempt(await request(data.exam_base + '/attempts/' + a.id + '/answers', {answers: {[q.id]: index}})); }
    catch (e) { toast(e.message + ' Jawaban belum tersimpan; coba kembali.'); } finally { state.examBusy = false; render(); }
  }
  async function finishTest(expired = false) {
    if (state.examBusy || !state.exam || state.exam.status !== 'in_progress') return;
    if (!expired && !confirm('Selesaikan mini test? Jawaban tidak dapat diubah setelah selesai.')) return;
    state.examBusy = true; render();
    try { acceptAttempt(await request(data.exam_base + '/attempts/' + state.exam.id + '/finish', {answers: {}})); }
    catch (e) { toast(e.message); state.examDeadline = Date.now() + 10000; } finally { state.examBusy = false; render(); }
  }
  async function loadAttempt(id, open = true) {
    state.examBusy = true;
    try { const a = await request(data.exam_base + '/attempts/' + id); state.examIndex = 0; acceptAttempt(a); if (open) { state.page = 'exam'; state.level = a.level; } }
    catch (e) { try { localStorage.removeItem(attemptKey); } catch (_) {} toast('Tes tidak dapat dipulihkan: ' + e.message); }
    finally { state.examBusy = false; render(); }
  }
  function chat() {
    const c = chatState, lesson = data.lessons.find(l => l.id === c.lessonId);
    const examples = lesson?.chapter ? ['Apa contoh kalimat Bab ' + lesson.chapter + '?', 'Bagaimana belajar materi Bab ' + lesson.chapter + '?'] : c.level === 'N4' ? ['Apa fungsi んです?', 'Apa itu bentuk potensial?', 'Bagaimana menggunakan ながら?'] : c.level === 'N5' ? ['Apa fungsi partikel は?', 'Apa perbedaan あります dan います?', 'Apa perbedaan kata sifat い dan な?'] : ['Apa itu hiragana?', 'Bagaimana mulai belajar bahasa Jepang?', 'Apa arti コーヒー (koohii)?'];
    return `${title('Tanya Materi · tanpa AI', 'Ada yang belum dipahami?', 'Cari jawaban dari bank materi Japantest. Ajukan satu topik per pertanyaan; tersedia Fondasi, Bab 1–10 (N5), dan dasar Bab 26–30 (N4).')}<section class="panel stack"><div class="row"><label>Tingkat <select id="chat-level">${['foundation','N5','N4'].map(l => `<option value="${l}" ${c.level === l ? 'selected' : ''}>${l === 'foundation' ? 'Fondasi / pemula' : l}</option>`).join('')}</select></label><span class="tag" id="chat-quota">${c.quota ? c.quota.remaining + ' / ' + c.quota.limit + ' pertanyaan tersisa hari ini' : 'Memuat kuota…'}</span></div>${lesson ? `<div class="row"><span>Sumber dipilih: ${esc(lesson.title)}</span><button class="textbtn" data-clear-chat-lesson>Semua materi tingkat ini</button></div>` : ''}<small>Kuota diperbarui pukul 00.00 WIB per sesi browser. Pertanyaan, jawaban, dan laporan disimpan di server serta dapat ditinjau pengajar. Jangan kirim data pribadi.</small><div class="quick-links">${examples.map(q => `<button class="btn soft" data-chat-example="${esc(q)}" ${c.busy || c.loading || c.quota?.remaining === 0 ? 'disabled' : ''}>${esc(q)}</button>`).join('')}</div></section><section class="stack chat-history" aria-label="Riwayat tanya materi" aria-live="polite">${c.loading ? '<p role="status">Memuat riwayat…</p>' : ''}${c.messages.map(m => {
      const r = m.response;
      return `<article class="panel stack chat-message"><div class="chat-question"><small>Pertanyaanmu · ${esc(m.level === 'foundation' ? 'Fondasi' : m.level)}</small><p>${esc(m.question)}</p></div><div class="chat-answer"><strong>${r.matched ? 'Jawaban dari bank materi' : 'Mari cari materi terkait'}</strong><p class="lesson-text">${esc(r.answer)}</p></div>${r.source ? `<div class="row"><span class="tag">Sumber: ${esc(r.source.label)}</span>${r.source.lesson_id ? `<button class="textbtn" data-lesson="${r.source.lesson_id}">Buka materi sumber →</button>` : button('Buka Huruf', 'kana')}</div>${r.review_status !== 'reviewed' ? '<small>Jawaban awal · masih memerlukan tinjauan pengajar.</small>' : '<small>Sudah ditinjau pengajar.</small>'}` : ''}${r.suggestions?.length ? `<div class="quick-links">${r.suggestions.filter(q => q.question !== m.question).map(q => `<button class="btn" data-chat-example="${esc(q.question)}" ${c.busy || c.quota?.remaining === 0 ? 'disabled' : ''}>${esc(q.question)}</button>`).join('')}</div>` : ''}${(r.related_lessons || []).map(l => `<button class="textbtn" data-lesson="${l.lesson_id}">${esc(l.title)} →</button>`).join('')}${m.reported ? '<small role="status">Laporan sudah diterima pengajar.</small>' : `<details><summary>Laporkan jawaban keliru</summary><form class="stack chat-report" data-chat-report="${esc(m.id)}"><label>Bagian yang perlu diperbaiki<textarea name="reason" minlength="5" maxlength="2000" rows="2" required placeholder="Jelaskan kekeliruan atau materi yang kurang…"></textarea></label><button class="btn" ${c.reporting ? 'disabled' : ''}>${c.reporting === m.id ? 'Mengirim…' : 'Kirim laporan'}</button></form></details>`}</article>`;
    }).join('') || (!c.loading ? '<p class="muted">Belum ada percakapan. Pilih contoh pertanyaan atau tulis pertanyaanmu.</p>' : '')}</section><form id="chat-form" class="panel stack"><label for="chat-question">Pertanyaan tentang materi<textarea id="chat-question" name="question" minlength="2" maxlength="1000" rows="3" required placeholder="Contoh: Apa fungsi partikel wa?">${esc(c.draft)}</textarea></label>${c.error ? `<p class="chat-error" role="alert">${esc(c.error)}</p>` : ''}<div class="row"><small>Jawaban ditampilkan apa adanya dari database. Jika belum cocok, pertanyaan masuk antrean pengajar.</small><button class="btn primary" ${c.busy || c.loading || !c.loaded || c.quota?.remaining === 0 ? 'disabled' : ''}>${c.busy ? 'Mencari jawaban…' : 'Kirim pertanyaan'}</button></div></form>`;
  }
  async function loadChat() {
    if (chatState.loading) return;
    chatState.loading = true; if (state.page === 'chat') render();
    try { const r = await request(data.chat_base); chatState.messages = r.messages; chatState.quota = r.quota; chatState.loaded = true; chatState.error = ''; }
    catch (e) { chatState.error = e.message; }
    finally { chatState.loading = false; if (state.page === 'chat') render(); }
  }
  function uuid() {
    if (window.crypto?.randomUUID) return crypto.randomUUID();
    return '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16));
  }
  async function askChat(question) {
    const c = chatState;
    if (c.busy || c.loading || !c.loaded || c.quota?.remaining === 0) return;
    question = question.trim(); if (question.length < 2) return;
    const body = {question, level: c.level, lesson_id: c.lessonId};
    const signature = JSON.stringify(body);
    if (c.pending?.signature !== signature) c.pending = {signature, body: {...body, request_id: uuid()}};
    c.draft = question; c.busy = true; c.error = ''; if (state.page === 'chat') render();
    try {
      const r = await request(data.chat_base, c.pending.body);
      c.messages = [...c.messages.filter(m => m.id !== r.message.id), r.message].slice(-10); c.quota = r.quota; c.draft = ''; c.pending = null;
    } catch (e) { await loadChat(); c.error = e.message; }
    finally { c.busy = false; if (state.page === 'chat') { render(); root.querySelector('#chat-question')?.focus(); } }
  }
  root.addEventListener('submit', async e => {
    const form = e.target;
    if (form.id === 'chat-form') { e.preventDefault(); askChat(form.elements.question.value); return; }
    if (!form.dataset.chatReport) return;
    e.preventDefault(); if (chatState.reporting) return;
    const id = form.dataset.chatReport, reason = form.elements.reason.value;
    chatState.reporting = id; form.querySelector('button').disabled = true;
    try { await request(data.chat_base + '/' + id + '/report', {reason}); const m = chatState.messages.find(m => m.id === id); if (m) m.reported = true; toast('Laporan disimpan untuk diperiksa pengajar.'); chatState.reporting = null; if (state.page === 'chat') render(); }
    catch (err) { toast(err.message); }
    finally { chatState.reporting = null; if (form.isConnected) form.querySelector('button').disabled = false; }
  });
  function profile() {
    const total = progress.scores.reduce((n, s) => n + s.total, 0), right = progress.scores.reduce((n, s) => n + s.score, 0);
    return `${title('Profil · Pelajar Haru', 'Lihat kemajuanmu.', 'Progres Huruf, Kosakata, dan Belajar tetap tersimpan pada browser ini.')}<div class="skills"><section class="skill"><small>Materi dipelajari</small><strong>${completed()} / ${data.lessons.length}</strong></section><section class="skill"><small>Kosakata dikenal</small><strong>${countKnown()} / ${data.words.length}</strong></section><section class="skill"><small>Latihan dijawab</small><strong>${total}</strong></section><section class="skill"><small>Akurasi latihan</small><strong>${total ? Math.round(right / total * 100) + '%' : 'Belum ada'}</strong></section></div><div class="grid2"><section class="panel stack"><h2>Preferensi belajar</h2><label><input type="checkbox" data-setting="romaji" ${progress.romaji ? 'checked' : ''}> Tampilkan romaji</label><label>Target harian <select id="daily-target">${[5,10,15,20].map(n => `<option value="${n}" ${progress.target === n ? 'selected' : ''}>${n} menit</option>`).join('')}</select></label><p class="muted">Target adalah pengaturan; durasi belajar belum dilacak otomatis.</p>${button('Lanjutkan belajar', 'learn', 'primary')}${button('Reset progres belajar', 'reset')}<small>Progres belajar lokal belum disinkronkan ke akun. Jawaban virtual test tersimpan pada sesi browser di server.</small></section><section class="panel stack"><h2>Riwayat latihan</h2>${progress.scores.slice(-5).reverse().map(s => `<div class="list-row"><span class="number">${s.score === s.total ? '✓' : '↻'}</span><div class="grow">${esc(s.title || 'Kuis Haru')}<br><small>${new Date(s.date).toLocaleDateString('id-ID')} · ${s.score}/${s.total} benar</small></div></div>`).join('') || '<p class="empty">Belum ada latihan dijawab.</p>'}<h3>Materi selesai</h3>${data.lessons.filter(l => progress.done.includes(l.id)).map(l => `<button class="textbtn" data-lesson="${l.id}">✓ ${esc(l.title)}</button>`).join('') || '<p class="muted">Mulai satu pelajaran untuk melihat progres.</p>'}${button('Riwayat virtual test', 'exam')}</section></div>`;
  }
  function render() {
    picker.value = state.level;
    root.querySelectorAll('[data-nav]').forEach(b => { b.classList.toggle('active', b.dataset.nav === state.page); if (b.dataset.nav === state.page) b.setAttribute('aria-current', 'page'); else b.removeAttribute('aria-current'); });
    out.innerHTML = ({home, path, learn, kana, words, practice, exam, chat, progress: profile})[state.page]();
  }
  function clearQuestion() { generation++; state.selected = null; state.feedback = null; state.busy = false; }
  let modalFocus = null;
  function openKana(id) {
    const c = data.characters.find(c => c.id === id); if (!c) return;
    modalFocus = document.activeElement; const el = document.createElement('div'); el.className = 'modal-backdrop';
    el.innerHTML = `<section class="panel kana-modal stack" role="dialog" aria-modal="true" aria-labelledby="kana-modal-title"><span class="eyebrow" id="kana-modal-title">Kenali bunyinya · ${esc(c.script)}</span><div class="big-kana" lang="ja">${esc(c.symbol)}</div><h2>${esc(c.romaji)}</h2><div class="row"><button class="btn primary" data-kana-audio="${c.id}">▶ Dengarkan</button><button class="btn" data-close-kana>Tutup</button></div><small>Audio rekaman jika tersedia; jika belum, suara Jepang sintetis perangkat.</small></section>`;
    root.append(el); el.querySelector('[data-close-kana]').focus(); el.onclick = e => { if (e.target === el) closeKana(); };
  }
  function closeKana() { root.querySelector('.modal-backdrop')?.remove(); modalFocus?.focus(); }
  root.addEventListener('keydown', e => {
    const modal = root.querySelector('.modal-backdrop'); if (!modal) return;
    if (e.key === 'Escape') closeKana();
    if (e.key === 'Tab') { const bs = modal.querySelectorAll('button'); if (e.shiftKey && document.activeElement === bs[0]) { e.preventDefault(); bs[bs.length - 1].focus(); } else if (!e.shiftKey && document.activeElement === bs[bs.length - 1]) { e.preventDefault(); bs[0].focus(); } }
  });
  root.addEventListener('input', e => { if (e.target.id === 'chat-question') chatState.draft = e.target.value; if (e.target.id === 'word-search') { state.search = e.target.value; root.querySelector('#word-results').innerHTML = wordResults(); } });
  root.addEventListener('change', e => {
    const t = e.target;
    if (t.id === 'chat-level') { chatState.level = t.value; chatState.lessonId = null; render(); }
    if (t.dataset.setting === 'romaji') { progress.romaji = t.checked; persist(); render(); }
    if (t.id === 'word-category') { state.category = t.value; root.querySelector('#word-results').innerHTML = wordResults(); }
    if (t.id === 'daily-target') { progress.target = +t.value; persist(); render(); }
    if (t.id === 'practice-lesson') { state.practiceLesson = t.value; state.qi = 0; clearQuestion(); render(); }
  });
  picker.addEventListener('change', () => { state.level = picker.value; state.lessonId = null; state.learnView = state.level === 'foundation' ? 'current' : 'track'; state.practiceView = state.level === 'foundation' ? 'current' : 'track'; state.practiceLesson = ''; state.qi = 0; clearQuestion(); render(); });
  root.addEventListener('click', e => {
    const b = e.target.closest('button'); if (!b || b.disabled) return; const d = b.dataset;
    if (d.nav) { go(d.nav); return; }
    if (d.track) { state.level = d.track; state.learnView = 'track'; state.lessonId = null; state.practiceView = 'track'; state.practiceLesson = ''; state.qi = 0; clearQuestion(); go('learn'); return; }
    if (d.chatLesson) { const l = data.lessons.find(l => l.id === +d.chatLesson); if (!['foundation','N5','N4'].includes(levelOf(l))) { toast('Chat tersedia untuk Fondasi, N5, dan N4.'); return; } chatState.lessonId = l.id; chatState.level = levelOf(l); go('chat'); return; }
    if ('clearChatLesson' in d) { chatState.lessonId = null; render(); return; }
    if (d.chatExample) { askChat(d.chatExample); return; }
    if (d.lesson) { state.lessonId = +d.lesson; go('learn'); return; }
    if (d.learnView) { state.learnView = d.learnView; state.lessonId = null; render(); return; }
    if ('backLessons' in d) { state.lessonId = null; render(); return; }
    if (d.complete) { if (!progress.done.includes(+d.complete)) progress.done.push(+d.complete); persist(); render(); return; }
    if (d.lessonPractice) { const l = data.lessons.find(l => l.id === +d.lessonPractice); state.level = levelOf(l); state.practiceView = l.reference_id ? 'track' : 'current'; state.practiceLesson = d.lessonPractice; state.qi = 0; clearQuestion(); go('practice'); return; }
    if (d.practiceView) { state.practiceView = d.practiceView; state.practiceLesson = ''; state.qi = 0; clearQuestion(); render(); return; }
    if (d.script) { state.script = d.script; render(); return; }
    if (d.kana) { openKana(+d.kana); return; }
    if ('closeKana' in d) { closeKana(); return; }
    if (d.kanaAudio) { const c = data.characters.find(c => c.id === +d.kanaAudio); play(c.symbol, c.audio_url); return; }
    if (d.wordAudio) { const w = data.words.find(w => w.id === +d.wordAudio); play(w.japanese, w.audio_url); return; }
    if (d.lessonAudio) { const l = data.lessons.find(l => l.id === +d.lessonAudio); play(l.japanese, l.audio_url); return; }
    if (d.questionAudio) { const q = data.questions.find(q => q.id === +d.questionAudio); play('', q.audio_url); return; }
    if (d.known) { const id = +d.known; progress.known = progress.known.includes(id) ? progress.known.filter(n => n !== id) : [...progress.known, id]; persist(); render(); return; }
    if (d.answer != null) { state.selected = +d.answer; render(); return; }
    if (d.startTest) { startTest(+d.startTest); return; }
    if (d.resultTest) { loadAttempt(d.resultTest); return; }
    if (d.examAnswer != null) { saveExamAnswer(+d.examAnswer); return; }
    if (d.examIndex != null) { state.examIndex = +d.examIndex; render(); return; }
    if ('examAudio' in d) { const q = state.exam.questions[state.examIndex]; play('', q.audio_url); return; }
    const action = d.action;
    if (['home','path','learn','kana','words','practice','exam','chat','progress'].includes(action)) { go(action); return; }
    if (action === 'check') check();
    if (action === 'next-question') { state.qi++; clearQuestion(); render(); }
    if (action === 'finish-test') finishTest();
    if (action === 'close-test') { state.exam = null; render(); }
    if (action === 'reset' && confirm('Reset progres belajar dan preferensi pada browser ini? Riwayat tes di server tetap tersedia pada sesi yang sama.')) { Object.assign(progress, {done: [], known: [], scores: [], romaji: true, target: 10, exams: []}); persist(); render(); }
  });
  render();
  try { const id = localStorage.getItem(attemptKey); if (id) loadAttempt(id); } catch (_) {}
})();
