import 'package:flutter/material.dart';

import '../main.dart';
import '../services/api.dart';
import '../services/progress.dart';
import '../services/audio.dart';
import 'widgets.dart';
import 'lesson.dart';
import 'quiz.dart';

class LearningShell extends StatefulWidget {
  const LearningShell({super.key});
  @override
  State<LearningShell> createState() => _LearningShellState();
}

class _LearningShellState extends State<LearningShell> {
  final api = LearningApi();
  final progress = LearningProgress();
  final audio = LearningAudio();
  List<Json> lessons = [], words = [], characters = [];
  bool loading = true;
  String? error;
  int page = 0;
  String script = 'hiragana', search = '', category = 'Semua';
  final searchController = TextEditingController();
  static const labels = [
    'Beranda',
    'Belajar',
    'Huruf',
    'Kosakata',
    'Latihan',
    'Profil',
  ];
  static const icons = [
    Icons.home_outlined,
    Icons.auto_stories_outlined,
    Icons.translate_rounded,
    Icons.style_outlined,
    Icons.quiz_outlined,
    Icons.person_outline,
  ];
  @override
  void initState() {
    super.initState();
    progress.addListener(_changed);
    _load();
  }

  void _changed() {
    if (mounted) setState(() {});
  }

  Future<void> _load() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      await progress.load();
      final result = await Future.wait([
        api.collection('lessons'),
        api.collection('vocabularies'),
        api.collection('characters'),
      ]);
      if (!mounted) return;
      setState(() {
        lessons = result[0];
        words = result[1];
        characters = result[2];
        loading = false;
      });
    } catch (e) {
      if (mounted) {
        setState(() {
          error = '$e';
          loading = false;
        });
      }
    }
  }

  void message(String text) {
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(text)));
    }
  }

  Future<void> save(Future<void> action) async {
    try {
      await action;
    } catch (_) {
      message('Progres belum berhasil disimpan pada perangkat.');
    }
  }

  Future<void> play(Json item, String type, String text) async {
    try {
      await audio.play(text, api.audioUrl(item, type));
    } catch (e) {
      message('$e');
    }
  }

  void navigate(int index) {
    audio.stop();
    setState(() => page = index);
  }

  Future<void> openLesson(Json lesson) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => LessonScreen(
          api: api,
          lessonId: lesson['id'] as int,
          progress: progress,
          audio: audio,
        ),
      ),
    );
    if (mounted) setState(() {});
  }

  Future<void> openQuiz([int? lessonId]) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => QuizScreen(
          api: api,
          lessons: lessonId == null
              ? lessons
              : lessons.where((l) => l['id'] == lessonId).toList(),
          progress: progress,
          audio: audio,
        ),
      ),
    );
    if (mounted) setState(() {});
  }

  @override
  void dispose() {
    searchController.dispose();
    progress.removeListener(_changed);
    progress.dispose();
    audio.dispose();
    api.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final wide = MediaQuery.sizeOf(context).width >= 900;
    Widget content;
    if (loading) {
      content = const Center(child: CircularProgressIndicator());
    } else if (error != null) {
      content = Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.wifi_off_rounded, size: 42, color: green),
              const SizedBox(height: 14),
              Text(error!, textAlign: TextAlign.center),
              const SizedBox(height: 18),
              FilledButton(onPressed: _load, child: const Text('Coba lagi')),
            ],
          ),
        ),
      );
    } else {
      content = RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: EdgeInsets.all(wide ? 32 : 20),
          children: [
            switch (page) {
              0 => home(),
              1 => learn(),
              2 => kana(),
              3 => vocabulary(),
              4 => practice(),
              _ => profile(),
            },
          ],
        ),
      );
    }
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'は haru.',
          style: TextStyle(fontWeight: FontWeight.w800),
        ),
        actions: [
          const Padding(
            padding: EdgeInsets.all(8),
            child: Tag('Jepang · Pemula'),
          ),
          IconButton(
            onPressed: loading ? null : _load,
            tooltip: 'Muat ulang materi',
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: Row(
        children: [
          if (wide) sidebar(),
          Expanded(child: content),
        ],
      ),
      bottomNavigationBar: wide ? null : bottomNav(),
    );
  }

  Widget sidebar() => SizedBox(
        width: 230,
        child: Container(
          color: Colors.white,
          padding: const EdgeInsets.all(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 24),
                child: Text(
                  'Belajar Minna no Nihongo',
                  style: TextStyle(color: green, fontWeight: FontWeight.w600),
                ),
              ),
              ...List.generate(
                labels.length,
                (i) => Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: ListTile(
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    selected: page == i,
                    selectedTileColor: pale,
                    leading: Icon(icons[i]),
                    title: Text(labels[i]),
                    onTap: () => navigate(i),
                  ),
                ),
              ),
              const Spacer(),
              const Text(
                'Pelan-pelan, pasti bisa 🌱',
                style: TextStyle(color: green),
              ),
            ],
          ),
        ),
      );
  Widget bottomNav() => SafeArea(
        child: Container(
          color: Colors.white,
          child: Row(
            children: List.generate(
              labels.length,
              (i) => Expanded(
                child: Semantics(
                  selected: page == i,
                  button: true,
                  label: labels[i],
                  child: InkWell(
                    onTap: () => navigate(i),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 11),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(icons[i],
                              color: page == i ? green : Colors.grey),
                          const SizedBox(height: 4),
                          Text(
                            labels[i],
                            style: TextStyle(
                              fontSize: 10,
                              color: page == i ? green : Colors.grey,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      );
  Widget cards() {
    if (lessons.isEmpty) return const EmptyMaterial();
    return LayoutBuilder(
      builder: (context, box) {
        final width =
            box.maxWidth > 550 ? (box.maxWidth - 16) / 2 : box.maxWidth;
        return Wrap(
          spacing: 16,
          runSpacing: 16,
          children: lessons.map((l) {
            final done = progress.done.contains(l['id']);
            return SizedBox(
              width: width,
              child: InkWell(
                borderRadius: BorderRadius.circular(20),
                onTap: () => openLesson(l),
                child: LearningCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Tag(done ? '✓ Selesai' : 'Pemula'),
                      const SizedBox(height: 16),
                      Text(
                        '${l['title']}',
                        style: const TextStyle(
                          fontSize: 19,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        '${l['summary']}',
                        style: const TextStyle(color: Colors.grey, height: 1.5),
                      ),
                      const SizedBox(height: 20),
                      LinearProgressIndicator(
                        value: done ? 1 : 0,
                        color: green,
                        backgroundColor: pale,
                      ),
                      const SizedBox(height: 12),
                      Text(
                        '${l['duration_minutes']} menit · ${done ? 'Pelajari kembali' : 'Mulai belajar'} →',
                        style: const TextStyle(fontSize: 12, color: green),
                      ),
                    ],
                  ),
                ),
              ),
            );
          }).toList(),
        );
      },
    );
  }

  Widget home() {
    final completed =
        lessons.where((l) => progress.done.contains(l['id'])).length;
    final mastered =
        words.where((w) => progress.known.contains(w['id'])).length;
    final next =
        lessons.where((l) => !progress.done.contains(l['id'])).firstOrNull ??
            lessons.firstOrNull;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const PageTitle(
          'こんにちは, teman belajar! 🌿',
          'Mulai dari satu kata. Bangun kebiasaan setiap hari.',
        ),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(28),
          decoration: BoxDecoration(
            color: green,
            borderRadius: BorderRadius.circular(22),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'BELAJAR MINNA NO NIHONGO',
                style: TextStyle(
                  color: Colors.white70,
                  fontSize: 12,
                  letterSpacing: 1.5,
                ),
              ),
              const SizedBox(height: 18),
              const Text(
                'Perjalanan kecil,\nkemampuan baru.',
                style: TextStyle(
                  fontSize: 30,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 12),
              const Text(
                'Kenali huruf, dengarkan pengucapan, dan coba kalimat pertamamu.',
                style: TextStyle(color: Colors.white70, height: 1.6),
              ),
              const SizedBox(height: 22),
              FilledButton.tonal(
                onPressed: next == null ? null : () => openLesson(next),
                child: Text(
                  completed > 0 ? 'Lanjutkan belajar ↗' : 'Mulai pelajaran ↗',
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 20),
        Row(
          children: [
            stat('$completed/${lessons.length}', 'Pelajaran'),
            const SizedBox(width: 8),
            stat('$mastered', 'Kosakata'),
            const SizedBox(width: 8),
            stat('${progress.scores.length}', 'Latihan'),
          ],
        ),
        const SizedBox(height: 24),
        Row(
          children: [
            const Expanded(
              child: Text(
                'Pelajaran untukmu',
                style: TextStyle(fontSize: 21, fontWeight: FontWeight.w700),
              ),
            ),
            TextButton(
              onPressed: () => navigate(1),
              child: const Text('Lihat semua →'),
            ),
          ],
        ),
        const SizedBox(height: 12),
        cards(),
        const SizedBox(height: 22),
        LearningCard(
          color: pale,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Tag('TARGET BELAJAR'),
              const SizedBox(height: 12),
              const Text(
                'Sedikit, tetapi rutin.',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
              ),
              Text('Luangkan ${progress.target} menit untuk belajar hari ini.'),
              const SizedBox(height: 8),
              const Text(
                'Target pengingat; durasi belum dihitung.',
                style: TextStyle(fontSize: 12, color: Colors.grey),
              ),
            ],
          ),
        ),
        if (words.isNotEmpty) ...[
          const SizedBox(height: 18),
          LearningCard(
            child: Column(
              children: [
                const Tag('KATA PILIHAN'),
                const SizedBox(height: 12),
                JapaneseText('${words.first['japanese']}'),
                if (progress.romaji)
                  Text(
                    '${words.first['romaji']}',
                    style: const TextStyle(color: Colors.grey),
                  ),
                Text('${words.first['meaning']}'),
                AudioButton(
                  () => play(
                    words.first,
                    'vocabularies',
                    '${words.first['japanese']}',
                  ),
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }

  Widget stat(String value, String label) => Expanded(
        child: LearningCard(
          padding: const EdgeInsets.all(12),
          child: Column(
            children: [
              Text(
                value,
                style:
                    const TextStyle(fontSize: 23, fontWeight: FontWeight.w700),
              ),
              Text(label, style: const TextStyle(fontSize: 12)),
            ],
          ),
        ),
      );
  Widget learn() => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const PageTitle(
            'Belajar selangkah demi selangkah',
            'Dengarkan, pahami, dan coba latihan setelah belajar.',
          ),
          cards(),
        ],
      );
  Widget kana() {
    final list = characters.where((c) => c['script'] == script).toList();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const PageTitle(
          'Kenali huruf Jepang',
          'Ketuk huruf untuk melihat cara baca dan mendengarkan.',
        ),
        SegmentedButton<String>(
          segments: const [
            ButtonSegment(value: 'hiragana', label: Text('Hiragana')),
            ButtonSegment(value: 'katakana', label: Text('Katakana')),
          ],
          selected: {script},
          onSelectionChanged: (s) => setState(() => script = s.first),
        ),
        const SizedBox(height: 20),
        if (list.isEmpty)
          const EmptyMaterial(text: 'Huruf belum tersedia.')
        else
          LayoutBuilder(
            builder: (context, constraints) => Wrap(
              spacing: 8,
              runSpacing: 8,
              children: list
                  .map(
                    (c) => SizedBox(
                      width: (constraints.maxWidth - 32) / 5,
                      child: InkWell(
                        onTap: () => showDialog(
                          context: context,
                          builder: (ctx) => AlertDialog(
                            title: const Text('Kenali bunyinya'),
                            content: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                JapaneseText('${c['symbol']}', size: 64),
                                Text('Cara baca: ${c['romaji']}'),
                                if (c['romaji'] == 'wo')
                                  const Text(
                                    'Sebagai partikel, umumnya dibaca o.',
                                  ),
                              ],
                            ),
                            actions: [
                              TextButton(
                                onPressed: () =>
                                    play(c, 'characters', '${c['symbol']}'),
                                child: const Text('Dengarkan'),
                              ),
                              TextButton(
                                onPressed: () => Navigator.pop(ctx),
                                child: const Text('Tutup'),
                              ),
                            ],
                          ),
                        ),
                        child: LearningCard(
                          padding: const EdgeInsets.symmetric(
                            vertical: 15,
                            horizontal: 4,
                          ),
                          child: Column(
                            children: [
                              JapaneseText('${c['symbol']}', size: 28),
                              if (progress.romaji)
                                Text(
                                  '${c['romaji']}',
                                  style: const TextStyle(
                                    fontSize: 12,
                                    color: Colors.grey,
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  )
                  .toList(),
            ),
          ),
        const SizedBox(height: 18),
        const Text(
          'Rekaman audio digunakan jika tersedia. Suara sintetis memerlukan suara Jepang pada perangkat.',
          style: TextStyle(color: Colors.grey, fontSize: 12),
        ),
      ],
    );
  }

  Widget vocabulary() {
    final categories = [
      'Semua',
      ...words.map((w) => '${w['category']}').toSet(),
    ];
    final selected = categories.contains(category) ? category : 'Semua';
    final filtered = words
        .where(
          (w) =>
              (selected == 'Semua' || w['category'] == selected) &&
              [
                w['japanese'],
                w['romaji'],
                w['meaning'],
                w['reading'],
                w['example'],
              ].join(' ').toLowerCase().contains(search.toLowerCase()),
        )
        .toList();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const PageTitle(
          'Kantong kosakata',
          'Dengarkan, pahami, dan tandai kata yang sudah kamu kuasai.',
        ),
        TextField(
          controller: searchController,
          decoration: const InputDecoration(
            hintText: 'Cari kata, romaji, atau arti…',
            prefixIcon: Icon(Icons.search),
            border: OutlineInputBorder(),
          ),
          onChanged: (value) => setState(() => search = value),
        ),
        const SizedBox(height: 14),
        Wrap(
          spacing: 8,
          children: categories
              .map(
                (c) => ChoiceChip(
                  label: Text(c),
                  selected: selected == c,
                  onSelected: (_) => setState(() => category = c),
                ),
              )
              .toList(),
        ),
        const SizedBox(height: 20),
        if (filtered.isEmpty)
          const EmptyMaterial(text: 'Tidak ada kosakata yang sesuai.')
        else
          LayoutBuilder(
            builder: (ctx, box) {
              final cols = box.maxWidth >= 800
                  ? 3
                  : box.maxWidth >= 550
                      ? 2
                      : 1;
              final width = (box.maxWidth - 16 * (cols - 1)) / cols;
              return Wrap(
                spacing: 16,
                runSpacing: 16,
                children: filtered.map((w) {
                  final known = progress.known.contains(w['id']);
                  return SizedBox(
                    width: width,
                    child: LearningCard(
                      child: Column(
                        children: [
                          Tag('${w['category']}'),
                          const SizedBox(height: 12),
                          JapaneseText('${w['japanese']}'),
                          if (progress.romaji)
                            Text(
                              '${w['romaji']}',
                              style: const TextStyle(color: Colors.grey),
                            ),
                          if (w['reading'] != null)
                            JapaneseText('${w['reading']}', size: 18),
                          const SizedBox(height: 8),
                          Text('${w['meaning']}'),
                          if (w['example'] != null) ...[
                            const SizedBox(height: 12),
                            JapaneseText('${w['example']}', size: 18),
                            Text('${w['example_translation'] ?? ''}'),
                          ],
                          AudioButton(
                            () => play(w, 'vocabularies', '${w['japanese']}'),
                          ),
                          OutlinedButton(
                            onPressed: () =>
                                save(progress.toggleWord(w['id'] as int)),
                            child: Text(
                              known ? '✓ Dikuasai' : 'Tandai dikuasai',
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                }).toList(),
              );
            },
          ),
      ],
    );
  }

  Widget practice() => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const PageTitle(
            'Latihan kecil, kemajuan besar',
            'Jawaban diperiksa oleh server, lalu pembahasan ditampilkan.',
          ),
          if (lessons.isEmpty)
            const EmptyMaterial()
          else
            LearningCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Tag('KUIS CAMPURAN'),
                  const SizedBox(height: 18),
                  const Text(
                    'Uji pemahamanmu',
                    style: TextStyle(fontSize: 23, fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 10),
                  const Text(
                    'Latihan dari pelajaran yang tersedia, termasuk kuis membaca, arti, dan mendengarkan jika ada.',
                  ),
                  const SizedBox(height: 18),
                  FilledButton(
                    onPressed: () => openQuiz(),
                    child: const Text('Mulai latihan →'),
                  ),
                ],
              ),
            ),
        ],
      );
  Widget profile() => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const PageTitle(
            'Perjalanan belajarmu',
            'Setiap langkah kecil tetap berarti.',
          ),
          LearningCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Tag('PELAJAR PEMULA'),
                const SizedBox(height: 18),
                const Text(
                  'Pelajar Haru',
                  style: TextStyle(fontSize: 22, fontWeight: FontWeight.w700),
                ),
                ...lessons.map(
                  (l) => ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: Text('${l['title']}'),
                    trailing: Icon(
                      progress.done.contains(l['id'])
                          ? Icons.check_circle
                          : Icons.circle_outlined,
                      color: green,
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                const Text(
                  'Riwayat kuis',
                  style: TextStyle(fontWeight: FontWeight.w700),
                ),
                if (progress.scores.isEmpty)
                  const Text('Belum ada kuis selesai.')
                else
                  ...progress.scores.reversed.take(5).map(
                        (s) => Text(
                          '${DateTime.parse(s['date'] as String).toLocal().toString().substring(0, 16)} · ${s['score']}/${s['total']} benar',
                        ),
                      ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          LearningCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Pengaturan belajar',
                  style: TextStyle(fontSize: 21, fontWeight: FontWeight.w700),
                ),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: const Text('Tampilkan romaji'),
                  subtitle: const Text('Bantuan baca huruf Latin'),
                  value: progress.romaji,
                  onChanged: (v) => save(progress.setRomaji(v)),
                ),
                DropdownButtonFormField<int>(
                  key: ValueKey(progress.target),
                  initialValue: progress.target,
                  decoration: const InputDecoration(labelText: 'Target harian'),
                  items: [5, 10, 15, 20]
                      .map(
                        (v) =>
                            DropdownMenuItem(value: v, child: Text('$v menit')),
                      )
                      .toList(),
                  onChanged: (v) {
                    if (v != null) save(progress.setTarget(v));
                  },
                ),
                const SizedBox(height: 18),
                const Text(
                  'Progres disimpan pada perangkat ini. Belum disinkronkan ke akun; durasi belajar belum dihitung.',
                  style: TextStyle(color: Colors.grey, fontSize: 12),
                ),
                const SizedBox(height: 14),
                OutlinedButton(
                  onPressed: () async {
                    final reset = await showDialog<bool>(
                      context: context,
                      builder: (ctx) => AlertDialog(
                        title: const Text('Reset progres?'),
                        content: const Text(
                          'Progres dan pengaturan pada perangkat ini akan dihapus.',
                        ),
                        actions: [
                          TextButton(
                            onPressed: () => Navigator.pop(ctx, false),
                            child: const Text('Batal'),
                          ),
                          TextButton(
                            onPressed: () => Navigator.pop(ctx, true),
                            child: const Text('Reset'),
                          ),
                        ],
                      ),
                    );
                    if (reset == true && mounted) await save(progress.reset());
                  },
                  child: const Text('Reset progres belajar'),
                ),
              ],
            ),
          ),
        ],
      );
}
