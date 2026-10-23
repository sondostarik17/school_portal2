
<?php
session_start();

require_once 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message
    ];
}

$errors = [];
$name = '';
$scoreValue = '';

/* =========================
   DELETE STUDENT
========================= */
if (isset($_GET['delete_id'])) {

    $id = filter_var(
        $_GET['delete_id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id === false) {
        flash('error', 'Invalid student ID.');
    } else {
        try {
            $stmt = $conn->prepare(
                'DELETE FROM students WHERE id = ?'
            );

            $stmt->bind_param('i', $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                flash('success', 'Student deleted successfully.');
            } else {
                flash('error', 'Student not found.');
            }

            $stmt->close();

        } catch (mysqli_sql_exception $exception) {
            error_log($exception->getMessage());
            flash('error', 'Could not delete the student.');
        }
    }

    header('Location: index.php');
    exit;
}

/* =========================
   ADD STUDENT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $scoreValue = trim($_POST['score'] ?? '');

    if ($name === '') {
        $errors[] = 'Student name is required.';
    } elseif (mb_strlen($name) > 255) {
        $errors[] = 'Student name must not exceed 255 characters.';
    }

    $score = filter_var(
        $scoreValue,
        FILTER_VALIDATE_INT
    );

    if (
        $score === false ||
        $score < 0 ||
        $score > 100
    ) {
        $errors[] = 'Score must be a whole number from 0 to 100.';
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare(
                'INSERT INTO students (name, score) VALUES (?, ?)'
            );

            $stmt->bind_param('si', $name, $score);
            $stmt->execute();
            $stmt->close();

            flash('success', 'Student added successfully.');

            header('Location: index.php');
            exit;

        } catch (mysqli_sql_exception $exception) {
            error_log($exception->getMessage());
            $errors[] = 'Could not add the student. Please try again.';
        }
    }
}

/* =========================
   FETCH STUDENTS
========================= */
$students = [];
$listError = false;

try {
    $result = $conn->query(
        'SELECT id, name, score FROM students ORDER BY id DESC'
    );

    while ($student = $result->fetch_assoc()) {
        $students[] = $student;
    }

    $result->free();

} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage());
    $listError = true;
}

/* =========================
   ALERT MESSAGES
========================= */
$alerts = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

foreach ($errors as $error) {
    $alerts[] = [
        'type' => 'error',
        'message' => $error
    ];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | WE Student Portal</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .page-background {
            background:
                radial-gradient(
                    circle at 10% 0%,
                    rgba(139, 92, 246, 0.10),
                    transparent 32%
                ),
                radial-gradient(
                    circle at 100% 100%,
                    rgba(196, 181, 253, 0.06),
                    transparent 30%
                );
        }

        .custom-input:focus {
            outline: none;
            border-color: #A78BFA;
            box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.15);
        }

        .student-row {
            transition: background-color 0.2s ease;
        }

        .student-row:hover {
            background-color: rgba(196, 181, 253, 0.04);
        }
    </style>
</head>

