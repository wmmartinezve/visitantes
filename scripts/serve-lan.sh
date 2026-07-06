#!/usr/bin/env bash
# Expone Laravel en la red local para que la app móvil en dispositivo físico pueda conectar.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

IP="$(ipconfig getifaddr en0 2>/dev/null || ipconfig getifaddr en1 2>/dev/null || hostname -I 2>/dev/null | awk '{print $1}' || echo 'TU_IP_LOCAL')"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo " Visitantes — servidor LAN"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo " En la app móvil (dispositivo físico), use:"
echo "   http://${IP}:8000"
echo ""
echo " Misma red Wi‑Fi que este equipo."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

php artisan serve --host=0.0.0.0 --port=8000
