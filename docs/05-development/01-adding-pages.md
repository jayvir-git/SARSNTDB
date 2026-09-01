# Adding new pages

To add a new **page** to SARSNTDB (a new URL that renders HTML and fits into the existing app), follow these conventions.

## 1. Create the PHP file

- Create a new `.php` file in the project root (e.g. `MyNewPage.php`).
- At the top (or in the &lt;head&gt;), **include the navigation** so the new page has the same navbar as the rest of the app:

```php
<?php include "./Navigation.php"; ?>
```

Or, if your file is in a subfolder, adjust the path (e.g. `include "../Navigation.php";`).

## 2. Use the database (if needed)

- If the page needs to run **queries**, include the connection **once** and use the shared `$con`:

```php
<?php require_once './connection.php'; ?>
```

- Then use `$con->query(...)` or prepared statements for your queries. Do **not** create a second connection with different credentials unless you intentionally need a different database.

## 3. Keep the typo for compatibility

- Several existing pages include **ProtienInfo.php** (misspelling of “Protein”). When adding includes that other pages use, keep **ProtienInfo.php** where the rest of the app expects it (e.g. if your new page mirrors GenomeSearch, include ProtienInfo.php the same way). Do **not** rename **ProtienInfo.php** to ProteinInfo.php project-wide without updating every file that includes it. See [Conventions and typos](03-conventions-and-typos.md).

## 4. Reuse styles and scripts

- Link the same CSS and JS as the rest of the app so the look and behavior are consistent:
  - **bootstrap.css** (and/or Bootstrap from CDN)
  - **style.css** or **styles.css** (see [Conventions](03-conventions-and-typos.md))
  - jQuery if you need it (already loaded via Navigation in most pages)

## 5. Add the page to navigation (optional)

- If the new page should appear in the **navbar**, edit **Navigation.php** and add a new &lt;li&gt; with a link to your script (e.g. `MyNewPage.php`). You can also add an “active” class when `basename($_SERVER['PHP_SELF']) == 'MyNewPage.php'` so the current page is highlighted.

## Example skeleton

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My New Page - SARSNTDB</title>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <?php include "./Navigation.php"; ?>
</head>
<body>
    <h3>My New Page</h3>
    <?php
    require_once './connection.php';
    $result = $con->query("SELECT ...");
    // ... output HTML from $result ...
    ?>
</body>
</html>
```

## Security

- Use **parameterized queries** (prepared statements) for any user input (GET/POST) that goes into SQL. Avoid building SQL by concatenating user input.

For adding **new data endpoints** (e.g. a PHP that returns JSON or HTML fragment for AJAX), see [Adding endpoints](02-adding-endpoints.md).
