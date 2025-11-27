<?php
// Prevent direct access to this file
if (!defined('APP_LOADED')) {
    http_response_code(403);
    require_once __DIR__ . '/../../errors/403.php';
    exit;
}
?>
<div class="container-fluid">
    <h4>Schedule for <?= htmlspecialchars($facility->name) ?></h4>
    <h4>Block a Time Slot</h4>
    <form action="?controller=admin&action=blockTime" method="POST" class="row g-3">
        <input type="hidden" name="facilityId" value="<?= $facility->facilityId ?>">
        <div class="col-md-3">
            <label for="blockDate" class="form-label">Start Date</label>
            <input type="date" class="form-control" id="blockDate" name="blockDate" required>
        </div>
        <div class="col-md-3">
            <label for="blockEndDate" class="form-label">End Date (optional)</label>
            <input type="date" class="form-control" id="blockEndDate" name="blockEndDate">
        </div>
        <div class="col-md-2">
            <label for="startTime" class="form-label">Start Time</label>
            <input type="time" class="form-control" id="startTime" name="startTime" required>
        </div>
        <div class="col-md-2">
            <label for="endTime" class="form-label">End Time</label>
            <input type="time" class="form-control" id="endTime" name="endTime" required>
        </div>
        <div class="col-md-3">
            <label for="reason" class="form-label">Reason</label>
            <input type="text" class="form-control" id="reason" name="reason" placeholder="e.g., Maintenance">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-danger">Block Time</button>
        </div>
    </form>

    <hr>

    <h4>Currently Blocked Time Slots</h4>
    <?php if (empty($blockedSlots)): ?>
        <div class="alert alert-info">No time slots are currently blocked for this facility.</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Reason</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blockedSlots as $slot): ?>
                    <tr>
                        <td><?= htmlspecialchars($slot->blockDate) ?></td>
                        <td><?= htmlspecialchars(date('g:i A', strtotime($slot->startTime))) ?> - <?= htmlspecialchars(date('g:i A', strtotime($slot->endTime))) ?></td>
                        <td><?= htmlspecialchars($slot->reason) ?></td>
                        <td>
                            <a href="?controller=admin&action=unblockTime&id=<?= $slot->blockedAvailabilityId ?>&facilityId=<?= $facility->facilityId ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to unblock this time slot?');">Unblock</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>