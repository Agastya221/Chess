SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Add access_code column to students for student login
ALTER TABLE students
    ADD COLUMN IF NOT EXISTS access_code VARCHAR(32) NULL UNIQUE AFTER notes;

-- Generate access codes for existing students that don't have one
UPDATE students
SET access_code = CONCAT(
    SUBSTRING(MD5(RAND()), 1, 4),
    '-',
    SUBSTRING(MD5(RAND()), 1, 4)
)
WHERE access_code IS NULL OR access_code = '';
