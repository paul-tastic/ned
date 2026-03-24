import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/server.dart';
import '../theme.dart';
import 'status_dot.dart';

class ServerCard extends StatelessWidget {
  final Server server;
  final VoidCallback onTap;

  const ServerCard({
    super.key,
    required this.server,
    required this.onTap,
  });

  String _formatLastSeen(DateTime? lastSeen) {
    if (lastSeen == null) return 'Never';
    final now = DateTime.now();
    final diff = now.difference(lastSeen);
    if (diff.inSeconds < 60) return 'Just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    if (diff.inDays < 7) return '${diff.inDays}d ago';
    return DateFormat('MMM d').format(lastSeen);
  }

  @override
  Widget build(BuildContext context) {
    final metric = server.latestMetric;

    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  StatusDot(status: server.status),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      server.name,
                      style: const TextStyle(
                        color: NedColors.textPrimary,
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                _formatLastSeen(server.lastSeenAt),
                style: const TextStyle(
                  color: NedColors.textMuted,
                  fontSize: 12,
                ),
              ),
              if (metric != null) ...[
                const SizedBox(height: 10),
                _MetricBar(
                  label: 'CPU',
                  percent: metric.cpuPercent,
                ),
                const SizedBox(height: 6),
                _MetricBar(
                  label: 'RAM',
                  percent: metric.memoryPercent,
                ),
                const SizedBox(height: 6),
                _MetricBar(
                  label: 'Disk',
                  percent: metric.diskPercent,
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _MetricBar extends StatelessWidget {
  final String label;
  final double percent;

  const _MetricBar({
    required this.label,
    required this.percent,
  });

  Color _barColor() {
    if (percent >= 90) return NedColors.red;
    if (percent >= 75) return NedColors.amber;
    return NedColors.green;
  }

  @override
  Widget build(BuildContext context) {
    final clamped = percent.clamp(0, 100).toDouble();

    return Row(
      children: [
        SizedBox(
          width: 32,
          child: Text(
            label,
            style: const TextStyle(
              color: NedColors.textMuted,
              fontSize: 11,
            ),
          ),
        ),
        Expanded(
          child: ClipRRect(
            borderRadius: BorderRadius.circular(3),
            child: SizedBox(
              height: 6,
              child: LinearProgressIndicator(
                value: clamped / 100,
                backgroundColor: NedColors.background,
                valueColor: AlwaysStoppedAnimation<Color>(_barColor()),
              ),
            ),
          ),
        ),
        const SizedBox(width: 6),
        SizedBox(
          width: 36,
          child: Text(
            '${clamped.toInt()}%',
            textAlign: TextAlign.right,
            style: const TextStyle(
              color: NedColors.textSecondary,
              fontSize: 11,
            ),
          ),
        ),
      ],
    );
  }
}
