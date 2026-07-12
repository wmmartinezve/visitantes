import 'package:flutter/material.dart';
import 'package:visitantes_mobile/core/api/field_api.dart';
import 'package:visitantes_mobile/core/models/mobile_user.dart';
import 'package:visitantes_mobile/core/offline/catalog_service.dart';
import 'package:visitantes_mobile/core/offline/sync_service.dart';
import 'package:visitantes_mobile/features/anfitrion/register_guest_screen.dart';

/// Contenedor del tab «Registrar» con superficie Material explícita.
class RegisterGuestFlow extends StatelessWidget {
  const RegisterGuestFlow({
    super.key,
    required this.user,
    required this.catalog,
    required this.sync,
    required this.fieldApi,
    required this.nucleoYaRegistrado,
    required this.requiereRegistroHogar,
    required this.registrarNuevoHogar,
    this.onRegistered,
    this.onUserUpdated,
    this.onRegistrarOtroHogar,
    this.onExitWizard,
  });

  final MobileUser user;
  final CatalogService catalog;
  final SyncService sync;
  final FieldApi fieldApi;
  final bool nucleoYaRegistrado;
  final bool requiereRegistroHogar;
  final bool registrarNuevoHogar;
  final VoidCallback? onRegistered;
  final ValueChanged<MobileUser>? onUserUpdated;
  final VoidCallback? onRegistrarOtroHogar;
  final VoidCallback? onExitWizard;

  @override
  Widget build(BuildContext context) {
    return RegisterGuestScreen(
      user: user,
      catalog: catalog,
      sync: sync,
      fieldApi: fieldApi,
      nucleoYaRegistrado: nucleoYaRegistrado,
      requiereRegistroHogar: requiereRegistroHogar,
      registrarNuevoHogar: registrarNuevoHogar,
      onRegistered: onRegistered,
      onUserUpdated: onUserUpdated,
      onRegistrarOtroHogar: onRegistrarOtroHogar,
      onExitWizard: onExitWizard,
    );
  }
}
