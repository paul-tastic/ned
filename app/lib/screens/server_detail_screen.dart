import 'package:flutter/material.dart';
import '../models/server.dart';
import '../models/server_detail.dart';
import '../services/ned_api_service.dart';
import '../theme.dart';
import '../widgets/metric_chart.dart';
import '../widgets/status_dot.dart';

class ServerDetailScreen extends StatefulWidget {
  final int serverId;
  final String serverName;

  const ServerDetailScreen({
    super.key,
    required this.serverId,
    required this.serverName,
  });

  @override
  State<ServerDetailScreen> createState() => _ServerDetailScreenState();
}

class _ServerDetailScreenState extends State<ServerDetailScreen> {
  final _api = NedApiService();
  ServerDetail? _detail;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadServer();
  }

  Future<void> _loadServer() async {
    try {
      final detail = await _api.getServer(widget.serverId);
      if (mounted) {
        setState(() {
          _detail = detail;
          _isLoading = false;
          _error = null;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
          _error = e.toString();
        });
      }
    }
  }

  String _formatBytes(int bytes) {
    if (bytes < 1024) return '$bytes B';
    if (bytes < 1024 * 1024) return '${(bytes / 1024).toStringAsFixed(1)} KB';
    if (bytes < 1024 * 1024 * 1024) {
      return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
    }
    return '${(bytes / (1024 * 1024 * 1024)).toStringAsFixed(1)} GB';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.serverName),
      ),
      body: _isLoading
          ? const Center(
              child: CircularProgressIndicator(color: NedColors.green),
            )
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          _error!,
                          textAlign: TextAlign.center,
                          style:
                              const TextStyle(color: NedColors.textSecondary),
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton(
                          onPressed: () {
                            setState(() => _isLoading = true);
                            _loadServer();
                          },
                          child: const Text('Retry'),
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadServer,
                  color: NedColors.green,
                  child: ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: [
                      _buildHeader(),
                      const SizedBox(height: 16),
                      _buildStatsCards(),
                      const SizedBox(height: 16),
                      MetricChart(
                        title: 'CPU Usage (24h)',
                        points: _detail!.metrics,
                        valueSelector: (p) => p.cpuPercent,
                        lineColor: NedColors.green,
                      ),
                      const SizedBox(height: 12),
                      MetricChart(
                        title: 'Memory Usage (24h)',
                        points: _detail!.metrics,
                        valueSelector: (p) => p.memoryPercent,
                        lineColor: NedColors.amber,
                      ),
                      const SizedBox(height: 12),
                      MetricChart(
                        title: 'Disk Usage (24h)',
                        points: _detail!.metrics,
                        valueSelector: (p) => p.diskPercent,
                        lineColor: NedColors.red,
                      ),
                      const SizedBox(height: 16),
                      _buildDiskBreakdown(),
                      const SizedBox(height: 16),
                      _buildServicesList(),
                      const SizedBox(height: 32),
                    ],
                  ),
                ),
    );
  }

  Widget _buildHeader() {
    final server = _detail!.server;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            StatusDot(status: server.status, size: 14),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    server.name,
                    style: const TextStyle(
                      color: NedColors.textPrimary,
                      fontWeight: FontWeight.bold,
                      fontSize: 18,
                    ),
                  ),
                  if (server.hostname != null) ...[
                    const SizedBox(height: 2),
                    Text(
                      server.hostname!,
                      style: const TextStyle(
                        color: NedColors.textMuted,
                        fontSize: 13,
                      ),
                    ),
                  ],
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: NedColors.statusColor(server.status).withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                server.status.toUpperCase(),
                style: TextStyle(
                  color: NedColors.statusColor(server.status),
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatsCards() {
    final metric = _detail!.server.latestMetric;
    if (metric == null) {
      return const Card(
        child: Padding(
          padding: EdgeInsets.all(16),
          child: Text(
            'No metrics available',
            style: TextStyle(color: NedColors.textMuted),
          ),
        ),
      );
    }

    return Row(
      children: [
        Expanded(
          child: _StatCard(
            label: 'CPU',
            value: '${metric.cpuPercent.toStringAsFixed(1)}%',
            color: _colorForPercent(metric.cpuPercent),
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _StatCard(
            label: 'RAM',
            value: '${metric.memoryPercent.toStringAsFixed(1)}%',
            subtitle: '${_formatBytes(metric.memoryUsed)} / ${_formatBytes(metric.memoryTotal)}',
            color: _colorForPercent(metric.memoryPercent),
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _StatCard(
            label: 'Disk',
            value: '${metric.diskPercent.toStringAsFixed(1)}%',
            color: _colorForPercent(metric.diskPercent),
          ),
        ),
        if (_detail!.uptime != null) ...[
          const SizedBox(width: 8),
          Expanded(
            child: _StatCard(
              label: 'Uptime',
              value: _detail!.uptime!,
              color: NedColors.textPrimary,
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildDiskBreakdown() {
    final disks = _detail!.server.latestMetric?.disks;
    if (disks == null || disks.isEmpty) return const SizedBox.shrink();

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Disk Usage',
              style: TextStyle(
                color: NedColors.textPrimary,
                fontWeight: FontWeight.w600,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 12),
            ...disks.map((disk) => Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            disk.mount,
                            style: const TextStyle(
                              color: NedColors.textSecondary,
                              fontSize: 13,
                            ),
                          ),
                          Text(
                            '${_formatBytes(disk.used)} / ${_formatBytes(disk.total)} (${disk.usagePercent.toStringAsFixed(1)}%)',
                            style: const TextStyle(
                              color: NedColors.textMuted,
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(3),
                        child: SizedBox(
                          height: 6,
                          child: LinearProgressIndicator(
                            value: (disk.usagePercent / 100).clamp(0, 1),
                            backgroundColor: NedColors.background,
                            valueColor: AlwaysStoppedAnimation<Color>(
                              _colorForPercent(disk.usagePercent),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                )),
          ],
        ),
      ),
    );
  }

  Widget _buildServicesList() {
    final services = _detail!.server.latestMetric?.services;
    if (services == null || services.isEmpty) return const SizedBox.shrink();

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Services',
              style: TextStyle(
                color: NedColors.textPrimary,
                fontWeight: FontWeight.w600,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 12),
            ...services.entries.map((entry) {
              final isRunning = entry.value.toLowerCase() == 'running' ||
                  entry.value.toLowerCase() == 'active';
              return Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(
                  children: [
                    Container(
                      width: 8,
                      height: 8,
                      decoration: BoxDecoration(
                        color: isRunning ? NedColors.green : NedColors.red,
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        entry.key,
                        style: const TextStyle(
                          color: NedColors.textSecondary,
                          fontSize: 13,
                        ),
                      ),
                    ),
                    Text(
                      entry.value,
                      style: TextStyle(
                        color: isRunning ? NedColors.green : NedColors.red,
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              );
            }),
          ],
        ),
      ),
    );
  }

  Color _colorForPercent(double percent) {
    if (percent >= 90) return NedColors.red;
    if (percent >= 75) return NedColors.amber;
    return NedColors.green;
  }
}

class _StatCard extends StatelessWidget {
  final String label;
  final String value;
  final String? subtitle;
  final Color color;

  const _StatCard({
    required this.label,
    required this.value,
    this.subtitle,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: [
            Text(
              label,
              style: const TextStyle(
                color: NedColors.textMuted,
                fontSize: 11,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              value,
              style: TextStyle(
                color: color,
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            if (subtitle != null) ...[
              const SizedBox(height: 2),
              Text(
                subtitle!,
                style: const TextStyle(
                  color: NedColors.textMuted,
                  fontSize: 10,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
