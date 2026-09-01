# Installation

Follow these steps to install and run SARSNTDB locally (official setup: XAMPP + `app_sarsntdb`).

## Step 1: Install XAMPP and start services

1. Install XAMPP (Apache, MySQL, PHP).
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.

## Step 2: Place the project in htdocs

1. Copy or clone the project so it is available under the web server’s document root.
2. **Recommended path:** `C:\xampp\htdocs\SARSNTDB\` (Windows) or the equivalent `htdocs/SARSNTDB` on your system.
3. Ensure the following are present at the project root:
   - `index.php`, `GenomeSearch.php`, `connection.php`, `Navigation.php`
   - `ProtienInfo.php` (note the typo – it is required by name)
   - `style.css` or `styles.css` (see [Development: Conventions](../05-development/03-conventions-and-typos.md))
   - `fastas/reference.fasta` if you want DNA sequence search to work

## Step 3: Create the database and import SQL

1. Open **phpMyAdmin** (e.g. `http://localhost/phpmyadmin`).
2. Create a new database named **`app_sarsntdb`** (collation e.g. `utf8mb4_general_ci`).
3. Select `app_sarsntdb` and use **Import**.
4. Import the SQL files provided by the project (e.g. from the professor or repo):
   - **SARS.sql** – creates `Gene_1`, `Gene_2`, `Protein_1` and inserts baseline data.
   - If you have **gene_1_25.sql**, **cov_comp_25.sql**, or other dumps, import them as instructed (they may extend the schema or add tables like `cov_comp`, `repeats`, `repeatcoord`).
5. If the dump uses a different database name (e.g. `SARS` or `web_app`), either:
   - Create the database as `app_sarsntdb` and change the database name in the SQL before import, or
   - Create the DB name as in the dump and then update `connection.php` to use that name (the project standard is `app_sarsntdb`).

## Step 4: Create MySQL user and grant privileges

1. In phpMyAdmin, go to **User accounts** (or equivalent).
2. **Add user** with:
   - **User name:** same as in `connection.php` (e.g. `app_sarsntdb`).
   - **Host:** `localhost` or `127.0.0.1` (must match `connection.php`).
   - **Password:** same as in `connection.php`.
3. Grant this user **all privileges** on the database `app_sarsntdb` (or at least SELECT, INSERT, UPDATE, DELETE for normal operation).
4. Apply changes.

## Step 5: Configure connection.php

1. Copy **`connection.example.php`** to **`connection.php`** in the project root (do not commit `connection.php`).
2. Open **`connection.php`** and ensure it matches your MySQL setup:

```php
$host="127.0.0.1";
$port=3306;
$socket="";
$password="YOUR_PASSWORD";   // same as MySQL user
$user="app_sarsntdb";       // same as MySQL user
$dbname="app_sarsntdb";

$con = new mysqli($host, $user, $password, $dbname, $port, $socket)
    or die ('Could not connect to the database server' . mysqli_connect_error());
```

3. If your MySQL user or database name is different, change `$user` and `$dbname` accordingly. The rest of the app expects the connection variable to be `$con`.

## Step 6: Verify the installation

1. Open a browser and go to: **`http://localhost/SARSNTDB/GenomeSearch.php`**  
   (Replace `SARSNTDB` with the folder name you used under `htdocs`.)
2. You should see the genome search page with navigation (Search, Reference, Help) and Gene/Protein dropdown.
3. Try a simple search:
   - Choose a gene/protein from the dropdown, or
   - Enter Start (e.g. `21563`) and End (e.g. `25384`) and submit.
4. If you see results (genes, domains, or a message that no data was found for that range), the app and database are working.
5. Optional: test **sequence search** by entering a short DNA sequence (e.g. `ACGAAC`) in the Start field and submitting; this requires `fastas/reference.fasta` to be present.

## Step 7 (optional): Reference FASTA for sequence search

- The **DNA sequence search** (typing A/T/G/C in Start or End) looks up the sequence in the reference genome.
- Put the SARS-CoV-2 reference genome in **`fastas/reference.fasta`** (standard FASTA format: optional header line starting with `>`, then lines of sequence).
- If this file is missing, sequence search may return no matches; coordinate-based search and other features do not depend on it.

## Troubleshooting

- **Blank page or 500 error:** See [Operations: Troubleshooting](../06-operations/01-troubleshooting.md). Check PHP error display/logs and `connection.php` (host, user, password, dbname).
- **“Could not connect to the database server”:** Verify MySQL is running, the database exists, the user exists, and the password in `connection.php` matches.
- **Empty results for a valid range:** Verify the tables (e.g. `Gene_1`) contain data and column names match what the PHP expects (e.g. `Start`, `End`, `Gene`, `Protein`). See [Architecture: Database schema](../03-architecture/04-database-schema.md).

Next: [Running and verifying](04-running-and-verifying.md).
