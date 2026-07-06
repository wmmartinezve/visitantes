import 'package:flutter_test/flutter_test.dart';
import 'package:visitantes_mobile/main.dart';

void main() {
  testWidgets('App arranca con pantalla de login', (tester) async {
    await tester.pumpWidget(const VisitantesApp());
    expect(find.textContaining('Visitantes'), findsWidgets);
    expect(find.text('Ingresar'), findsOneWidget);
  });
}
