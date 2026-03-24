import 'server.dart';

class MetricPoint {
  final DateTime timestamp;
  final double cpuPercent;
  final double memoryPercent;
  final double diskPercent;

  MetricPoint({
    required this.timestamp,
    required this.cpuPercent,
    required this.memoryPercent,
    required this.diskPercent,
  });

  factory MetricPoint.fromJson(Map<String, dynamic> json, int cpuCores) {
    final load = (json['load_1m'] as num?)?.toDouble() ?? 0;
    final memTotal = (json['memory_total'] as num?)?.toInt() ?? 1;
    final memUsed = (json['memory_used'] as num?)?.toInt() ?? 0;

    // Calculate disk percent from disks array
    double diskPct = 0;
    if (json['disks'] != null) {
      final disks = json['disks'] as List<dynamic>;
      int totalSize = 0;
      int totalUsed = 0;
      for (final d in disks) {
        final disk = d as Map<String, dynamic>;
        totalSize += (disk['total'] as num?)?.toInt() ?? 0;
        totalUsed += (disk['used'] as num?)?.toInt() ?? 0;
      }
      diskPct = totalSize > 0 ? (totalUsed / totalSize) * 100 : 0;
    }

    return MetricPoint(
      timestamp: DateTime.parse(
          json['recorded_at'] as String? ?? DateTime.now().toIso8601String()),
      cpuPercent: cpuCores > 0 ? (load / cpuCores) * 100 : 0,
      memoryPercent: memTotal > 0 ? (memUsed / memTotal) * 100 : 0,
      diskPercent: diskPct,
    );
  }
}

class ServerDetail {
  final Server server;
  final List<MetricPoint> metrics;
  final String? uptime;

  ServerDetail({
    required this.server,
    required this.metrics,
    this.uptime,
  });

  factory ServerDetail.fromJson(Map<String, dynamic> json) {
    final serverData = json['server'] as Map<String, dynamic>? ?? json;
    final server = Server.fromJson(serverData);

    final cpuCores = server.latestMetric?.cpuCores ?? 1;

    final metricsList = (json['metrics'] as List<dynamic>?)
            ?.map((m) =>
                MetricPoint.fromJson(m as Map<String, dynamic>, cpuCores))
            .toList() ??
        [];

    return ServerDetail(
      server: server,
      metrics: metricsList,
      uptime: json['uptime'] as String?,
    );
  }
}
