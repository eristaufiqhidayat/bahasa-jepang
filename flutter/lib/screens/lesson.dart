import 'package:flutter/material.dart';

import '../services/api.dart';
import '../services/progress.dart';
import '../services/audio.dart';
import 'widgets.dart';
import 'quiz.dart';

class LessonScreen extends StatefulWidget {
  final LearningApi api;
  final int lessonId;
  final LearningProgress progress;
  final LearningAudio audio;
  const LessonScreen({
    super.key,
    required this.api,
    required this.lessonId,
    required this.progress,
    required this.audio,
  });
  @override
  State<LessonScreen> createState() => _LessonScreenState();
}

class _LessonScreenState extends State<LessonScreen> {
  Json? lesson;
  String? error;
  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    setState(() {
      lesson = null;
      error = null;
    });
    try {
      final data = await widget.api.lesson(widget.lessonId);
      if (mounted) setState(() => lesson = data);
    } catch (e) {
      if (mounted) setState(() => error = '$e');
    }
  }

  Future<void> play(Json item, String type, String text) async {
    try {
      await widget.audio.play(text, widget.api.audioUrl(item, type));
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
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(title: const Text('Pelajaran')),
        body: error != null
            ? Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(error!),
                    FilledButton(
                        onPressed: load, child: const Text('Coba lagi')),
                  ],
                ),
              )
            : lesson == null
                ? const Center(child: CircularProgressIndicator())
                : ListView(
                    padding: const EdgeInsets.all(22),
                    children: [
                      PageTitle('${lesson!['title']}', '${lesson!['summary']}'),
                      LearningCard(
                        child: Text(
                          '${lesson!['content']}',
                          style: const TextStyle(height: 1.8),
                        ),
                      ),
                      const SizedBox(height: 20),
                      if (lesson!['japanese'] != null)
                        phrase(
                          lesson!,
                          'lessons',
                          '${lesson!['japanese']}',
                          '${lesson!['romaji'] ?? ''}',
                          '${lesson!['translation'] ?? ''}',
                        ),
                      ...(lesson!['vocabularies'] as List).map(
                        (v) => phrase(
                          Map<String, dynamic>.from(v as Map),
                          'vocabularies',
                          '${v['japanese']}',
                          '${v['romaji']}',
                          '${v['meaning']}',
                        ),
                      ),
                      const SizedBox(height: 18),
                      FilledButton(
                        onPressed: () async {
                          try {
                            await widget.progress.complete(widget.lessonId);
                            if (mounted) setState(() {});
                          } catch (_) {
                            if (context.mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content:
                                      Text('Progres belum berhasil disimpan.'),
                                ),
                              );
                            }
                          }
                        },
                        child: Text(
                          widget.progress.done.contains(widget.lessonId)
                              ? '✓ Sudah selesai'
                              : 'Tandai selesai',
                        ),
                      ),
                      const SizedBox(height: 10),
                      OutlinedButton(
                        onPressed: () => Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) => QuizScreen(
                              api: widget.api,
                              lessons: [lesson!],
                              progress: widget.progress,
                              audio: widget.audio,
                            ),
                          ),
                        ),
                        child: const Text('Latihan pelajaran ini →'),
                      ),
                    ],
                  ),
      );
  Widget phrase(
    Json item,
    String type,
    String text,
    String romaji,
    String meaning,
  ) =>
      Padding(
        padding: const EdgeInsets.only(bottom: 16),
        child: LearningCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(child: JapaneseText(text)),
                  AudioButton(() => play(item, type, text)),
                ],
              ),
              if (widget.progress.romaji)
                Text(romaji, style: const TextStyle(color: Colors.grey)),
              const SizedBox(height: 12),
              Text(meaning,
                  style: const TextStyle(fontWeight: FontWeight.w600)),
              if (item['example'] != null) ...[
                const SizedBox(height: 12),
                JapaneseText('${item['example']}', size: 20),
                Text('${item['example_translation'] ?? ''}'),
              ],
            ],
          ),
        ),
      );
}
