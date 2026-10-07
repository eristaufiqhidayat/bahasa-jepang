import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:haru_belajar_jepang/services/progress.dart';

class FakePreferences implements SharedPreferencesAsync {
  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
  final Map<String, String> values = {};
  @override
  Future<String?> getString(String key) async => values[key];
  @override
  Future<void> setString(String key, String value) async {
    await Future<void>.delayed(const Duration(milliseconds: 2));
    values[key] = value;
  }
}

void main() {
  test('Repeated load does not duplicate quiz history', () async {
    final prefs = FakePreferences();
    final p = LearningProgress(preferences: prefs);
    await p.complete(11);
    await p.toggleWord(9);
    await p.addScore(2, 3);
    await p.load();
    await p.load();
    expect(p.done, {11});
    expect(p.known, {9});
    expect(p.scores.length, 1);
    p.dispose();
  });
  test('Queued rapid changes persist the newest progress', () async {
    final prefs = FakePreferences();
    final p = LearningProgress(preferences: prefs);
    await Future.wait([
      p.complete(1),
      p.complete(2),
      p.toggleWord(7),
      p.setRomaji(false),
    ]);
    final data = jsonDecode(prefs.values[LearningProgress.key]!);
    expect(data['done'], [1, 2]);
    expect(data['known'], [7]);
    expect(data['romaji'], false);
    p.dispose();
  });
  test('Reset removes progress and restores learning preferences', () async {
    final prefs = FakePreferences();
    final p = LearningProgress(preferences: prefs);
    await p.complete(1);
    await p.setTarget(20);
    await p.reset();
    expect(p.done, isEmpty);
    expect(p.known, isEmpty);
    expect(p.scores, isEmpty);
    expect(p.target, 10);
    expect(p.romaji, true);
    p.dispose();
  });
}
