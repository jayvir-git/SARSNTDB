# Troubleshooting

Common problems and how to fix them.

---

## Blank page or 500 Internal Server Error

### Causes and fixes

1. **PHP error** – A fatal error or uncaught exception can cause a blank page or 500.
   - **Fix:** Enable error display temporarily. In the PHP file that runs first (or in a shared include), add at the top:
     ```php
     ini_set('display_errors', 1);
     error_reporting(E_ALL);
     ```
   - Reload the page and check the browser (and/or the Apache error log) for the message. Then fix the reported error and remove or reduce `display_errors` for production.
2. **Database connection failure** – If **connection.php** fails (wrong host, user, password, or database name), the script may `die()` and leave the page blank or show “Could not connect to the database server”.
   - **Fix:** Verify in **connection.php**: `$host`, `$user`, `$password`, `$dbname`. Ensure MySQL is running, the database **app_sarsntdb** exists, and the MySQL user has privileges on it. Test the same credentials in phpMyAdmin or command-line MySQL.
3. **Missing include** – If a required file is missing (e.g. **connection.php**, **Navigation.php**, **ProtienInfo.php**), PHP may fatal on `require_once` or `include`.
   - **Fix:** Ensure all required files exist at the paths used (relative to the script that includes them). If you moved the project, fix the paths (e.g. `./connection.php` vs `../connection.php`).

---

## “Could not connect to the database server”

- The **mysqli** constructor in **connection.php** failed.
- **Check:** MySQL service is running (XAMPP Control Panel). Database **app_sarsntdb** exists. User and password in **connection.php** match a MySQL user that can connect (host 127.0.0.1 or localhost). No firewall blocking MySQL port (usually 3306).
- **Test:** Create a small PHP file that only does `require_once 'connection.php';` and then `echo "OK";`. If you see “Could not connect” and then “OK” never appears, the connection is the problem.

---

## Empty or wrong results (genes, domains, mutations, repeats)

### Possible causes

1. **Wrong or empty database** – Tables exist but have no rows, or the data was imported into a different database/table name.
   - **Fix:** In phpMyAdmin, confirm **app_sarsntdb** is selected and that **Gene_1**, **Protein_1**, **cov_comp**, **repeats** (etc.) exist and contain data. Re-import the SQL dumps if needed.
2. **Column name mismatch** – The PHP expects columns that don’t exist (e.g. **Function**, **matchedcols** in **Gene_1**). The query may fail or return no rows.
   - **Fix:** Compare the schema in your database with the queries in **GenomeResult.php**, **repeatData.php**, **GenomeComparisonData.php**, etc. Add missing columns or adjust the PHP to match your schema. See [Database schema](../03-architecture/04-database-schema.md).
3. **JOIN or filter logic** – e.g. **Protein** in the dropdown might not exactly match the value in the database (case, spaces, “ORF3a” vs “ORF3a Protein”). The app sometimes normalizes the protein name for **cov_comp** (e.g. to lowercase gene name). If results are empty for one protein only, check how the selected value is used in the WHERE clause.
4. **Sequence search returns nothing** – DNA sequence search uses **fastas/reference.fasta**. If the file is missing or the sequence is not in the file, there are no matches.
   - **Fix:** Ensure **fastas/reference.fasta** exists and contains the SARS-CoV-2 reference genome (one or more sequences). Check that the input uses only A/T/G/C (and that the reference uses the same convention).

---

## Changes in code not visible in the browser

- **Cause:** You are editing the **repository** copy, but Apache serves from **XAMPP htdocs** (e.g. **C:\xampp\htdocs\SARSNTDB\**).
- **Fix:** Copy the modified PHP/JS/CSS (or the whole project) from the repo into the htdocs folder. Reload the page (and do a hard refresh if needed: Ctrl+F5). See [Repo vs XAMPP](../05-development/04-repo-vs-htdocs.md).

---

## Reference or Help page: missing PDF or images

- **reference.php** embeds **helpimages/SARSNTDB-JO-thesis.pdf**. If the PDF is missing, the iframe will be empty or show an error.
- **help.php** references images in **helpimages/** (e.g. weclomepage.png, sgenedetail.png). Broken image links mean those files are missing or the path is wrong.
- **Fix:** Ensure **helpimages/** contains the PDF and all referenced image files. Paths are relative to the document root (e.g. `./helpimages/...`).

---

## Mutations page: no data or broken charts

- Mutations data comes from **mutation tables** in the database. If those tables were never created or populated, the mutations pages will be empty.
- **Fix:** Import the SQL that creates and fills the mutation tables (if provided by the professor or repo). Check **MutationsSummary.php** and **MutationsDetail.php** for the exact table and column names and ensure they match your schema.
- If the page loads but **charts** don’t render, check the browser console for JavaScript errors and ensure **CanvasJS** is loaded (path to canvasjs.min.js or jquery.canvasjs.min.js is correct).

---

## Repeats: “Repeat not found in database”

- **repeatData.php** queries the **repeats** table. If the exact sequence (or a LIKE match) is not in the table, the API returns a message like “Repeat not found in database…”.
- **Fix:** Ensure the **repeats** table exists and is populated (e.g. from a provided SQL dump). The app may require a minimum length (e.g. 6). Try a sequence that you know exists in the genome and that is present in the **repeats** table.

---

## Quick checklist

- [ ] Apache and MySQL are running (XAMPP).
- [ ] **connection.php** has the correct host, user, password, **dbname = app_sarsntdb**.
- [ ] Database **app_sarsntdb** exists and has been imported with the main SQL (e.g. SARS.sql).
- [ ] Tables **Gene_1**, **Protein_1**, **cov_comp**, **repeats** (and mutation tables if using mutations) exist and have data.
- [ ] **fastas/reference.fasta** exists for sequence search.
- [ ] You are testing the copy under **htdocs**, not only the repo.
- [ ] PHP errors are visible (temporarily) or checked in the server error log.

For step-by-step debugging (tracing queries, checking GET/POST), see [Debugging](02-debugging.md).
