-- Token de redefinição de senha com expiração
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS token_expira_em TIMESTAMPTZ NULL;
