import 'package:flutter/material.dart';

class NedColors {
  static const Color background = Color(0xFF18181B); // zinc-900
  static const Color card = Color(0xFF27272A); // zinc-800
  static const Color cardBorder = Color(0xFF3F3F46); // zinc-700
  static const Color green = Color(0xFF34D399); // emerald-400
  static const Color amber = Color(0xFFFBBF24); // amber-400
  static const Color red = Color(0xFFF87171); // red-400
  static const Color gray = Color(0xFF71717A); // zinc-500
  static const Color textPrimary = Colors.white;
  static const Color textSecondary = Color(0xFFA1A1AA); // zinc-400
  static const Color textMuted = Color(0xFF71717A); // zinc-500

  static Color statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'online':
        return green;
      case 'warning':
        return amber;
      case 'critical':
        return red;
      case 'offline':
      default:
        return gray;
    }
  }
}

ThemeData nedTheme() {
  return ThemeData(
    brightness: Brightness.dark,
    scaffoldBackgroundColor: NedColors.background,
    colorScheme: const ColorScheme.dark(
      surface: NedColors.card,
      primary: NedColors.green,
      error: NedColors.red,
    ),
    appBarTheme: const AppBarTheme(
      backgroundColor: NedColors.background,
      foregroundColor: NedColors.textPrimary,
      elevation: 0,
      scrolledUnderElevation: 0,
    ),
    cardTheme: CardThemeData(
      color: NedColors.card,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: NedColors.cardBorder, width: 1),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: NedColors.card,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: NedColors.cardBorder),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: NedColors.cardBorder),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: NedColors.green),
      ),
      labelStyle: const TextStyle(color: NedColors.textSecondary),
      hintStyle: const TextStyle(color: NedColors.textMuted),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: NedColors.green,
        foregroundColor: NedColors.background,
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(8),
        ),
        textStyle: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w600,
        ),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: NedColors.textSecondary,
      ),
    ),
  );
}
