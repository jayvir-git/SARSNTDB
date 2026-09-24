<?php
/**
 * Accession list behind a combined group whose project cell is "many #s".
 */
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/nj_read_helpers.php';

$code = isset($_GET['group']) ? trim((string) $_GET['group']) : '';
$file = nj_read_many_project_file($code);
$titles = [
    'Thailand_mix' => 'Thailand many #s',
    'Est-mix' => 'Estonia many #s',
];
$title = isset($titles[$code]) ? $titles[$code] : 'Project accessions';
$ids = [];
if ($file !== null) {
    $path = __DIR__ . '/data/vcf_group_projects/' . $file;
    if (is_file($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $ids[] = $line;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body>
    <?php include __DIR__ . '/Navigation.php'; ?>
    <div class="container" style="margin-top:16px;">
        <h4><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h4>
        <?php if ($file === null) : ?>
            <p>This group uses a single project accession.</p>
        <?php elseif (!$ids) : ?>
            <p>The accession list is not on this server.</p>
        <?php else : ?>
            <p><?php echo count($ids); ?> projects from the same sampling facility and instrument, kept as one group.</p>
            <ul>
                <?php foreach ($ids as $id) : ?>
                    <li><?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <p><a href="MutationsSearch.php">Back to Mutations</a></p>
    </div>
</body>
</html>
