<?php
require_once __DIR__ . '/config.php';

// Verwijder alle sessievariabelen, vernietig de sessie en stuur terug naar de homepagina
session_unset();
session_destroy();
redirect('/index.php');
