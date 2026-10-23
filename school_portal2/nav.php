<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<script src="https://cdn.tailwindcss.com"></script>

<nav class="sticky top-0 z-50 border-b border-purple-900/50 bg-[#100D16]/95 backdrop-blur-xl">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-6">

        <!-- Logo -->
        <a href="index.php" class="group flex items-center gap-3">
            <span class="text-base font-extrabold tracking-wide text-white sm:text-lg">
                WE <span class="text-[#C4B5FD]">Student Portal</span>
            </span>
        </a>

        <!-- Navigation Links -->
        <div class="flex flex-wrap items-center gap-2">

            <a href="student.php" class="rounded-xl px-3 py-2 text-sm font-bold transition duration-300
               <?= $currentPage === 'student.php'
                   ? 'border border-purple-400/20 bg-purple-600/20 text-[#D8B4FE] shadow-md shadow-purple-950/20'
                   : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>">
                Student Check
            </a>

            <a href="index.php" class="rounded-xl px-3 py-2 text-sm font-bold transition duration-300
               <?= $currentPage === 'index.php'
                   ? 'border border-purple-400/20 bg-purple-600/20 text-[#D8B4FE] shadow-md shadow-purple-950/20'
                   : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>">
                Dashboard (Admin)
            </a>

        </div>

    </div>
</nav>

