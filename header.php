<?php
// סיכום החלק:
// הקובץ מכין את ראש הדף ואת תפריט הניווט לפי מצב המשתמש והתפקיד שלו.
// הוא מציג קישורים מתאימים לאורח, תורם, מתנדב, Charity או Admin.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'functions.php';

$userName = $_SESSION['full_name'] ?? '';
$userType = $_SESSION['role'] ?? 'Guest';

$roleLabel = $userType === 'Donor'
    ? 'Restaurant'
    : ($userType === 'Charity' ? 'Charity Center' : $userType);

$currentPage = basename($_SERVER['PHP_SELF']);
$adminMessagesCount = 0;

if ($userType === 'Admin') {
    $adminMessagesCount = getPendingAdminMessageCount();
}

// הוספת class בשם active לקישור של הדף הנוכחי.
if (!function_exists('activeLink')) {
    function activeLink($page)
    {
        global $currentPage;

        return $currentPage === $page ? 'active' : '';
    }
}

// הצגת קישור בתפריט הניווט.
if (!function_exists('navItem')) {
    function navItem($page, $title)
    {
        echo '<li class="nav-item">';
        echo '<a class="nav-link ' . activeLink($page) . '" href="' . $page . '">';
        echo $title;
        echo '</a>';
        echo '</li>';
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ShareBite</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link rel="stylesheet" href="style.css?v=1">
</head>

<body>

<nav class="navbar navbar-expand-lg site-navbar sticky-top">

    <div class="container">

        <a class="navbar-brand" href="index.php">

            <span class="brand-icon">
                <i class="fa-solid fa-utensils"></i>
            </span>

            <span class="brand-text">
                ShareBite
            </span>

            <?php if (isLoggedIn()): ?>

                <span class="role-badge">
                    <?= escapeHtml($roleLabel) ?>
                </span>

            <?php endif; ?>

        </a>


        <button
            class="navbar-toggler custom-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
        >
            <span class="navbar-toggler-icon"></span>
        </button>


        <div
            class="collapse navbar-collapse"
            id="mainNavbar"
        >

            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <?php

                if (!isLoggedIn()) {

                    navItem('index.php', 'Home');
                    navItem('contact.php', 'Contact Us');

                } elseif ($userType === 'Donor') {

                    navItem('index.php', 'Home');
                    navItem('offers.php', 'All Offers');
                    navItem('donor_dashboard.php', 'My Donations');
                    navItem('map.php', 'Map');
                    navItem('history.php', 'History');
                    navItem('contact.php', 'Contact Us');

                } elseif ($userType === 'Volunteer') {

                    navItem('index.php', 'Home');
                    navItem('offers.php', 'All Offers');
                    navItem('search_offers.php', 'Search');
                    navItem('map.php', 'Map');
                    navItem('history.php', 'My Reservations');
                    navItem('saved_offers.php', 'Favorites');
                    navItem('contact.php', 'Contact Us');

                } elseif ($userType === 'Charity') {

                    navItem('index.php', 'Home');
                    navItem('charity_dashboard.php', 'Charity Dashboard');
                    navItem('map.php', 'Map');
                    navItem('contact.php', 'Contact Us');

                } elseif ($userType === 'Admin') {

                    navItem('admin_dashboard.php', 'Admin Panel');
                    navItem('manage_users.php', 'Manage Users');
                    navItem('admin_charities.php', 'Charities');
                    navItem('offers.php', 'Offers');
                    navItem('map.php', 'Map');
                    navItem('admin_suggestions.php', 'Suggestions');

                    echo '<li class="nav-item">';

                    echo '<a class="nav-link ' .
                        activeLink('admin_messages.php') .
                        '" href="admin_messages.php">';

                    echo 'Messages';

                    if ($adminMessagesCount > 0) {
                        echo ' <span class="message-badge">' .
                            (int)$adminMessagesCount .
                            '</span>';
                    }

                    echo '</a>';
                    echo '</li>';
                }

                ?>

            </ul>


            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">

                <?php if (!isLoggedIn()): ?>

                    <?php navItem('login.php', 'Login'); ?>

                    <li class="nav-item">

                        <a
                            class="nav-btn ms-lg-2"
                            href="register.php"
                        >
                            Create Account
                        </a>

                    </li>

                <?php else: ?>


                    <?php if ($userType === 'Donor'): ?>

                        <li class="nav-item">

                            <a
                                class="nav-btn add-btn me-lg-2"
                                href="add_offer.php"
                            >
                                <i class="fa-solid fa-plus"></i>
                                Add Donation
                            </a>

                        </li>

                    <?php endif; ?>


                    <?php if ($userType !== 'Admin'): ?>

                        <li class="nav-item">

                            <a
                                class="notification-link"
                                href="notifications.php"
                            >
                                <i class="fa-solid fa-bell"></i>
                            </a>

                        </li>

                    <?php endif; ?>


                    <li class="nav-item dropdown">

                        <a
                            class="nav-link dropdown-toggle user-menu"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                        >

                            <i class="fa-solid fa-user-circle"></i>

                            <?= escapeHtml($userName) ?>

                        </a>


                        <ul class="dropdown-menu dropdown-menu-end custom-dropdown">

                            <li>

                                <a
                                    class="dropdown-item"
                                    href="profile.php"
                                >
                                    <i class="fa-solid fa-user"></i>
                                    My Profile
                                </a>

                            </li>


                            <?php if ($userType !== 'Admin'): ?>

                                <li>

                                    <a
                                        class="dropdown-item"
                                        href="suggestions.php"
                                    >
                                        <i class="fa-solid fa-lightbulb"></i>
                                        Suggestions
                                    </a>

                                </li>


                                <li>

                                    <a
                                        class="dropdown-item"
                                        href="report_problem.php"
                                    >
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        Report a Problem
                                    </a>

                                </li>

                            <?php endif; ?>


                            <li>
                                <hr class="dropdown-divider">
                            </li>


                            <li>

                                <a
                                    class="dropdown-item text-danger"
                                    href="logout.php"
                                >
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                    Logout
                                </a>

                            </li>

                        </ul>

                    </li>

                <?php endif; ?>

            </ul>

        </div>

    </div>

</nav>

<main class="container mt-5">
