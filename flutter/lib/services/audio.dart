import 'package:audioplayers/audioplayers.dart';
import 'package:flutter_tts/flutter_tts.dart';

import 'api.dart';

class LearningAudio {
  final AudioPlayer _player = AudioPlayer();
  final FlutterTts _tts = FlutterTts();
  Future<void> play(String text, String? url) async {
    try {
      await stop();
      if (url != null) {
        await _player.play(UrlSource(url));
        return;
      }
      final available = await _tts.isLanguageAvailable('ja-JP');
      if (available != true && available != 1) {
        throw ApiException(
          'Suara Jepang belum tersedia. Pasang suara Jepang pada perangkat.',
        );
      }
      await _tts.setLanguage('ja-JP');
      await _tts.setSpeechRate(0.45);
      await _tts.speak(text);
    } on ApiException {
      rethrow;
    } catch (_) {
      throw ApiException(
        'Audio belum bisa diputar. Periksa berkas dan suara Jepang perangkat.',
      );
    }
  }

  Future<void> stop() async {
    await _player.stop();
    await _tts.stop();
  }

  void dispose() {
    _player.dispose();
    _tts.stop();
  }
}
