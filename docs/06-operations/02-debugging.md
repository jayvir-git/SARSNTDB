# Debugging

Practical ways to trace and fix issues in SARSNTDB.

## 1. PHP errors

- **Display errors (temporary):** At the top of the script that runs (e.g. GenomeResult.php), add:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
  Reload the page and read the error message. Remove or turn off display_errors once done.
- **Log errors:** Check the Apache/PHP error log (e.g. in XAMPP: `xampp/apache/logs/error.log`). PHP logs there when display_errors is off.

## 2. Database errors

- **Query failure:** If `$con->query($sql)` returns false, get the error:
  ```php
  if (!$result) {
      echo $con->error;
      echo "\nSQL: " . $sql;
      exit;
  }
  ```
  This shows the MySQL error and the exact SQL. Fix the SQL or the schema (e.g. wrong table/column name).
- **Empty result set:** After `$result->fetch_all(MYSQLI_ASSOC)`, check `$result->num_rows`. If 0, the query ran but found no rows. Add temporary `echo $sql;` to see the built query and run it in phpMyAdmin to verify filters (Start, End, Protein, etc.).

## 3. GET/POST parameters

- **Inspect what the page receives:** At the top of the script (after reading GET/POST), temporarily:
  ```php
  echo '<pre>GET: '; print_r($_GET); echo '</pre>';
  echo '<pre>POST: '; print_r($_POST); echo '</pre>';
  exit;
  ```
  Reload or submit the form and confirm parameter names and values (e.g. `Start`, `End`, `Protein`). Fix the form or the PHP if a name is wrong or missing.

## 4. Sequence search (DNA)

- **Check reference file:** Verify **fastas/reference.fasta** exists and is readable. In PHP you can:
  ```php
  echo file_exists('./fastas/reference.fasta') ? 'exists' : 'missing';
  ```
- **Check match positions:** In **GenomeResult.php**, after `findSequenceMatches()`, temporarily `print_r($startMatches);` to see that positions are found and 1-based as expected.
- **Session:** Sequence matches are stored in **$_SESSION**. Ensure `session_start()` is called before using `$_SESSION`. If the “Sequence Search Results” block never appears, verify that `$_SESSION['sequence_matches']` and `$_SESSION['search_sequence']` are set and output in the HTML.

## 5. Front end (JavaScript / AJAX)

- **Browser DevTools:** Open F12 → **Console** for JavaScript errors. Open **Network** to see requests and responses.
- **AJAX calls:** Find the request to the endpoint (e.g. GenomeComparisonData.php?Protein=...). Check **Status** (200 vs 4xx/5xx) and **Response** (HTML or JSON). If the response is HTML with a PHP error, fix the PHP. If the response is empty, the PHP may have crashed or returned nothing.
- **jQuery:** If using `$.get()` or `$.ajax()`, add a `.fail()` handler and log the response so you see server errors.

## 6. Data flow

- **Trace the flow:** Follow the doc [Data flow](../03-architecture/03-data-flow.md). For the action you’re debugging, identify: which PHP runs, which GET/POST it uses, which tables it queries, and what it outputs. Add temporary `echo` or `error_log()` at each step to confirm parameters, SQL, and row counts.
- **Compare with schema:** Ensure every column and table name in the PHP matches [Database schema](../03-architecture/04-database-schema.md) (or your actual DB). A typo (e.g. `Gene_1` vs `gene_1`, or `Start` vs `start`) can cause “Unknown column” or empty results.

## 7. Repo vs htdocs

- If you edit in the **repo** but don’t see changes in the browser, you are likely running the **htdocs** copy. Copy the updated file(s) to **C:\xampp\htdocs\SARSNTDB\** (or your htdocs path) and reload. See [Repo vs XAMPP](../05-development/04-repo-vs-htdocs.md).

## Summary

- Use **display_errors** and **error_log** to see PHP errors.
- Use **$con->error** and **echo $sql** to debug failed or empty queries.
- Use **print_r($_GET)** / **$_POST** to verify parameters.
- Use **browser DevTools** (Console + Network) for JS and AJAX.
- Trace the flow from URL → PHP → DB → output and confirm each step.
