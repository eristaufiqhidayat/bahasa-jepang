import 'package:flutter/material.dart';

import '../main.dart';

class LearningCard extends StatelessWidget {
  final Widget child;
  final Color color;
  final EdgeInsets padding;
  const LearningCard({
    super.key,
    required this.child,
    this.color = Colors.white,
    this.padding = const EdgeInsets.all(22),
  });
  @override
  Widget build(BuildContext context) => Container(
        padding: padding,
        decoration: BoxDecoration(
          color: color,
          border: Border.all(color: const Color(0xFFE1E6DE)),
          borderRadius: BorderRadius.circular(20),
        ),
        child: child,
      );
}

class PageTitle extends StatelessWidget {
  final String title, subtitle;
  const PageTitle(this.title, this.subtitle, {super.key});
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.w800,
                color: ink,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              subtitle,
              style: const TextStyle(color: Color(0xFF748078), height: 1.6),
            ),
          ],
        ),
      );
}

class Tag extends StatelessWidget {
  final String text;
  const Tag(this.text, {super.key});
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: pale,
          borderRadius: BorderRadius.circular(8),
        ),
        child: Text(
          text,
          style: const TextStyle(
            color: green,
            fontSize: 12,
            fontWeight: FontWeight.w600,
          ),
        ),
      );
}

class JapaneseText extends StatelessWidget {
  final String text;
  final double size;
  const JapaneseText(this.text, {super.key, this.size = 30});
  @override
  Widget build(BuildContext context) => Text(
        text,
        locale: const Locale('ja'),
        style: TextStyle(fontSize: size, color: ink, height: 1.5),
      );
}

class AudioButton extends StatelessWidget {
  final VoidCallback onPressed;
  const AudioButton(this.onPressed, {super.key});
  @override
  Widget build(BuildContext context) => IconButton.filledTonal(
        onPressed: onPressed,
        tooltip: 'Dengarkan',
        icon: const Icon(Icons.volume_up_rounded),
      );
}

class EmptyMaterial extends StatelessWidget {
  final String text;
  const EmptyMaterial({
    super.key,
    this.text =
        'Materi segera hadir. Pelajaran perlu dipublikasikan melalui panel admin.',
  });
  @override
  Widget build(BuildContext context) => LearningCard(
        child: Column(
          children: [
            const Icon(Icons.auto_stories_rounded, size: 42, color: green),
            const SizedBox(height: 12),
            Text(text, textAlign: TextAlign.center),
          ],
        ),
      );
}
