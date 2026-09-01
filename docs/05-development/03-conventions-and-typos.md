# Conventions and typos

The codebase has a few intentional or legacy naming choices. Respect them so existing includes and links keep working.

## ProtienInfo.php (typo kept)

- The file **ProtienInfo.php** (misspelling of “Protein”) is included by **GenomeSearch.php** and possibly others.
- **ProteinInfo.php** (correct spelling) also exists and is used by **GenomeDetailData.php** (it defines the class used for detail data).
- **Do not rename ProtienInfo.php** to ProteinInfo.php project-wide without updating every script that includes it. When adding new includes that mirror existing pages, use **ProtienInfo.php** where the rest of the app does. The project rules explicitly say: “Keep typo ProtienInfo.php when adding includes.”

## style.css vs styles.css

- Both **style.css** and **styles.css** exist. Some pages link to **style.css**, others to **styles.css**.
- When deploying to XAMPP, the project rules say: copy the repo’s **styles.css** as **style.css** (or ensure both exist) so that pages that request **style.css** get the correct styles. When adding a new page, use the same stylesheet link as the similar existing page (e.g. **style.css** if that’s what GenomeSearch uses).

## Connection and hybrid layout

- **Standard pages** use **`./connection.php`** at the project root (user/db **app_sarsntdb**).
- **hybrid.php** uses **`conn/connection.php`** and **function.php** — a different layout and possibly different credentials. Do not mix these unless you are intentionally working on the hybrid flow.

## Table and column names

- **Gene_1** (capital G) is the main gene table. Some SQL dumps use **gene_1** (lowercase) or a different database name (e.g. **web_app**). The main app expects **Gene_1** in **app_sarsntdb** with columns such as Gene, Start, End, Protein, Accession, Function, matchedcols where used. When adding queries, use the same table/column names as the rest of the codebase (see [Database schema](../03-architecture/04-database-schema.md)).

## Navigation and “active” state

- **Navigation.php** sets the “active” class on the current nav item by comparing **`basename($_SERVER['PHP_SELF'])`** to the script name (e.g. `GenomeSearch.php`, `reference.php`). If you add a new top-level page and want it highlighted in the nav, add a corresponding condition in Navigation.php.

## File locations

- **Repo (editing)** may be in a folder like `Documents\SARSNTDB-main\...`.
- **XAMPP (serving)** is typically **`C:\xampp\htdocs\SARSNTDB\`**.
- Edits in the repo do **not** affect the running site until files are copied to htdocs. See [Repo vs XAMPP](04-repo-vs-htdocs.md).
