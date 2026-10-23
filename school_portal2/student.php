<?php
require_once 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$searchName = '';
$students = [];
$searched = false;
$errorMessage = '';

/* =========================
   SEARCH STUDENTS BY NAME
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $searchName = trim($_POST['search_name'] ?? '');
    $searched = true;

    if ($searchName === '') {
        $errorMessage = 'Please enter a student name.';
    } else {
        try {
            $searchTerm = '%' . $searchName . '%';

            $stmt = $conn->prepare(
                'SELECT id, name, score
                 FROM students
                 WHERE name LIKE ?
                 ORDER BY id DESC'
            );

            $stmt->bind_param('s', $searchTerm);
            $stmt->execute();

            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }

            $stmt->close();

        } catch (mysqli_sql_exception $exception) {
            error_log($exception->getMessage());
            $errorMessage = 'Something went wrong. Please try again.';
        }
    }
}

/* =========================
   CLOSE DATABASE
========================= */
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Check | WE Student Portal</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .page-background {
            background:
                radial-gradient(circle at 10% 0%,
                    rgba(139, 92, 246, .10),
                    transparent 32%),
                radial-gradient(circle at 95% 100%,
                    rgba(196, 181, 253, .06),
                    transparent 30%);
        }

        .custom-input:focus {
            outline: none;
            border-color: #A78BFA;
            box-shadow: 0 0 0 3px rgba(167, 139, 250, .15);
        }

        .student-card {
            transition: border-color .2s ease,
                transform .2s ease;
        }

        .student-card:hover {
            border-color: rgba(196, 181, 253, .35);
            transform: translateY(-2px);
        }
    </style>
</head>

