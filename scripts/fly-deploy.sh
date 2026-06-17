#!/usr/bin/env bash
# Deploy LabHub no Fly.io (região gru — São Paulo)
#
# Pré-requisitos:
#   1. fly auth login
#   2. export DB_PASSWORD=... MAIL_PASSWORD=... (senhas do Supabase e Brevo)
#
# Uso:
#   ./scripts/fly-deploy.sh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if ! command -v fly >/dev/null 2>&1; then
  echo "Instalando Fly CLI..."
  curl -L https://fly.io/install.sh | sh
  export PATH="$HOME/.fly/bin:$PATH"
fi

APP_NAME="${FLY_APP_NAME:-labhub-uniceplac-sp}"
REGION="${FLY_REGION:-gru}"

echo "→ Verificando login Fly.io..."
if ! fly auth whoami >/dev/null 2>&1; then
  echo "Execute: fly auth login"
  exit 1
fi

echo "→ App: ${APP_NAME} (região ${REGION})"
if ! fly apps list 2>/dev/null | grep -q "${APP_NAME}"; then
  fly apps create "${APP_NAME}" --org personal 2>/dev/null || fly apps create "${APP_NAME}"
fi

echo "→ Volume para uploads (fotos de perfil)..."
if ! fly volumes list -a "${APP_NAME}" 2>/dev/null | grep -q labhub_uploads; then
  fly volumes create labhub_uploads --size 1 --region "${REGION}" -a "${APP_NAME}" --yes
fi

if [[ -z "${DB_PASSWORD:-}" ]]; then
  echo "Erro: export DB_PASSWORD=senha_supabase antes de continuar."
  exit 1
fi

echo "→ Definindo secrets..."
SECRETS=(
  "DB_PASSWORD=${DB_PASSWORD}"
)
if [[ -n "${MAIL_PASSWORD:-}" ]]; then
  SECRETS+=("MAIL_PASSWORD=${MAIL_PASSWORD}")
fi
if [[ -n "${GOOGLE_CLIENT_ID:-}" ]]; then
  SECRETS+=("GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID}")
  SECRETS+=("GOOGLE_CLIENT_SECRET=${GOOGLE_CLIENT_SECRET}")
  SECRETS+=("GOOGLE_REDIRECT_URI=${GOOGLE_REDIRECT_URI:-https://${APP_NAME}.fly.dev/login_google.php}")
fi

fly secrets set "${SECRETS[@]}" -a "${APP_NAME}"

echo "→ Deploy..."
fly deploy --remote-only -a "${APP_NAME}"

echo ""
echo "✓ Deploy concluído!"
echo "  URL: https://${APP_NAME}.fly.dev"
echo ""
echo "Atualize APP_URL se usar domínio customizado:"
echo "  fly secrets set APP_URL=https://seu-dominio -a ${APP_NAME}"
