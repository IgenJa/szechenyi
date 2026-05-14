<?php
require_once __DIR__ . '/auth.php';
sz_logout();
header('Location: login.php');
exit;