<body class="min-h-screen overflow-x-hidden bg-[#100D16] text-white">

    <!-- NAVBAR -->
    <?php require_once 'nav.php'; ?>

    <main class="page-background min-h-screen">

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">

            <!-- PAGE HEADER -->
            <header class="mb-8 text-center sm:mb-10">

                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-purple-400/20 bg-purple-500/15 text-2xl text-[#C4B5FD]">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>

                <h1 class="mt-5 text-2xl font-extrabold tracking-tight sm:text-3xl lg:text-4xl">
                    Student Check
                </h1>

                <p class="mx-auto mt-3 max-w-lg text-sm leading-6 text-gray-400 sm:text-base">
                    Search for a student by name to view their score and academic status.
                </p>

            </header>

            <!-- SEARCH CARD -->
            <section
                class="mx-auto max-w-2xl overflow-hidden rounded-3xl border border-purple-900/50 bg-[#17121F]/90 shadow-xl shadow-purple-950/10">

                <div class="flex items-center gap-3 border-b border-purple-900/40 px-5 py-5 sm:px-7">

                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-purple-500/15 text-lg text-[#C4B5FD]">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>

                    <div>
                        <h2 class="text-lg font-bold sm:text-xl">
                            Find Your Results
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            Enter the student's name below.
                        </p>
                    </div>

                </div>

                <div class="p-5 sm:p-7">

                    <form action="student.php" method="POST" class="space-y-4">

                        <label for="search_name" class="block text-sm font-semibold text-gray-300">
                            Student Name
                        </label>

                        <div class="flex items-center gap-3 rounded-xl border border-purple-900/50 bg-[#100D16] px-4">

                            <i class="fa-solid fa-user text-[#C4B5FD]"></i>

                            <input type="text" id="search_name" name="search_name" value="<?= e($searchName) ?>"
                                placeholder="Enter Student Name" maxlength="255" required
                                class="custom-input min-w-0 w-full rounded-xl bg-transparent py-4 text-sm text-white placeholder:text-gray-600">

                        </div>

                        <button type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-purple-400/20 bg-purple-600/20 px-6 py-4 text-sm font-bold text-[#DDD6FE] transition duration-300 hover:bg-purple-600/35 focus:outline-none focus:ring-2 focus:ring-purple-400/50">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            Search by Name
                        </button>

                    </form>

                    <?php if ($errorMessage !== ''): ?>

                        <div class="mt-5 flex items-start gap-3 rounded-xl border border-rose-400/20 bg-rose-500/10 p-4 text-sm leading-6 text-rose-300"
                            role="alert">

                            <i class="fa-solid fa-circle-exclamation mt-1"></i>

                            <p><?= e($errorMessage) ?></p>

                        </div>

                    <?php endif; ?>

                </div>
            </section>

            <!-- SEARCH RESULTS -->
            <?php if ($searched && $errorMessage === ''): ?>

                <section class="mt-8">

                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">

                        <div>
                            <h2 class="text-xl font-bold sm:text-2xl">
                                Search Results
                            </h2>

                            <p class="mt-2 text-sm text-gray-400">
                                Results for:
                                <span class="font-semibold text-[#C4B5FD]">
                                    <?= e($searchName) ?>
                                </span>
                            </p>
                        </div>

                        <span
                            class="rounded-full border border-purple-400/20 bg-purple-500/10 px-4 py-2 text-xs font-bold text-[#C4B5FD]">
                            <?= count($students) ?>
                            <?= count($students) === 1 ? 'Student Found' : 'Students Found' ?>
                        </span>

                    </div>

                    <?php if (empty($students)): ?>

                        <!-- NO RESULTS -->
                        <div class="rounded-3xl border border-purple-900/50 bg-[#17121F]/90 px-5 py-12 text-center sm:py-14">

                            <div
                                class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-purple-400/20 bg-purple-500/10 text-2xl text-[#C4B5FD]">
                                <i class="fa-solid fa-user-slash"></i>
                            </div>

                            <h3 class="mt-5 text-lg font-bold">
                                No Student Found
                            </h3>

                            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">
                                We couldn't find a student with that name. Check the spelling and try again.
                            </p>

                        </div>

                    <?php else: ?>

                        <!-- STUDENT CARDS -->
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                            <?php foreach ($students as $student): ?>

                                <?php
                                $score = (int) $student['score'];
                                $passed = $score >= 50;
                                ?>

                                <article
                                    class="student-card overflow-hidden rounded-3xl border border-purple-900/50 bg-[#17121F]/90 shadow-lg shadow-purple-950/10">

                                    <!-- CARD HEADER -->
                                    <div class="flex items-center gap-4 border-b border-purple-900/40 p-5 sm:p-6">

                                        <div
                                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-purple-400/20 bg-purple-500/10 text-xl font-extrabold text-[#C4B5FD]">
                                            <?= e(mb_strtoupper(mb_substr($student['name'], 0, 1))) ?>
                                        </div>

                                        <div class="min-w-0 flex-1">

                                            <h3 class="break-words text-lg font-bold text-white">
                                                <?= e($student['name']) ?>
                                            </h3>

                                            <p class="mt-1 text-xs text-gray-500">
                                                Student ID: #<?= e($student['id']) ?>
                                            </p>

                                        </div>

                                    </div>

                                    <!-- CARD BODY -->
                                    <div class="space-y-5 p-5 sm:p-6">

                                        <div class="flex items-center justify-between gap-4">

                                            <span class="text-sm text-gray-400">
                                                Final Score
                                            </span>

                                            <span
                                                class="text-2xl font-extrabold <?= $passed ? 'text-emerald-300' : 'text-rose-300' ?>">
                                                <?= $score ?>
                                                <span class="text-sm font-medium text-gray-500">
                                                    / 100
                                                </span>
                                            </span>

                                        </div>

                                        <!-- SCORE BAR -->
                                        <div class="h-2 overflow-hidden rounded-full bg-white/5">

                                            <div class="h-full rounded-full <?= $passed ? 'bg-emerald-400' : 'bg-rose-400' ?>"
                                                style="width: <?= $score ?>%;"></div>

                                        </div>

                                        <!-- STATUS -->
                                        <div class="flex items-center justify-between gap-3 border-t border-purple-900/40 pt-5">

                                            <span class="text-sm text-gray-400">
                                                Academic Status
                                            </span>

                                            <?php if ($passed): ?>

                                                <span
                                                    class="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-500/10 px-3 py-2 text-xs font-bold text-emerald-300">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    Pass
                                                </span>

                                            <?php else: ?>

                                                <span
                                                    class="inline-flex items-center gap-2 rounded-full border border-rose-400/20 bg-rose-500/10 px-3 py-2 text-xs font-bold text-rose-300">
                                                    <i class="fa-solid fa-circle-xmark"></i>
                                                    Fail
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </section>

            <?php else: ?>

                <!-- INITIAL INFORMATION -->
                <?php if ($errorMessage === ''): ?>

                    <div
                        class="mx-auto mt-8 max-w-2xl rounded-2xl border border-purple-900/40 bg-[#17121F]/60 p-5 text-center sm:p-6">

                        <i class="fa-solid fa-lightbulb text-lg text-[#C4B5FD]"></i>

                        <p class="mt-3 text-sm leading-6 text-gray-400">
                            You can enter a full name or part of a name to find matching student records.
                        </p>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

            <!-- FOOTER -->
            <footer class="py-10 text-center text-xs text-gray-600">
                WE Student Portal &copy; <?= date('Y') ?>. All rights reserved.
            </footer>

        </div>

    </main>

</body>

</html>