<body class="min-h-screen overflow-x-hidden bg-[#100D16] text-white">

    <?php require_once 'nav.php'; ?>

    <main class="page-background min-h-screen">

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">

            <!-- PAGE HEADER -->
            <header class="mb-8 flex items-center gap-4 sm:mb-10">

                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-purple-400/20 bg-purple-500/15 text-xl text-[#C4B5FD] sm:h-14 sm:w-14">
                    <i class="fa-solid fa-gauge-high"></i>
                </div>

                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl lg:text-4xl">
                        Admin Dashboard
                    </h1>

                    <p class="mt-2 text-sm leading-6 text-gray-400">
                        Manage students, scores, and academic results.
                    </p>
                </div>

            </header>

            <!-- ALERTS -->
            <?php foreach ($alerts as $alert): ?>

                <?php
                $alertClass = ($alert['type'] ?? '') === 'success'
                    ? 'border-emerald-400/20 bg-emerald-500/10 text-emerald-300'
                    : 'border-rose-400/20 bg-rose-500/10 text-rose-300';
                ?>

                <div class="mb-4 flex items-start justify-between gap-4 rounded-2xl border p-4 <?= $alertClass ?>"
                     role="status">

                    <p class="flex items-start gap-3 text-sm leading-6">
                        <i class="fa-solid fa-circle-info mt-1"></i>
                        <?= e($alert['message'] ?? '') ?>
                    </p>

                    <button type="button"
                            onclick="this.parentElement.remove()"
                            aria-label="Close message"
                            class="px-2 opacity-70 transition hover:opacity-100">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>

            <?php endforeach; ?>

            <!-- ADD STUDENT -->
            <section class="mb-8 overflow-hidden rounded-3xl border border-purple-900/50 bg-[#17121F]/90 shadow-xl shadow-purple-950/10">

                <div class="flex items-center gap-3 border-b border-purple-900/40 px-5 py-5 sm:px-7">

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-500/15 text-lg text-[#C4B5FD]">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>

                    <div>
                        <h2 class="text-lg font-bold sm:text-xl">
                            Add New Student
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            Enter the student details below.
                        </p>
                    </div>

                </div>

                <div class="p-5 sm:p-7">

                    <form action="index.php"
                          method="POST"
                          class="grid grid-cols-1 items-end gap-5 md:grid-cols-2 lg:grid-cols-[1.5fr_1fr_auto]">

                        <!-- NAME -->
                        <div>
                            <label for="name"
                                   class="mb-2 block text-sm font-semibold text-gray-300">
                                Student Name
                            </label>

                            <div class="flex items-center gap-3 rounded-xl border border-purple-900/50 bg-[#100D16] px-4">

                                <i class="fa-solid fa-user text-[#C4B5FD]"></i>

                                <input
                                    type="text"
                                    name="name"
                                    id="name"
                                    maxlength="255"
                                    required
                                    value="<?= e($name) ?>"
                                    placeholder="Enter student name"
                                    class="custom-input min-w-0 w-full rounded-xl bg-transparent py-3.5 text-sm text-white placeholder:text-gray-600"
                                >

                            </div>
                        </div>

                        <!-- SCORE -->
                        <div>
                            <label for="score"
                                   class="mb-2 block text-sm font-semibold text-gray-300">
                                Student Score
                            </label>

                            <div class="flex items-center gap-3 rounded-xl border border-purple-900/50 bg-[#100D16] px-4">

                                <i class="fa-solid fa-star text-[#C4B5FD]"></i>

                                <input
                                    type="number"
                                    name="score"
                                    id="score"
                                    min="0"
                                    max="100"
                                    step="1"
                                    required
                                    value="<?= e($scoreValue) ?>"
                                    placeholder="0 - 100"
                                    class="custom-input min-w-0 w-full rounded-xl bg-transparent py-3.5 text-sm text-white placeholder:text-gray-600"
                                >

                            </div>
                        </div>

                        <!-- SUBMIT -->
                        <button
                            type="submit"
                            class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-purple-400/20 bg-purple-600/20 px-6 py-3.5 text-sm font-bold text-[#DDD6FE] transition duration-300 hover:bg-purple-600/35 focus:outline-none focus:ring-2 focus:ring-purple-400/50"
                        >
                            <i class="fa-solid fa-plus"></i>
                            Add Student
                        </button>

                    </form>

                    <p class="mt-4 text-xs text-gray-500">
                        <i class="fa-solid fa-circle-info mr-1 text-[#C4B5FD]"></i>
                        The score must be a whole number between 0 and 100.
                    </p>

                </div>
            </section>

            <!-- STUDENT TABLE -->
            <section class="overflow-hidden rounded-3xl border border-purple-900/50 bg-[#17121F]/90 shadow-xl shadow-purple-950/10">

                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-purple-900/40 px-5 py-5 sm:px-7">

                    <div class="flex items-center gap-3">

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-500/15 text-lg text-[#C4B5FD]">
                            <i class="fa-solid fa-users"></i>
                        </div>

                        <div>
                            <h2 class="text-lg font-bold sm:text-xl">
                                Student Records
                            </h2>

                            <p class="mt-1 text-xs text-gray-500">
                                View and manage registered students.
                            </p>
                        </div>

                    </div>

                    <?php if (!$listError): ?>
                        <span class="rounded-full border border-purple-400/20 bg-purple-500/10 px-4 py-2 text-xs font-bold text-[#C4B5FD]">
                            <?= count($students) ?>
                            <?= count($students) === 1 ? 'Student' : 'Students' ?>
                        </span>
                    <?php endif; ?>

                </div>

                <?php if ($listError): ?>

                    <div class="m-5 rounded-2xl border border-rose-400/20 bg-rose-500/10 p-5 text-sm leading-6 text-rose-300">
                        <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                        Could not load student records. Check your database connection.
                    </div>

                <?php elseif (empty($students)): ?>

                    <div class="px-5 py-14 text-center sm:py-16">

                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-purple-400/20 bg-purple-500/10 text-2xl text-[#C4B5FD]">
                            <i class="fa-solid fa-folder-open"></i>
                        </div>

                        <h3 class="mt-5 text-lg font-bold">
                            No Students Yet
                        </h3>

                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">
                            Use the form above to add your first student.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="overflow-x-auto">

                        <table class="w-full min-w-[650px] text-left text-sm">

                            <thead class="bg-[#100D16] text-xs uppercase tracking-wider text-gray-400">

                                <tr>
                                    <th scope="col" class="px-5 py-4 sm:px-7">
                                        ID
                                    </th>

                                    <th scope="col" class="px-5 py-4">
                                        Student Name
                                    </th>

                                    <th scope="col" class="px-5 py-4">
                                        Score
                                    </th>

                                    <th scope="col" class="px-5 py-4">
                                        Status
                                    </th>

                                    <th scope="col" class="px-5 py-4 sm:px-7">
                                        Action
                                    </th>
                                </tr>

                            </thead>

                            <tbody class="divide-y divide-purple-900/30">

                                <?php foreach ($students as $student): ?>

                                    <?php
                                    $passed = (int) $student['score'] >= 50;
                                    ?>

                                    <tr class="student-row">

                                        <!-- ID -->
                                        <td class="px-5 py-5 font-semibold text-gray-400 sm:px-7">
                                            #<?= e($student['id']) ?>
                                        </td>

                                        <!-- NAME -->
                                        <td class="px-5 py-5">

                                            <div class="flex items-center gap-3">

                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 font-bold text-[#C4B5FD]">
                                                    <?= e(mb_strtoupper(mb_substr($student['name'], 0, 1))) ?>
                                                </div>

                                                <span class="font-semibold text-gray-200">
                                                    <?= e($student['name']) ?>
                                                </span>

                                            </div>

                                        </td>

                                        <!-- SCORE -->
                                        <td class="px-5 py-5">

                                            <span class="font-bold <?= $passed ? 'text-emerald-300' : 'text-rose-300' ?>">
                                                <?= e($student['score']) ?>
                                            </span>

                                            <span class="text-gray-600">/ 100</span>

                                        </td>

                                        <!-- STATUS -->
                                        <td class="px-5 py-5">

                                            <?php if ($passed): ?>

                                                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-300">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    Pass
                                                </span>

                                            <?php else: ?>

                                                <span class="inline-flex items-center gap-2 rounded-full border border-rose-400/20 bg-rose-500/10 px-3 py-1.5 text-xs font-bold text-rose-300">
                                                    <i class="fa-solid fa-circle-xmark"></i>
                                                    Fail
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <!-- DELETE -->
                                        <td class="px-5 py-5 sm:px-7">

                                            <button
                                                type="button"
                                                onclick="confirmDelete(<?= (int) $student['id'] ?>)"
                                                class="inline-flex items-center gap-2 rounded-xl border border-rose-400/20 bg-rose-500/10 px-3 py-2 text-xs font-bold text-rose-300 transition hover:bg-rose-500/20 focus:outline-none focus:ring-2 focus:ring-rose-400/40"
                                            >
                                                <i class="fa-solid fa-trash"></i>
                                                Delete
                                            </button>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                    <!-- TABLE FOOTER -->
                    <div class="border-t border-purple-900/40 px-5 py-4 sm:px-7">

                        <p class="text-xs leading-5 text-gray-500">
                            <i class="fa-solid fa-shield-halved mr-1 text-[#C4B5FD]"></i>
                            Student records are loaded from your database.
                        </p>

                    </div>

                <?php endif; ?>

            </section>

            <!-- FOOTER -->
            <footer class="py-8 text-center text-xs text-gray-600">
                WE Student Portal &copy; <?= date('Y') ?>. All rights reserved.
            </footer>

        </div>

    </main>

    <!-- DELETE CONFIRMATION -->
    <script>
        function confirmDelete(id) {
            const confirmed = window.confirm(
                'Are you sure you want to delete this student?'
            );

            if (confirmed) {
                window.location.href =
                    'index.php?delete_id=' + encodeURIComponent(id);
            }
        }
    </script>

</body>
</html>
