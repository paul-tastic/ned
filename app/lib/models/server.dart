class Disk {
  final String mount;
  final int total;
  final int used;

  Disk({
    required this.mount,
    required this.total,
    required this.used,
  });

  double get usagePercent => total > 0 ? (used / total) * 100 : 0;

  factory Disk.fromJson(Map<String, dynamic> json) {
    return Disk(
      mount: json['mount'] as String? ?? json['filesystem'] as String? ?? '',
      total: (json['total'] as num?)?.toInt() ?? 0,
      used: (json['used'] as num?)?.toInt() ?? 0,
    );
  }
}

class LatestMetric {
  final double load1m;
  final int cpuCores;
  final int memoryTotal;
  final int memoryUsed;
  final List<Disk> disks;
  final Map<String, String> services;
  final DateTime recordedAt;

  LatestMetric({
    required this.load1m,
    required this.cpuCores,
    required this.memoryTotal,
    required this.memoryUsed,
    required this.disks,
    required this.services,
    required this.recordedAt,
  });

  double get cpuPercent =>
      cpuCores > 0 ? (load1m / cpuCores) * 100 : 0;

  double get memoryPercent =>
      memoryTotal > 0 ? (memoryUsed / memoryTotal) * 100 : 0;

  double get diskPercent {
    if (disks.isEmpty) return 0;
    final totalSize = disks.fold<int>(0, (sum, d) => sum + d.total);
    final totalUsed = disks.fold<int>(0, (sum, d) => sum + d.used);
    return totalSize > 0 ? (totalUsed / totalSize) * 100 : 0;
  }

  factory LatestMetric.fromJson(Map<String, dynamic> json) {
    return LatestMetric(
      load1m: (json['load_1m'] as num?)?.toDouble() ?? 0,
      cpuCores: (json['cpu_cores'] as num?)?.toInt() ?? 1,
      memoryTotal: (json['memory_total'] as num?)?.toInt() ?? 0,
      memoryUsed: (json['memory_used'] as num?)?.toInt() ?? 0,
      disks: (json['disks'] as List<dynamic>?)
              ?.map((d) => Disk.fromJson(d as Map<String, dynamic>))
              .toList() ??
          [],
      services: (json['services'] as Map<String, dynamic>?)?.map(
              (k, v) => MapEntry(k, v.toString())) ??
          {},
      recordedAt: DateTime.parse(
          json['recorded_at'] as String? ?? DateTime.now().toIso8601String()),
    );
  }
}

class Server {
  final int id;
  final String name;
  final String? hostname;
  final String status;
  final DateTime? lastSeenAt;
  final String? agentVersion;
  final LatestMetric? latestMetric;

  Server({
    required this.id,
    required this.name,
    this.hostname,
    required this.status,
    this.lastSeenAt,
    this.agentVersion,
    this.latestMetric,
  });

  factory Server.fromJson(Map<String, dynamic> json) {
    return Server(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String? ?? 'Unknown',
      hostname: json['hostname'] as String?,
      status: json['status'] as String? ?? 'offline',
      lastSeenAt: json['last_seen_at'] != null
          ? DateTime.tryParse(json['last_seen_at'] as String)
          : null,
      agentVersion: json['agent_version'] as String?,
      latestMetric: json['latest_metric'] != null
          ? LatestMetric.fromJson(
              json['latest_metric'] as Map<String, dynamic>)
          : null,
    );
  }
}
