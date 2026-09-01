# Running and verifying

## How to run the application

1. **Start XAMPP** (or your stack): ensure **Apache** and **MySQL** are running.
2. **Open a browser** and go to:
   - **Main app:** `http://localhost/SARSNTDB/GenomeSearch.php`
   - **Home:** `http://localhost/SARSNTDB/index.php`
   - Replace `SARSNTDB` with the name of the folder you use under `htdocs` if different.

No build step is required: PHP is executed by Apache when you request a `.php` URL.

## First-time verification

1. **Home page**  
   - Visit `index.php`. You should see “Welcome to SARSNTDB!” and a pie chart. Navigation (Search, Reference, Help) should appear.

2. **Genome search**  
   - Go to `GenomeSearch.php`.  
   - Select a Gene/Protein from the dropdown (e.g. “S Gene” or “Surface Glycoprotein”) and click the search button, or enter Start/End (e.g. Start `21563`, End `25384`) and submit.  
   - You should be taken to `GenomeResult.php` with a table of genes in that range and/or domain comparison data. If the database has no data for that range, you may see an empty or “no results” message instead of an error.

3. **Mutations**  
   - From the nav, Search → Mutations. Use the mutations search form; you should see summary/detail pages or charts if the mutations tables are populated.

4. **Repeats**  
   - Search → Repeats (`motifvisualizer.php`). Enter a short sequence (e.g. `ACGAAC`) and submit. You should see a visualization and/or table of positions if the `repeats` table exists and has data.

5. **Reference and Help**  
   - Reference: `reference.php` (embedded thesis PDF).  
   - Help: `help.php` (instructions and screenshots).

## Sequence search (optional)

- On `GenomeSearch.php`, in the **Start** (or End) field, enter a **DNA sequence** (e.g. `ACGAACTT`) instead of a number, then submit.
- The app will search `fastas/reference.fasta` and show “Sequence Search Results” with all match positions. This requires `fastas/reference.fasta` to exist and contain the reference genome.

## Repo vs htdocs (two locations)

- **Editing:** You may edit files in a **repository** (e.g. `Documents\SARSNTDB-main\...`).
- **Serving:** Apache serves from **htdocs** (e.g. `C:\xampp\htdocs\SARSNTDB\`).
- Changes in the repo do **not** affect the running site until files are **copied** into the htdocs folder. If you don’t see your changes, copy the updated files (or the whole project) to the htdocs path. See [Development: Repo vs XAMPP](../05-development/04-repo-vs-htdocs.md).

## Stopping the application

- Stop **Apache** (and optionally MySQL) from the XAMPP Control Panel. No separate “stop” step is needed for the PHP app itself.
