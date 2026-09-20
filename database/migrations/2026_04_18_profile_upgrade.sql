ALTER TABLE profiles
ADD COLUMN style_font VARCHAR(50) NULL AFTER avatar_path,
ADD COLUMN style_accent_color VARCHAR(20) NULL AFTER style_font,
ADD COLUMN style_card_color VARCHAR(20) NULL AFTER style_accent_color;

ALTER TABLE profile_interests
ADD UNIQUE KEY uniq_user_interest (user_id, interest_slug);
