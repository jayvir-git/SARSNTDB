# Prerequisites

Before installing SARSNTDB, ensure you have the following.

## Required software

### 1. XAMPP (or equivalent)

- **XAMPP** provides Apache (web server), MySQL/MariaDB (database), and PHP in one package.
- Download from [https://www.apachefriends.org/](https://www.apachefriends.org/) and install.
- You need at least:
  - **Apache** – to serve the PHP application.
  - **MySQL** (or MariaDB) – to store genes, proteins, mutations, repeats, and comparison data.
  - **PHP** – the project was developed with PHP (e.g. 7.4 / 8.x); ensure the PHP version is compatible (avoid very old or very new major versions if you see errors).

### 2. Web browser

- Any modern browser (Chrome, Firefox, Edge, Safari) for opening `http://localhost/SARSNTDB/...`.

### 3. (Optional) Python

- Some scripts (e.g. in `fastas/`, `getvcfdata.py`) use Python for processing FASTA or VCF data. Required only if you run those scripts or regenerate data; not required for the normal web app.

## Required data and configuration

### 1. Database dump (SQL)

- The project expects a MySQL database named **`app_sarsntdb`** (this is the official name used in project rules and `connection.php`).
- You need SQL dumps to create tables and load data. Typical sources:
  - **SARS.sql** – in the repo; defines `Gene_1`, `Gene_2`, `Protein_1` and inserts baseline data.
  - **gene_1_25.sql**, **cov_comp_25.sql** – optional/extended schema and comparison data; your professor or repo may provide these. If used, they may be imported into the same database (possibly with table name `Gene_1` and extra columns like `Function`, `matchedcols`) or a different schema; follow the professor’s instructions.
- If the dump was created for a database named `SARS` or `web_app`, create the database as `app_sarsntdb` and import the dump; you can change the DB name in the SQL before import if needed.

### 2. MySQL user

- A MySQL user that can connect to the server and has privileges on `app_sarsntdb` (e.g. SELECT, INSERT, UPDATE, DELETE for development).
- The username and password must match what is in **`connection.php`** in the project root (see [Installation](03-installation.md)).

### 3. Reference FASTA (for sequence search)

- The **genome sequence search** (entering a DNA sequence instead of Start/End numbers) uses a reference genome file.
- Path: **`fastas/reference.fasta`** (relative to the project root). If this file is missing, sequence search may return no matches; coordinate-based search and other features can still work.

## Optional

- **phpMyAdmin** – often bundled with XAMPP; useful for creating the database, importing SQL, and creating the MySQL user.
- **Code editor** (e.g. VS Code, Cursor) – for editing PHP/JS and following the documentation.
- **Git** – if the project is cloned from a repo; not required if you only copy files into `htdocs`.

## Summary checklist

- [ ] XAMPP installed; Apache and MySQL can be started.
- [ ] Project files present under `htdocs/SARSNTDB` (or your chosen folder).
- [ ] Database `app_sarsntdb` created.
- [ ] SQL dumps imported (at least the main schema and data).
- [ ] MySQL user created with access to `app_sarsntdb`.
- [ ] `connection.php` contains the correct host, user, password, and database name.
- [ ] (Optional) `fastas/reference.fasta` present for DNA sequence search.

Next: [Installation](03-installation.md) for step-by-step setup.
