<?php
require_once __DIR__ . '/includes/functions.php';

logout_user();
session_start();
flash('success', 'You have been logged out.');
redirect('login.php');
