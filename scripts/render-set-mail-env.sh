#!/usr/bin/env bash
# Configura variáveis de e-mail (Brevo) no serviço Render labhub-uniceplac.
#
# Uso:
#   export RENDER_API_KEY=rnd_xxxxxxxx   # https://dashboard.render.com/u/settings#api-keys
#   export MAIL_PASSWORD=sua_chave_brevo
#   ./scripts/render-set-mail-env.sh
#
# Opcional: MAIL_FROM_ADDRESS (padrão admin@uniceplac.edu.br)

set -euo pipefail

SERVICE_NAME="${RENDER_SERVICE_NAME:-labhub-uniceplac}"
API_BASE="https://api.render.com/v1"

if [[ -z "${RENDER_API_KEY:-}" ]]; then
  echo "Erro: defina RENDER_API_KEY (Render Dashboard → Account Settings → API Keys)" >&2
  exit 1
fi

if [[ -z "${MAIL_PASSWORD:-}" ]]; then
  echo "Erro: defina MAIL_PASSWORD (chave SMTP/API da Brevo)" >&2
  exit 1
fi

auth_header="Authorization: Bearer ${RENDER_API_KEY}"

echo "→ Buscando serviço ${SERVICE_NAME}..."
services_json=$(curl -sf "${API_BASE}/services?limit=100" -H "${auth_header}" -H "Accept: application/json")

service_id=$(echo "${services_json}" | python3 -c "
import json, sys
data = json.load(sys.stdin)
for item in data:
    svc = item.get('service') or item
    if svc.get('name') == sys.argv[1]:
        print(svc['id'])
        break
" "${SERVICE_NAME}")

if [[ -z "${service_id}" ]]; then
  echo "Erro: serviço '${SERVICE_NAME}' não encontrado na conta Render." >&2
  exit 1
fi

echo "→ Serviço encontrado: ${service_id}"

set_env() {
  local key="$1"
  local value="$2"
  local encoded_key
  encoded_key=$(python3 -c "import urllib.parse; print(urllib.parse.quote('${key}', safe=''))")
  curl -sf -X PUT "${API_BASE}/services/${service_id}/env-vars/${encoded_key}" \
    -H "${auth_header}" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d "$(python3 -c "import json,sys; print(json.dumps({'value': sys.argv[1]}))" "${value}")" \
    > /dev/null
  echo "  ✓ ${key}"
}

MAIL_FROM="${MAIL_FROM_ADDRESS:-admin@uniceplac.edu.br}"

set_env "APP_URL" "https://labhub-uniceplac.onrender.com"
set_env "MAIL_HOST" "smtp-relay.brevo.com"
set_env "MAIL_PORT" "587"
set_env "MAIL_USERNAME" "ae8949001@smtp-brevo.com"
set_env "MAIL_PASSWORD" "${MAIL_PASSWORD}"
set_env "MAIL_FROM_ADDRESS" "${MAIL_FROM}"
set_env "MAIL_FROM_NAME" "LabHub UNICEPLAC"

echo ""
echo "→ Disparando redeploy..."
curl -sf -X POST "${API_BASE}/services/${service_id}/deploys" \
  -H "${auth_header}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"clearCache":"do_not_clear"}' > /dev/null

echo "✓ Variáveis aplicadas e deploy iniciado."
echo "  Verifique: https://dashboard.render.com"
