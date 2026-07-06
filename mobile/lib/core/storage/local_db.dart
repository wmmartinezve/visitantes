import 'package:hive_flutter/hive_flutter.dart';

class LocalDb {
  static const catalogBox = 'catalog';
  static const queueBox = 'sync_queue';
  static const metaBox = 'meta';

  static Future<void> init() async {
    await Hive.initFlutter();
    await Hive.openBox<dynamic>(catalogBox);
    await Hive.openBox<dynamic>(queueBox);
    await Hive.openBox<dynamic>(metaBox);
  }

  static Box<dynamic> get catalog => Hive.box<dynamic>(catalogBox);
  static Box<dynamic> get queue => Hive.box<dynamic>(queueBox);
  static Box<dynamic> get meta => Hive.box<dynamic>(metaBox);
}
