<?php
$pageTitle = 'Pending Reschedule Requests';
require_once __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3><i class="fas fa-exchange-alt"></i> Pending Reschedule Requests (<?= count($requests ?? []) ?>)</h3>
                    <div class="d-flex gap-2">
                        <a href="?controller=admin&action=unifiedBookingManagement<?php echo isset($_GET['resort_id']) ? '&resort_id=' . urlencode($_GET['resort_id']) : ''; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Bookings
                        </a>
                    </div>
                </div>

                <div class="card-body border-bottom">
                    <form id="rescheduleFilterForm" method="GET" class="row g-3 align-items-end">
                        <input type="hidden" name="controller" value="admin">
                        <input type="hidden" name="action" value="rescheduleRequests">

                        <div class="col-lg-6 col-md-6">
                            <label class="form-label">Filter by Resort</label>
                            <select name="resort_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All Resorts</option>
                                <?php foreach ($resorts as $resort): ?>
                                    <option value="<?= $resort->resortId ?>" <?= (isset($_GET['resort_id']) && $_GET['resort_id'] == $resort->resortId) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($resort->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-6 col-md-6">
                            <a href="?controller=admin&action=rescheduleRequests" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times"></i> Clear Filter
                            </a>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <?php if (empty($requests)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No pending reschedule requests</h4>
                            <p class="text-muted">There are currently no pending reschedule requests.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="reschedule-requests-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Booking</th>
                                        <th>Requested</th>
                                        <th>Current</th>
                                        <th>Requested By</th>
                                        <th>Reason</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $r): ?>
                                        <tr data-request-id="<?= htmlspecialchars($r->RequestID) ?>">
                                            <td><strong><?= htmlspecialchars($r->RequestID) ?></strong></td>
                                            <td><a href="?controller=admin&action=viewBooking&booking_id=<?= htmlspecialchars($r->BookingID) ?>">#<?= htmlspecialchars($r->BookingID) ?></a></td>
                                            <td>
                                                <div><?= htmlspecialchars($r->RequestedDate) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($r->RequestedTimeSlot) ?></small>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($r->CurrentDate) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($r->CurrentTimeSlot) ?></small>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($r->RequestedByName ?? $r->RequestedBy) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($r->RequestedByRole ?? 'Unknown') ?></small>
                                            </td>
                                            <td style="max-width:220px; white-space:normal;"><?= nl2br(htmlspecialchars($r->Reason ?? '')) ?></td>
                                            <td><?= htmlspecialchars($r->CreatedAt) ?></td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <div class="btn-group">
                                                        <button class="btn btn-sm btn-success btn-approve"> <i class="fas fa-check"></i> Approve</button>
                                                        <button class="btn btn-sm btn-danger btn-reject"> <i class="fas fa-times"></i> Reject</button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($pagination['total_pages'] > 1): ?>
                            <div class="d-flex justify-content-between align-items-center mt-3 px-3 pb-3">
                                <div class="text-muted">
                                    Showing <?= count($requests) ?> of <?= $pagination['total_items'] ?> reschedule requests
                                    (Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>)
                                </div>
                                <nav aria-label="Reschedule requests pagination">
                                    <ul class="pagination mb-0">
                                        <!-- First Page -->
                                        <?php if ($pagination['current_page'] > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= buildPaginationUrl(1) ?>" aria-label="First">
                                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                                </a>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= buildPaginationUrl($pagination['current_page'] - 1) ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">&laquo;&laquo;</span>
                                            </li>
                                            <li class="page-item disabled">
                                                <span class="page-link">&laquo;</span>
                                            </li>
                                        <?php endif; ?>

                                        <!-- Page Numbers -->
                                        <?php
                                        $startPage = max(1, $pagination['current_page'] - 2);
                                        $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++):
                                        ?>
                                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= buildPaginationUrl($i) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <!-- Next/Last Page -->
                                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= buildPaginationUrl($pagination['current_page'] + 1) ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= buildPaginationUrl($pagination['total_pages']) ?>" aria-label="Last">
                                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">&raquo;</span>
                                            </li>
                                            <li class="page-item disabled">
                                                <span class="page-link">&raquo;&raquo;</span>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>

                            <!-- Per Page Selector -->
                            <div class="px-3 pb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <label for="perPageSelect" class="mb-0 text-muted small">Items per page:</label>
                                    <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                                        <option value="10" <?= $pagination['per_page'] == 10 ? 'selected' : '' ?>>10</option>
                                        <option value="20" <?= $pagination['per_page'] == 20 ? 'selected' : '' ?>>20</option>
                                        <option value="50" <?= $pagination['per_page'] == 50 ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= $pagination['per_page'] == 100 ? 'selected' : '' ?>>100</option>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to build pagination URLs preserving filters
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}
?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<script>
function changePerPage(perPage) {
    const params = new URLSearchParams(window.location.search);
    params.set('per_page', perPage);
    params.set('page', 1); // Reset to first page when changing per page
    window.location.search = params.toString();
}
</script>

<script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Client-side simple search filtering (booking ID or customer name)
    const searchInput = document.getElementById('rescheduleSearchInput');
    const tableBody = document.querySelector('#reschedule-requests-table tbody');

    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();
            const rows = tableBody.querySelectorAll('tr');
            rows.forEach(row => {
                const text = row.textContent.trim().toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }

    function postAction(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data)
        }).then(r => r.json());
    }

    const table = document.getElementById('reschedule-requests-table');
    if (!table) return;

    table.addEventListener('click', function (ev) {
        const approveBtn = ev.target.closest('.btn-approve');
        const rejectBtn = ev.target.closest('.btn-reject');
        if (!approveBtn && !rejectBtn) return;

        const row = ev.target.closest('tr');
        const requestId = row.getAttribute('data-request-id');
        if (!requestId) return;

        if (approveBtn) {
            if (!confirm('Approve this reschedule request?')) return;
            approveBtn.disabled = true;
            postAction('?controller=admin&action=approveRescheduleRequest', { request_id: requestId })
                .then(res => {
                    if (res.success) {
                        row.remove();
                    } else {
                        alert(res.error || 'Approval failed');
                        approveBtn.disabled = false;
                    }
                })
                .catch(err => { alert('Network error'); approveBtn.disabled = false; });
        }

        if (rejectBtn) {
            const notes = prompt('Optional rejection notes (visible to user):');
            if (notes === null) return; // cancelled
            rejectBtn.disabled = true;
            postAction('?controller=admin&action=rejectRescheduleRequest', { request_id: requestId, review_notes: notes })
                .then(res => {
                    if (res.success) {
                        row.remove();
                    } else {
                        alert(res.error || 'Rejection failed');
                        rejectBtn.disabled = false;
                    }
                })
                .catch(err => { alert('Network error'); rejectBtn.disabled = false; });
        }
    });
});
</script>
