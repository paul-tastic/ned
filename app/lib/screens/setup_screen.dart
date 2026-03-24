import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../services/ned_api_service.dart';
import '../theme.dart';
import 'dashboard_screen.dart';

class SetupScreen extends StatefulWidget {
  const SetupScreen({super.key});

  @override
  State<SetupScreen> createState() => _SetupScreenState();
}

class _SetupScreenState extends State<SetupScreen> {
  final _api = NedApiService();
  bool _isLoading = false;

  void _navigateToDashboard() {
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => const DashboardScreen()),
    );
  }

  Future<void> _scanQrCode() async {
    final result = await Navigator.of(context).push<String>(
      MaterialPageRoute(builder: (_) => const _QrScannerPage()),
    );

    if (result == null || !mounted) return;

    setState(() => _isLoading = true);
    try {
      await _api.connectWithQr(result);
      if (mounted) _navigateToDashboard();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Invalid QR code: $e'),
            backgroundColor: NedColors.red,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showManualEntry() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => _ManualEntryPage(onSuccess: _navigateToDashboard),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Spacer(flex: 2),
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  color: NedColors.green.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Icon(
                  Icons.dns_rounded,
                  size: 40,
                  color: NedColors.green,
                ),
              ),
              const SizedBox(height: 24),
              const Text(
                'Ned',
                style: TextStyle(
                  color: NedColors.textPrimary,
                  fontSize: 32,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                'Server Monitoring',
                style: TextStyle(
                  color: NedColors.textSecondary,
                  fontSize: 16,
                ),
              ),
              const Spacer(),
              SizedBox(
                width: double.infinity,
                height: 54,
                child: ElevatedButton.icon(
                  onPressed: _isLoading ? null : _scanQrCode,
                  icon: const Icon(Icons.qr_code_scanner_rounded),
                  label: _isLoading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: NedColors.background,
                          ),
                        )
                      : const Text('Scan QR Code'),
                ),
              ),
              const SizedBox(height: 16),
              TextButton(
                onPressed: _isLoading ? null : _showManualEntry,
                child: const Text('Enter URL manually'),
              ),
              const Spacer(flex: 2),
            ],
          ),
        ),
      ),
    );
  }
}

class _QrScannerPage extends StatefulWidget {
  const _QrScannerPage();

  @override
  State<_QrScannerPage> createState() => _QrScannerPageState();
}

class _QrScannerPageState extends State<_QrScannerPage> {
  bool _hasScanned = false;

  void _onDetect(BarcodeCapture capture) {
    if (_hasScanned) return;
    final barcode = capture.barcodes.firstOrNull;
    if (barcode?.rawValue == null) return;

    final payload = barcode!.rawValue!;
    // Validate it's JSON with url and token
    try {
      final data = jsonDecode(payload) as Map<String, dynamic>;
      if (data['url'] != null && data['token'] != null) {
        _hasScanned = true;
        Navigator.of(context).pop(payload);
      }
    } catch (_) {
      // Not valid JSON, ignore
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Scan QR Code'),
      ),
      body: Stack(
        children: [
          MobileScanner(onDetect: _onDetect),
          Center(
            child: Container(
              width: 250,
              height: 250,
              decoration: BoxDecoration(
                border: Border.all(
                  color: NedColors.green.withValues(alpha: 0.6),
                  width: 2,
                ),
                borderRadius: BorderRadius.circular(16),
              ),
            ),
          ),
          const Positioned(
            bottom: 80,
            left: 0,
            right: 0,
            child: Text(
              'Point your camera at the QR code\non your Ned dashboard',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: NedColors.textSecondary,
                fontSize: 14,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ManualEntryPage extends StatefulWidget {
  final VoidCallback onSuccess;

  const _ManualEntryPage({required this.onSuccess});

  @override
  State<_ManualEntryPage> createState() => _ManualEntryPageState();
}

class _ManualEntryPageState extends State<_ManualEntryPage> {
  final _api = NedApiService();
  final _urlController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;
  bool _showCredentials = false;
  String? _error;

  @override
  void dispose() {
    _urlController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _continueWithUrl() {
    final url = _urlController.text.trim();
    if (url.isEmpty) {
      setState(() => _error = 'Please enter your server URL');
      return;
    }
    setState(() {
      _showCredentials = true;
      _error = null;
    });
  }

  Future<void> _login() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    final success = await _api.login(
      _urlController.text.trim(),
      _emailController.text.trim(),
      _passwordController.text,
    );

    if (!mounted) return;

    if (success) {
      Navigator.of(context).pop();
      widget.onSuccess();
    } else {
      setState(() {
        _isLoading = false;
        _error = 'Login failed. Check your credentials and try again.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Connect to Server'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (!_showCredentials) ...[
              const Text(
                'Server URL',
                style: TextStyle(
                  color: NedColors.textSecondary,
                  fontSize: 14,
                ),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: _urlController,
                autofocus: true,
                keyboardType: TextInputType.url,
                autocorrect: false,
                decoration: const InputDecoration(
                  hintText: 'https://ned.example.com',
                ),
                onSubmitted: (_) => _continueWithUrl(),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _continueWithUrl,
                child: const Text('Continue'),
              ),
            ],
            if (_showCredentials) ...[
              Text(
                _urlController.text.trim(),
                style: const TextStyle(
                  color: NedColors.textMuted,
                  fontSize: 13,
                ),
              ),
              const SizedBox(height: 20),
              const Text(
                'Email',
                style: TextStyle(
                  color: NedColors.textSecondary,
                  fontSize: 14,
                ),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: _emailController,
                autofocus: true,
                keyboardType: TextInputType.emailAddress,
                autocorrect: false,
                decoration: const InputDecoration(
                  hintText: 'you@example.com',
                ),
              ),
              const SizedBox(height: 16),
              const Text(
                'Password',
                style: TextStyle(
                  color: NedColors.textSecondary,
                  fontSize: 14,
                ),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: _passwordController,
                obscureText: true,
                decoration: const InputDecoration(
                  hintText: 'Password',
                ),
                onSubmitted: (_) => _login(),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _isLoading ? null : _login,
                child: _isLoading
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: NedColors.background,
                        ),
                      )
                    : const Text('Log In'),
              ),
            ],
            if (_error != null) ...[
              const SizedBox(height: 16),
              Text(
                _error!,
                style: const TextStyle(
                  color: NedColors.red,
                  fontSize: 13,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
