import 'package:flutter/material.dart';

import 'screens/shell.dart';

const green = Color(0xFF38664C);
const ink = Color(0xFF25382E);
const pale = Color(0xFFEAF0E5);
const paper = Color(0xFFFAF9F5);
void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const HaruApp());
}

class HaruApp extends StatelessWidget {
  const HaruApp({super.key});
  @override
  Widget build(BuildContext context) => MaterialApp(
        title: 'Haru — Belajar Jepang',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          useMaterial3: true,
          colorScheme: ColorScheme.fromSeed(
            seedColor: green,
            surface: Colors.white,
          ),
          scaffoldBackgroundColor: paper,
          textTheme: const TextTheme(bodyMedium: TextStyle(color: ink)),
          appBarTheme: const AppBarTheme(
            backgroundColor: paper,
            foregroundColor: ink,
            elevation: 0,
          ),
          filledButtonTheme: FilledButtonThemeData(
            style: FilledButton.styleFrom(
              backgroundColor: green,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ),
        home: const LearningShell(),
      );
}
