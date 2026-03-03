<?php

/**
 * Auto-login page for Adminer
 * Automatically submits login form with PostgreSQL credentials
 */

// Check if we need to auto-submit the login form
if (!isset($_POST['auth']) && !isset($_GET['username'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Connecting to Database...</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background: #f5f5f5;
            }
            .loading {
                text-align: center;
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
        </style>
    </head>
    <body>
        <div class="loading">
            <h2>🔗 Connecting to PostgreSQL...</h2>
            <p>Please wait while we connect to your database.</p>
        </div>

        <form id="autoLogin" method="post" action="/adminer.php" style="display: none;">
            <input type="hidden" name="auth[driver]" value="pgsql">
            <input type="hidden" name="auth[server]" value="postgres">
            <input type="hidden" name="auth[username]" value="user">
            <input type="hidden" name="auth[password]" value="password">
            <input type="hidden" name="auth[db]" value="db">
        </form>

        <script>
            // Auto-submit the form after a short delay
            setTimeout(function() {
                document.getElementById('autoLogin').submit();
            }, 1000);
        </script>
    </body>
    </html>
    <?php
    exit();
}

// For all other cases, serve Adminer
include './adminer.php';
