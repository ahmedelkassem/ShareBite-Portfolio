<?php
require_once 'functions.php';

checkRole('Admin');
redirect('admin_dashboard.php');
