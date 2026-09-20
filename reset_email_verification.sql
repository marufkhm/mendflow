-- Сброс верификации для теста / после ошибочного UPDATE
-- Запусти один раз, затем зарегистрируйся или войди — придёт код на email.

UPDATE users SET email_verified_at = NULL;

-- Или только один email:
-- UPDATE users SET email_verified_at = NULL WHERE email = 'you@email.com';
