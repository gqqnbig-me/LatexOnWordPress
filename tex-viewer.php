<?php
declare(strict_types=1);

$fileName = urldecode($_GET["file"]);
$texFile = realpath($fileName . '.tex');
$pdfFile = $fileName . '.pdf';
$imageFile = $fileName . '.png';

if ($texFile === false || strpos($texFile, dirname(__FILE__)) !== 0) {
    http_response_code(404);
    die("$fileName.tex is not in the allowed directory.");
}

$res = preg_match('/^[a-zA-Z0-9-_]+$/', $fileName);
if ($res === false || $res === 0)
    die("File name only allows [a-zA-Z0-9-_].");
?>

<!DOCTYPE html>
<html>
<body>

<div>
    <?php
    echo '<img src="';
    echo $fileName . '.png';
    echo '"/>';
    ?>
</div>
<div>
    <?php
    echo '<a href="';
    echo $pdfFile;
    echo '">Download PDF</a>';
    ?>
</div>


<?php

echo "<pre><code class='language-latex'>\n";
echo htmlentities(file_get_contents($texFile));
echo "\n</code></pre>";
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/vs.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/latex.min.js"></script>
<script>hljs.highlightAll();</script>
</body>
</html>