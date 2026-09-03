# Before publishing to GitHub

The application repo may be public. Keep secrets and vendor-restricted trees off GitHub.

## Must stay off the remote

- `connection.php` (gitignored; use `connection.example.php`)
- `_incoming/` data files (xlsx, csv, bed, pdf, docx)
- Meeting transcripts and `_incoming` packets
- `.codex-tools/`, `scripts/node_modules/`
- `phpGrid_Lite/` (gitignored; phpGrid EULA does not allow distributing the library)

## Confirm with the lab

- License for this repository (still unset)
- CanvasJS is the non-commercial build; keep the chart credit link
- Whether root SQL dumps (`SARS.sql`, `gene_1_25.sql`, `cov_comp_25.sql`) are OK to share
- New vs fork of the older public tree, if any

## Git state to watch

This working copy is also the XAMPP folder. Most of the app may still be untracked. A `git add .` after `.gitignore` is in place should not pick up secrets or incoming data; check `git status` before the first push.
