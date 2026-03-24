import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'screens/dashboard_screen.dart';
import 'screens/setup_screen.dart';
import 'services/ned_api_service.dart';
import 'theme.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarBrightness: Brightness.dark,
      statusBarIconBrightness: Brightness.light,
    ),
  );
  runApp(const NedApp());
}

class NedApp extends StatelessWidget {
  const NedApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Ned',
      theme: nedTheme(),
      debugShowCheckedModeBanner: false,
      home: const _InitScreen(),
    );
  }
}

class _InitScreen extends StatefulWidget {
  const _InitScreen();

  @override
  State<_InitScreen> createState() => _InitScreenState();
}

class _InitScreenState extends State<_InitScreen> {
  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    final api = NedApiService();
    await api.loadCredentials();

    if (!mounted) return;

    if (api.isAuthenticated) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const DashboardScreen()),
      );
    } else {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const SetupScreen()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: CircularProgressIndicator(color: NedColors.green),
      ),
    );
  }
}
