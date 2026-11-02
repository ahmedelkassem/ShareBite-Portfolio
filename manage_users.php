<?php
// הדף מאפשר ל-Admin לראות משתמשים ולבצע פעולות ניהול.

require_once 'functions.php';

checkRole('Admin');

$users = dbFetchAll("
    SELECT *
    FROM users
    ORDER BY user_id DESC
");

include 'header.php';
?>

<div class="container mt-5">

    <h2 class="mb-4">
        User Management
    </h2>

    <div class="table-responsive shadow-sm rounded bg-white p-3">

        <table class="table table-hover mb-0">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($users as $user): ?>

                    <tr>

                        <td>
                            <?= (int)$user['user_id'] ?>
                        </td>

                        <td>
                            <?= escapeHtml($user['full_name']) ?>
                        </td>

                        <td>
                            <?= escapeHtml($user['email']) ?>
                        </td>

                        <td>
                            <?= escapeHtml($user['role']) ?>
                        </td>

                        <td>

                            <?php if (
                                (int)$user['user_id'] !=
                                (int)$_SESSION['user_id']
                            ): ?>

                                <?php if ($user['role'] != 'Admin'): ?>

                                    <form
                                        method="post"
                                        action="update_role.php"
                                        class="d-inline-block me-1"
                                    >
                                        <?= csrfInputField() ?>

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$user['user_id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="return"
                                            value="manage_users.php"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-warning"
                                        >
                                            Make Admin
                                        </button>
                                    </form>

                                <?php else: ?>

                                    <span class="badge bg-primary me-1">
                                        Admin
                                    </span>

                                <?php endif; ?>


                                <form
                                    method="post"
                                    action="delete_user.php"
                                    class="d-inline-block"
                                    onsubmit="return confirm('Delete this user?');"
                                >
                                    <?= csrfInputField() ?>

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$user['user_id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-danger"
                                    >
                                        Delete
                                    </button>
                                </form>


                            <?php else: ?>

                                <span class="text-muted">
                                    Current user
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>
س
    </div>

</div>

<?php include 'footer.php'; ?>