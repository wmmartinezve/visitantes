#!/usr/bin/env bash
# Imprime SHA-1/SHA-256 del keystore de debug (Android Studio / flutter run).
# Agréguelos en Google Cloud Console → Credenciales → API key → Restricciones Android.
set -euo pipefail
cd "$(dirname "$0")/../android"
./gradlew signingReport 2>/dev/null | grep -E "Variant:|SHA1:|SHA-256:" | head -40
