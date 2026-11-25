<?php
declare(strict_types=1);
?>

<!DOCTYPE html>
<html lang="en">
<?= $this->partial('admin', 'head', $data) ?>
<body>
    <main>
        <h1>Sign In</h1>

        <form id="form" method="post">
            <input type="hidden" name="csrf_token" value="<?= $this->helper->escape($data['csrf_token']) ?>">

            <div>
                <label for="username">Username</label>
                <input id="username" name="username" type="text" autocomplete="username" required>
            </div>

            <div>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>

            <button type="submit" name="login_btn">Login</button>
        </form>

        <p id="message" role="status"></p>
    </main>
</body>
</html>
