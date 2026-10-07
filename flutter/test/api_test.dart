import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:haru_belajar_jepang/services/api.dart';

void main() {
  test('Collects all Laravel pages without losing Japanese text', () async {
    final pages = <int>[];
    final api = LearningApi(
      client: MockClient((r) async {
        final page = int.parse(r.url.queryParameters['page']!);
        pages.add(page);
        return http.Response.bytes(
          utf8.encode(
            jsonEncode({
              'data': [
                {'id': page, 'japanese': 'ありがとう'},
              ],
              'last_page': 2,
            }),
          ),
          200,
        );
      }),
    );
    final words = await api.collection('vocabularies');
    expect(words.length, 2);
    expect(words.first['japanese'], 'ありがとう');
    expect(pages, [1, 2]);
    api.dispose();
  });
  test('Answers are posted to Laravel with correct index', () async {
    final api = LearningApi(
      client: MockClient((r) async {
        expect(r.method, 'POST');
        expect(r.url.path, '/api/v1/questions/7/answer');
        expect(jsonDecode(r.body), {'answer_index': 2});
        return http.Response(
          jsonEncode({
            'correct': true,
            'correct_index': 2,
            'explanation': 'Kopi',
          }),
          200,
        );
      }),
    );
    expect((await api.answer(7, 2))['correct'], true);
    api.dispose();
  });
  test('Rate limit response becomes a readable error', () async {
    final api = LearningApi(
      client: MockClient((_) async => http.Response('{}', 429)),
    );
    await expectLater(
      api.collection('lessons'),
      throwsA(
        isA<ApiException>().having(
          (e) => e.message,
          'message',
          contains('satu menit'),
        ),
      ),
    );
    api.dispose();
  });
  test('Malformed response becomes a readable error', () async {
    final api = LearningApi(
      client: MockClient((_) async => http.Response('<html>Error</html>', 200)),
    );
    await expectLater(api.collection('lessons'), throwsA(isA<ApiException>()));
    api.dispose();
  });
  test('Media URL uses Laravel route without symlink', () {
    final api = LearningApi(baseUrl: 'https://japantest.id/');
    expect(
      api.audioUrl({'id': 5, 'audio_path': 'audio/test.mp3'}, 'vocabularies'),
      'https://japantest.id/belajar/audio/vocabularies/5',
    );
    expect(api.audioUrl({'id': 5, 'audio_path': null}, 'vocabularies'), isNull);
    api.dispose();
  });
}
