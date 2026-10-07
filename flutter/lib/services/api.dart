import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

typedef Json = Map<String, dynamic>;

class ApiException implements Exception {
  final String message;
  ApiException(this.message);
  @override
  String toString() => message;
}

class LearningApi {
  static const defaultBase = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://japantest.id',
  );
  final String baseUrl;
  final http.Client client;
  LearningApi({String baseUrl = defaultBase, http.Client? client})
      : baseUrl = baseUrl.replaceAll(RegExp(r'/+$'), ''),
        client = client ?? http.Client();
  Uri uri(String path, [Map<String, String>? query]) =>
      Uri.parse('$baseUrl/api/v1/$path').replace(queryParameters: query);
  Future<dynamic> _decode(Future<http.Response> request) async {
    try {
      final response = await request.timeout(const Duration(seconds: 25));
      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw ApiException(switch (response.statusCode) {
          404 => 'Materi tidak tersedia atau belum dipublikasikan.',
          429 => 'Terlalu banyak permintaan. Tunggu satu menit lalu coba lagi.',
          _ =>
            'Server belum bisa memproses permintaan (${response.statusCode}).',
        });
      }
      return jsonDecode(utf8.decode(response.bodyBytes));
    } on TimeoutException {
      throw ApiException(
        'Koneksi terlalu lama. Periksa internet lalu coba lagi.',
      );
    } on http.ClientException {
      throw ApiException(
        'Tidak dapat terhubung ke server. Periksa internet atau pengaturan CORS web.',
      );
    } on FormatException {
      throw ApiException('Respons server bukan data yang valid.');
    }
  }

  Future<List<Json>> collection(String path) async {
    final result = <Json>[];
    var page = 1;
    while (true) {
      final data = await _decode(
        client.get(
          uri(path, {'page': '$page'}),
          headers: {'Accept': 'application/json'},
        ),
      ) as Json;
      result.addAll(
        (data['data'] as List).map((v) => Map<String, dynamic>.from(v as Map)),
      );
      final lastPage = (data['last_page'] as num?)?.toInt() ?? 1;
      if (page >= lastPage) break;
      page++;
      if (page > 1000) {
        throw ApiException('Jumlah halaman materi melebihi batas.');
      }
    }
    return result;
  }

  Future<Json> lesson(int id) async => Map<String, dynamic>.from(
        await _decode(
          client
              .get(uri('lessons/$id'), headers: {'Accept': 'application/json'}),
        ) as Map,
      );
  Future<Json> answer(int id, int index) async => Map<String, dynamic>.from(
        await _decode(
          client.post(
            uri('questions/$id/answer'),
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
            body: jsonEncode({'answer_index': index}),
          ),
        ) as Map,
      );
  String? audioUrl(Json item, String type) {
    if (item['audio_path'] == null || item['audio_path'] == '') return null;
    // The Laravel media route works even when hosting disables symlink/exec.
    return '$baseUrl/belajar/audio/$type/${item['id']}';
  }

  void dispose() => client.close();
}
