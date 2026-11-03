-- Removes the canonical and TRS-L rows that scripts/import_two_segment_junctions.py added to the Two-segment page (D-85).
DELETE FROM `two_segment_structure` WHERE `notes` = 'Two-segment import (D-85)';
