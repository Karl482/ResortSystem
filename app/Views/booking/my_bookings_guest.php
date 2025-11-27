<?php
$pageTitle = "My Bookings";
require_once __DIR__ . '/../partials/guest_header.php';
?>

<h1><?= htmlspecialchars($pageTitle) ?></h1>

<div class="alert alert-info" role="alert">
    Please <a href="?action=login">login</a> or <a href="?action=showRegisterForm">register</a> to view your confirmed bookings.
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Creation Date</th>
                <th>Resort</th>
                <th>Date & Time</th>
                <th>Facilities</th>
                <th>Total Price</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="7" class="text-center">No confirmed bookings to display.</td>
            </tr>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>