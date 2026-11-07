-- Migration 042: "Disponible siempre" para maquinarias.
-- Cuando una maquinaria se fabrica a pedido (no es unidad unica), se marca
-- disponible_siempre = TRUE y su producto asociado no limita stock en Ventas.

ALTER TABLE maquinarias ADD COLUMN IF NOT EXISTS disponible_siempre BOOLEAN NOT NULL DEFAULT FALSE;
