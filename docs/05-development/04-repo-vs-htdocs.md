# Repo vs XAMPP htdocs

**Current setup:** this repository *is* the XAMPP folder (`C:\xampp\htdocs\SARSNTDB\`). Apache serves the files you edit. There is no copy step.

`connection.php` stays gitignored. New clones copy `connection.example.php` to `connection.php` and set the local password.

## If the repo and htdocs are ever split

Apache only serves from htdocs. Edits in a Documents clone do not appear at `http://localhost/SARSNTDB/` until those files are copied into `C:\xampp\htdocs\SARSNTDB\`. Do not overwrite a working `connection.php` with placeholders.
