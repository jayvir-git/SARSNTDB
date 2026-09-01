# Before publishing to GitHub

Do not push until Dr. Grigoriev confirms this should be public (or lab-private) and what the license is.

## Must stay off the remote

- `connection.php` (gitignored; use `connection.example.php`)
- `_incoming/` data files (xlsx, csv, bed, pdf, docx)
- Meeting transcripts and `_incoming` packets
- `.codex-tools/`, `scripts/node_modules/`

## Confirm with the lab

- License for this repository
- Whether `canvasjs-non-commercial-3.6.6/` and `phpGrid_Lite/` may be published
- Whether root SQL dumps (`SARS.sql`, `gene_1_25.sql`, `cov_comp_25.sql`) are OK to share
- New vs fork of the older public tree, if any

## Git state to watch

This working copy is also the XAMPP folder. Most of the app may still be untracked. A `git add .` after `.gitignore` is in place should not pick up secrets or incoming data; check `git status` before the first push.
