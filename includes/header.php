<?php
// functions.php is already included by auth_check.php, but it's safe to include again
// if this header is ever used on a public page. The 'require_once' prevents re-inclusion.
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- The title will be set on each individual page -->
    <title><?= isset($page_title) ? escape($page_title) : 'Security Awareness Platform' ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { 'primary': '#0052cc', 'primary-dark': '#0041a3', 'secondary': '#f4f5f7', 'accent': '#ffab00' }
                }
            }
        }
    </script>
</head>
<body class="h-full">
<div class="min-h-full">
    <nav class="bg-primary">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <!-- Logo Icon -->
                        <svg class="h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <a href="dashboard.php" class="text-white hover:bg-primary-dark rounded-md px-3 py-2 text-sm font-medium">Dashboard</a>
                            <a href="my_certificates.php" class="text-gray-300 hover:bg-primary-dark hover:text-white rounded-md px-3 py-2 text-sm font-medium">My Certificates</a>
                            <a href="materials.php" class="text-gray-300 hover:bg-primary-dark hover:text-white rounded-md px-3 py-2 text-sm font-medium">Materials</a>
                        </div>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6">
                        <div class="relative ml-3">
                            <div>
                                <a href="profile.php" class="flex max-w-xs items-center rounded-full bg-primary text-sm text-white p-2 hover:bg-primary-dark">
                                    <span class="mr-2">Hi, <?= escape($_SESSION['user_first_name']) ?></span>
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                         <a href="api/auth/logout.php" class="ml-4 rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <header class="bg-white shadow">
        <div class="mx-auto max-w-7xl py-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900"><?= isset($page_title) ? escape($page_title) : 'Dashboard' ?></h1>
        </div>
    </header>
    <main>
        <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
            <!-- Page content will go here -->
