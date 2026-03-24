import 'server.dart';

class DashboardResponse {
  final List<Server> servers;
  final Map<String, int> stats;

  DashboardResponse({
    required this.servers,
    required this.stats,
  });

  int get total => stats['total'] ?? servers.length;
  int get online => stats['online'] ?? 0;
  int get warning => stats['warning'] ?? 0;
  int get critical => stats['critical'] ?? 0;
  int get offline => stats['offline'] ?? 0;

  factory DashboardResponse.fromJson(Map<String, dynamic> json) {
    final serversList = (json['servers'] as List<dynamic>?)
            ?.map((s) => Server.fromJson(s as Map<String, dynamic>))
            .toList() ??
        [];

    final statsMap = <String, int>{};
    if (json['stats'] != null) {
      (json['stats'] as Map<String, dynamic>).forEach((key, value) {
        statsMap[key] = (value as num).toInt();
      });
    } else {
      // Compute stats from servers list if not provided
      statsMap['total'] = serversList.length;
      statsMap['online'] =
          serversList.where((s) => s.status == 'online').length;
      statsMap['warning'] =
          serversList.where((s) => s.status == 'warning').length;
      statsMap['critical'] =
          serversList.where((s) => s.status == 'critical').length;
      statsMap['offline'] =
          serversList.where((s) => s.status == 'offline').length;
    }

    return DashboardResponse(
      servers: serversList,
      stats: statsMap,
    );
  }
}
