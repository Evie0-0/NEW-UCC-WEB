<?php
declare(strict_types=1);
?>

<!DOCTYPE html>
<html lang="en">
    <?= $this->partial('admin', 'head', $data) ?>
<body>
    <?= $this->partial('admin', 'header', $data) ?>
    <?= $this->partial('admin', 'sidebar', $data) ?>

    <main>
        <p>This is the Dashboard</p>
    </main>
</body>
</html>
