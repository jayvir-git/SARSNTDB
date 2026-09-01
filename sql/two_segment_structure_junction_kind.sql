-- Add CJ vs non-canonical NJ flag to two_segment_structure (run once on existing DBs).
-- Safe to re-run only if your server supports ADD COLUMN IF NOT EXISTS; otherwise skip if column exists.

ALTER TABLE `two_segment_structure`
  ADD COLUMN `junction_kind` varchar(3) NOT NULL DEFAULT 'CJ'
  COMMENT 'CJ=canonical junction, NJ=non-canonical junction'
  AFTER `subtype`;
