# LabHub UNICEPLAC — Deploy

Sistema **100% PHP MVC** (referência: `temp/pasta reserva-edits`).

## URLs

| Ambiente | URL |
|----------|-----|
| **Fly.io (produção — SP)** | https://labhub-uniceplac-sp.fly.dev |
| **Render (legado)** | https://labhub-uniceplac.onrender.com |
| **Local Docker** | http://localhost:8080 |

## Fly.io (São Paulo — `gru`)

App e Supabase na mesma região (sa-east-1) → menor latência no Brasil.

**Importante:** o Fly.io pede cartão cadastrado (mesmo no free tier) antes do primeiro deploy:
https://fly.io/dashboard/contatovinicius-mends-gmail-com/billing

```bash
# 1. CLI e login (uma vez)
curl -L https://fly.io/install.sh | sh
export PATH="$HOME/.fly/bin:$PATH"
fly auth login

# 2. Deploy (secrets do Render, se já configurado)
chmod +x scripts/*.sh
./scripts/fly-migrate-from-render.sh

# Ou manualmente:
export DB_PASSWORD='senha_supabase'
export MAIL_PASSWORD='chave_brevo'
./scripts/fly-deploy.sh
```

**Secrets sensíveis** (não vão no git): `DB_PASSWORD`, `MAIL_PASSWORD`, opcionalmente `GOOGLE_*`.

**Volume** `labhub_uploads` persiste fotos de perfil entre deploys.

Após migrar, atualize `APP_URL` se usar domínio próprio:

```bash
fly secrets set APP_URL=https://seu-dominio -a labhub-uniceplac-sp
```

## Local

```bash
cp .env.example .env
docker compose up --build
```

## Render

1. https://dashboard.render.com → serviço `labhub-uniceplac`
2. Conectado ao repo GitHub — deploy automático a cada push na `main`
3. Variável `DB_PASSWORD` = senha do Supabase
4. **DB_HOST** deve ser `aws-1-sa-east-1.pooler.supabase.com` (não `aws-0`) — copie do dashboard Supabase se mudar

## Logins demo (senha: `password`)

| Perfil | E-mail |
|--------|--------|
| Coordenador | admin@uniceplac.edu.br |
| Professor | professor@uniceplac.edu.br |
| Suporte | suporte@uniceplac.edu.br |
