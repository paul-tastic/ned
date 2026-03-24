import 'dart:async';
import 'package:flutter/material.dart';
import '../models/dashboard_response.dart';
import '../services/ned_api_service.dart';
import '../theme.dart';
import '../widgets/server_card.dart';
import 'server_detail_screen.dart';
import 'setup_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final _api = NedApiService();
  DashboardResponse? _dashboard;
  bool _isLoading = true;
  String? _error;
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    _loadDashboard();
    _refreshTimer = Timer.periodic(
      const Duration(seconds: 30),
      (_) => _loadDashboard(),
    );
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  Future<void> _loadDashboard() async {
    try {
      final dashboard = await _api.getDashboard();
      if (mounted) {
        setState(() {
          _dashboard = dashboard;
          _isLoading = false;
          _error = null;
        });
      }
    } on UnauthenticatedException {
      if (mounted) _logout();
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
          _error = e.toString();
        });
      }
    }
  }

  void _logout() {
    _api.logout();
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const SetupScreen()),
      (_) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Ned',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout_rounded),
            tooltip: 'Disconnect',
            onPressed: _logout,
          ),
        ],
      ),
      body: _isLoading && _dashboard == null
          ? const Center(
              child: CircularProgressIndicator(color: NedColors.green),
            )
          : _error != null && _dashboard == null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(
                          Icons.cloud_off_rounded,
                          size: 48,
                          color: NedColors.textMuted,
                        ),
                        const SizedBox(height: 16),
                        Text(
                          _error!,
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: NedColors.textSecondary),
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton(
                          onPressed: () {
                            setState(() => _isLoading = true);
                            _loadDashboard();
                          },
                          child: const Text('Retry'),
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadDashboard,
                  color: NedColors.green,
                  child: CustomScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    slivers: [
                      if (_dashboard != null) ...[
                        SliverToBoxAdapter(
                          child: _StatsBar(dashboard: _dashboard!),
                        ),
                        SliverPadding(
                          padding: const EdgeInsets.all(12),
                          sliver: SliverGrid(
                            gridDelegate:
                                const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 2,
                              mainAxisSpacing: 8,
                              crossAxisSpacing: 8,
                              childAspectRatio: 0.85,
                            ),
                            delegate: SliverChildBuilderDelegate(
                              (context, index) {
                                final server = _dashboard!.servers[index];
                                return ServerCard(
                                  server: server,
                                  onTap: () {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(
                                        builder: (_) => ServerDetailScreen(
                                          serverId: server.id,
                                          serverName: server.name,
                                        ),
                                      ),
                                    );
                                  },
                                );
                              },
                              childCount: _dashboard!.servers.length,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
    );
  }
}

class _StatsBar extends StatelessWidget {
  final DashboardResponse dashboard;

  const _StatsBar({required this.dashboard});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _StatItem(
            label: 'Total',
            count: dashboard.total,
            color: NedColors.textPrimary,
          ),
          _StatItem(
            label: 'Online',
            count: dashboard.online,
            color: NedColors.green,
          ),
          _StatItem(
            label: 'Warning',
            count: dashboard.warning,
            color: NedColors.amber,
          ),
          _StatItem(
            label: 'Critical',
            count: dashboard.critical,
            color: NedColors.red,
          ),
          _StatItem(
            label: 'Offline',
            count: dashboard.offline,
            color: NedColors.gray,
          ),
        ],
      ),
    );
  }
}

class _StatItem extends StatelessWidget {
  final String label;
  final int count;
  final Color color;

  const _StatItem({
    required this.label,
    required this.count,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(
          '$count',
          style: TextStyle(
            color: color,
            fontSize: 22,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          label,
          style: const TextStyle(
            color: NedColors.textMuted,
            fontSize: 11,
          ),
        ),
      ],
    );
  }
}
