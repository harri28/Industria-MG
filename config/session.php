<?php

function startAppSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $sessionDir = dirname(__DIR__) . '/storage/sessions';

    if (!is_dir($sessionDir)) {
        mkdir($sessionDir, 0777, true);
    }

    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        ini_set('session.save_path', $sessionDir);
    }

    session_start();
}
