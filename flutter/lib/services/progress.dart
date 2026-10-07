import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api.dart';

class LearningProgress extends ChangeNotifier {
  final SharedPreferencesAsync _prefs;
  LearningProgress({SharedPreferencesAsync? preferences})
      : _prefs = preferences ?? SharedPreferencesAsync();
  static const key = 'haru.flutter.progress.v1';
  final Set<int> done = {};
  final Set<int> known = {};
  final List<Json> scores = [];
  bool romaji = true;
  int target = 10;
  Future<void> _writes = Future.value();
  bool _disposed = false;
  @override
  void dispose() {
    _disposed = true;
    super.dispose();
  }

  Future<void> load() async {
    final raw = await _prefs.getString(key);
    if (raw == null) return;
    try {
      final data = jsonDecode(raw) as Json;
      done.clear();
      known.clear();
      scores.clear();
      done.addAll((data['done'] as List? ?? []).whereType<int>());
      known.addAll((data['known'] as List? ?? []).whereType<int>());
      romaji = data['romaji'] != false;
      final value = data['target'];
      target = [5, 10, 15, 20].contains(value) ? value as int : 10;
      for (final row in data['scores'] as List? ?? []) {
        if (row is Map &&
            row['score'] is int &&
            row['total'] is int &&
            (row['total'] as int) > 0 &&
            (row['score'] as int) >= 0 &&
            (row['score'] as int) <= (row['total'] as int) &&
            DateTime.tryParse('${row['date']}') != null) {
          scores.add(Map<String, dynamic>.from(row));
        }
      }
      if (scores.length > 100) scores.removeRange(0, scores.length - 100);
    } on FormatException {
      /* Ignore corrupt device data and start clean. */
    } on TypeError {
      /* Ignore incompatible stored data. */
    }
    if (!_disposed) notifyListeners();
  }

  Future<void> _save() {
    final snapshot = jsonEncode({
      'done': done.toList(),
      'known': known.toList(),
      'scores': scores,
      'romaji': romaji,
      'target': target,
    });
    // Serialize writes so a slower old write cannot replace newer progress.
    final next = _writes
        .catchError((Object _) {})
        .then((_) => _prefs.setString(key, snapshot));
    _writes = next;
    if (!_disposed) notifyListeners();
    return next;
  }

  Future<void> complete(int id) {
    done.add(id);
    return _save();
  }

  Future<void> toggleWord(int id) {
    if (!known.remove(id)) known.add(id);
    return _save();
  }

  Future<void> setRomaji(bool value) {
    romaji = value;
    return _save();
  }

  Future<void> setTarget(int value) {
    target = value;
    return _save();
  }

  Future<void> addScore(int score, int total) {
    scores.add({
      'score': score,
      'total': total,
      'date': DateTime.now().toIso8601String(),
    });
    if (scores.length > 100) scores.removeAt(0);
    return _save();
  }

  Future<void> reset() {
    done.clear();
    known.clear();
    scores.clear();
    romaji = true;
    target = 10;
    return _save();
  }
}
