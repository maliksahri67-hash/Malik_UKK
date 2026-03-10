<?php
define('BASE_URL', '..');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
requireLoginMulti(['Admin','Petugas']);
$pageTitle = 'Monitoring Denda'; $activeNav = 'denda'; $role = getRole();
include __DIR__ . '/../includes/layout.php';
include __DIR__ . '/../includes/denda_view.php';
include __DIR__ . '/../includes/layout_footer.php';
