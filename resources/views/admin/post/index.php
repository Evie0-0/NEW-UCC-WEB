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
        <form id="form" method="get">
            <label for="query">Search</label>
            <input type="search" id="query" name="q">

            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                <option value="published">Published</option>
                <option value="draft">Draft</option>
                <option value="scheduled">Scheduled</option>
                <option value="expired">Expired</option>
            </select>

            <label for="sort">Sort by</label>
            <select id="sort" name="sort">
                <option value="created_desc">Created (newest first)</option>
                <option value="created_asc">Created (oldest first)</option>
                <option value="title_asc">Title (A-Z)</option>
                <option value="title_desc">Title (Z-A)</option>
                <option value="published_desc">Published (newst first)</option>
                <option value="published_asc">Published (oldest first)</option>
                <option value="expires_asc">Expiration (earlier first)</option>
                <option value="expires_desc">Expiration (latest first)</option>
            </select>

            <label for="from">From</label>
            <input type="date" id="from" name="from">

            <label for="to">To</label>
            <input type="date" id="to" name="to">

            <button type="submit" name="search_btn">Search</button>
        </form>

        <div id="results"></div>
    </main>
</body>
</html>
