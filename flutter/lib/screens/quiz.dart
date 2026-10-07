import 'package:flutter/material.dart';

import '../main.dart';
import '../services/api.dart';
import '../services/progress.dart';
import '../services/audio.dart';
import 'widgets.dart';

class QuizScreen extends StatefulWidget {
  final LearningApi api;
  final List<Json> lessons;
  final LearningProgress progress;
  final LearningAudio audio;
  const QuizScreen({
    super.key,
    required this.api,
    required this.lessons,
    required this.progress,
    required this.audio,
  });
  @override
  State<QuizScreen> createState() => _QuizScreenState();
}

class _QuizScreenState extends State<QuizScreen> {
  List<Json> questions = [];
  bool loading = true, busy = false, finished = false;
  int index = 0, score = 0;
  int? chosen;
  Json? feedback;
  String? error;
  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      // Sequential requests avoid hitting the API rate limit for large curricula.
      final all = <Json>[];
      for (final l in widget.lessons) {
        final full = l.containsKey('questions')
            ? l
            : await widget.api.lesson(l['id'] as int);
        all.addAll(
          (full['questions'] as List).map(
            (q) => {
              ...Map<String, dynamic>.from(q as Map),
              'lesson_title': full['title'],
            },
          ),
        );
      }
      if (mounted) {
        setState(() {
          questions = all;
          loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          loading = false;
          error = '$e';
        });
      }
    }
  }

  Future<void> answer(int value) async {
    if (busy || feedback != null) return;
    setState(() {
      busy = true;
      chosen = value;
    });
    try {
      final result = await widget.api.answer(
        questions[index]['id'] as int,
        value,
      );
      if (!mounted) return;
      setState(() {
        feedback = result;
        busy = false;
        if (result['correct'] == true) score++;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        busy = false;
        chosen = null;
      });
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<void> next() async {
    if (feedback == null || busy) return;
    await widget.audio.stop();
    if (!mounted) return;
    if (index == questions.length - 1) {
      setState(() {
        busy = true;
      });
      try {
        await widget.progress.addScore(score, questions.length);
      } catch (_) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Hasil kuis belum berhasil disimpan.'),
            ),
          );
        }
      }
      if (mounted) {
        setState(() {
          finished = true;
          busy = false;
        });
      }
    } else {
      setState(() {
        index++;
        chosen = null;
        feedback = null;
      });
    }
  }

  Future<void> playQuestion() async {
    final q = questions[index];
    try {
      final url = widget.api.audioUrl(q, 'questions');
      if (url == null) throw ApiException('Audio soal belum tersedia.');
      await widget.audio.play('', url);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text('$e')));
      }
    }
  }

  @override
  void dispose() {
    widget.audio.stop();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    Widget body;
    if (loading) {
      body = const Center(child: CircularProgressIndicator());
    } else if (error != null) {
      body = Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Padding(padding: const EdgeInsets.all(20), child: Text(error!)),
            FilledButton(onPressed: load, child: const Text('Coba lagi')),
          ],
        ),
      );
    } else if (questions.isEmpty) {
      body = const Padding(
        padding: EdgeInsets.all(22),
        child: EmptyMaterial(text: 'Belum ada soal pada pelajaran ini.'),
      );
    } else if (finished) {
      body = Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: LearningCard(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Tag('LATIHAN SELESAI'),
                const SizedBox(height: 24),
                Text(
                  score == questions.length ? 'Hebat! 🌟' : 'Terus berlatih 🌱',
                  style: const TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  '$score / ${questions.length}',
                  style: const TextStyle(fontSize: 48, color: green),
                ),
                const Text('Ulangi latihan untuk memperkuat ingatan.'),
                const SizedBox(height: 20),
                FilledButton(
                  onPressed: () => setState(() {
                    index = 0;
                    score = 0;
                    chosen = null;
                    feedback = null;
                    finished = false;
                  }),
                  child: const Text('Ulangi kuis'),
                ),
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('Kembali'),
                ),
              ],
            ),
          ),
        ),
      );
    } else {
      final q = questions[index];
      final options = q['options'] as List;
      body = ListView(
        padding: const EdgeInsets.all(22),
        children: [
          const PageTitle(
            'Latihan kecil, kemajuan besar',
            'Pilih satu jawaban. Pembahasan muncul setelah menjawab.',
          ),
          LearningCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    Tag('SOAL ${index + 1}/${questions.length}'),
                    const Spacer(),
                    Text('$score benar'),
                  ],
                ),
                const SizedBox(height: 14),
                LinearProgressIndicator(
                  value: index / questions.length,
                  color: green,
                  backgroundColor: pale,
                ),
                const SizedBox(height: 24),
                Text(
                  '${q['lesson_title']}',
                  style: const TextStyle(color: Colors.grey),
                ),
                const SizedBox(height: 14),
                JapaneseText('${q['prompt']}', size: 26),
                if (q['type'] == 'listening')
                  Center(child: AudioButton(playQuestion)),
                const SizedBox(height: 24),
                ...List.generate(options.length, (i) {
                  final correct =
                      feedback != null && i == feedback!['correct_index'];
                  final wrong = feedback != null && i == chosen && !correct;
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        alignment: Alignment.centerLeft,
                        padding: const EdgeInsets.all(18),
                        backgroundColor: correct
                            ? pale
                            : wrong
                                ? const Color(0xFFF9E7DF)
                                : Colors.white,
                        disabledForegroundColor: ink,
                        side: BorderSide(
                          color: correct ? green : const Color(0xFFE1E6DE),
                        ),
                      ),
                      onPressed:
                          busy || feedback != null ? null : () => answer(i),
                      child: Text(
                        '${String.fromCharCode(65 + i)}. ${options[i]}',
                      ),
                    ),
                  );
                }),
                if (busy)
                  const Center(
                    child: Padding(
                      padding: EdgeInsets.all(8),
                      child: CircularProgressIndicator(),
                    ),
                  ),
                if (feedback != null) ...[
                  const SizedBox(height: 10),
                  LearningCard(
                    color: pale,
                    child: Text(
                      '${feedback!['correct'] == true ? '✓ Benar!' : 'Belum tepat.'} ${feedback!['explanation']}',
                      style: const TextStyle(height: 1.7),
                    ),
                  ),
                  const SizedBox(height: 16),
                  FilledButton(
                    onPressed: busy ? null : next,
                    child: Text(
                      index == questions.length - 1
                          ? 'Lihat hasil →'
                          : 'Soal berikutnya →',
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      );
    }
    return Scaffold(
      appBar: AppBar(title: const Text('Latihan')),
      body: body,
    );
  }
}
