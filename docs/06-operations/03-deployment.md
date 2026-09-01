# Deployment (copy to XAMPP)

SARSNTDB is run locally by **copying** the project (or changed files) into the XAMPP **htdocs** folder. There is no separate “build” step.

## Steps

1. **Ensure XAMPP** is installed and that Apache and MySQL are running.
2. **Create/update the database** in MySQL (e.g. phpMyAdmin):
   - Database name: **app_sarsntdb**
   - Import the provided SQL (e.g. SARS.sql, and optionally gene_1_25.sql, cov_comp_25.sql, and mutation/repeat dumps).
   - Create a MySQL user that matches **connection.php** and grant it privileges on **app_sarsntdb**.
3. **Copy the project** from your **repository** (or source folder) into the web server document root so that the app is available at a path like **SARSNTDB**:
   - **Windows (XAMPP):** Copy to **`C:\xampp\htdocs\SARSNTDB\`**
   - **Mac/Linux (XAMPP):** Copy to **`/Applications/XAMPP/htdocs/SARSNTDB/`** or the equivalent **htdocs** path on your system.
4. **Configure connection.php** (copy from `connection.example.php`) so that `$host`, `$user`, `$password`, and `$dbname` match your MySQL setup. Do not commit `connection.php`.
5. **Verify** by opening **http://localhost/SARSNTDB/GenomeSearch.php** (replace SARSNTDB with the folder name you used). Test a search and, if applicable, sequence search and mutations/repeats.

## Minimal file set (if not copying the whole project)

For a **minimal update** after editing only a few files, copy at least:

- **GenomeSearch.php**, **GenomeResult.php** (genome/sequence search).
- **connection.php**, **Navigation.php**, **ProtienInfo.php** (and **ProteinInfo.php** if used).
- **style.css** and/or **styles.css**, **bootstrap.css** (if changed).
- **fastas/reference.fasta** (if changed or missing).
- Any **PHP/JS** you modified (e.g. MutationsSearch.php, motifvisualizer.php, repeatData.php).
- **helpimages/** (if you added or changed images or the thesis PDF).

Copying the **entire** repo into htdocs is the safest way to keep the running site in sync with your edits.

## Production or shared server

- The app is designed for **localhost**. For any other environment:
  - Set **connection.php** (or env vars) to that server’s MySQL host, user, password, and database.
  - Turn off PHP **display_errors** and rely on **error_log**.
  - Ensure the web server document root points to the folder that contains **index.php**, **GenomeSearch.php**, **connection.php**, etc.
  - Restrict access to **connection.php** and other sensitive files via server config if needed (e.g. deny direct access to certain paths).

## Summary

- **Deployment** = copy repo (or changed files) to **htdocs/SARSNTDB/** and configure **connection.php** and MySQL.
- No build step; PHP runs on request. After copying, reload the browser to see changes.
