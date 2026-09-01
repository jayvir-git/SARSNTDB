# Adding new data endpoints

A **data endpoint** is a PHP script that is called by the front end (e.g. via AJAX) and returns **data** (JSON, HTML fragment, or plain text) rather than a full page. Examples in the app: **GenomeComparisonData.php**, **repeatData.php**.

## 1. Create the PHP file

- Create a new `.php` file (e.g. `MyDataEndpoint.php`).
- This script typically **does not** include Navigation or full HTML layout; it only outputs data (and the correct headers).

## 2. Set response headers

- For **JSON**:

```php
header('Content-Type: application/json; charset=utf-8');
```

- For **HTML fragment** (e.g. when the client injects the response into the page):

```php
header('Content-Type: text/html; charset=utf-8');
```

- If the endpoint is only called from your own front end and same origin, you may not need CORS headers. If it’s called from another domain, add appropriate `Access-Control-Allow-Origin` (and related) headers.

## 3. Connect to the database

```php
require_once './connection.php';
```

Use `$con` for all queries. If the endpoint lives in a subfolder, use the correct path to **connection.php** (e.g. `require_once __DIR__ . '/../connection.php';`).

## 4. Read parameters safely

- Read GET: `$param = isset($_GET['paramName']) ? $_GET['paramName'] : '';`
- Read POST: `$param = isset($_POST['paramName']) ? $_POST['paramName'] : '';`
- **Validate and sanitize** (e.g. allow only digits for a coordinate, or only A/T/G/C for a sequence). Use **parameterized queries** so that user input is never concatenated into SQL.

## 5. Run queries and build the response

- Run your queries with `$con->query()` or prepared statements.
- Build an array or object, then:
  - **JSON:** `echo json_encode($data);`
  - **HTML:** `echo $htmlFragment;`

## 6. Avoid output before the response

- Do not `include` files that output HTML (e.g. Navigation) unless the endpoint is intentionally returning HTML that includes that content. Do not print errors or warnings before the JSON/HTML if the client expects clean JSON.

## Example (JSON endpoint)

```php
<?php
header('Content-Type: application/json; charset=utf-8');
require_once './connection.php';

$gene = isset($_GET['gene']) ? trim($_GET['gene']) : '';
if ($gene === '') {
    echo json_encode(['error' => 'Missing gene']);
    exit;
}

$stmt = $con->prepare("SELECT Start, End, Gene FROM Gene_1 WHERE Gene = ?");
$stmt->bind_param("s", $gene);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

echo json_encode($row ?: ['error' => 'Not found']);
```

## Calling the endpoint from the front end

- From jQuery: `$.get('MyDataEndpoint.php', { gene: 'S Gene' }, function(data) { ... });`
- Or use `XMLHttpRequest` / `fetch`. The URL may be relative (e.g. `GenomeComparisonData.php?Protein=...`) when the page is on the same host/path.

For **new pages** (full HTML), see [Adding pages](01-adding-pages.md). For **conventions** (ProtienInfo, style.css, etc.), see [Conventions and typos](03-conventions-and-typos.md).
