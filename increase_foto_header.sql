-- Increase foto_header column length from 50 to 255 characters
ALTER TABLE project MODIFY foto_header VARCHAR(255) DEFAULT '';
