-- Migration 007: Add imagen column to productos
ALTER TABLE productos ADD COLUMN IF NOT EXISTS imagen VARCHAR(255);
