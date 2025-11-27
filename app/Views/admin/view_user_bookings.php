<?php
// Prevent direct access
if (!defined('APP_LOADED')) {
    http_response_code(403);
    include __DIR__ . '/../errors/403.php';
    exit();
}

if (empty($bookings)): ?>
    <div class="alert alert-info" role="alert">
        This user has no bookings.
    </div>
<?php else: ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Resort</th>
                <th>Facilities</th>
                <th>Date</th>
                <th>Time</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($booking->ResortName ?? 'Unknown Resort') ?></strong></td>
                    <td>
                        <?php if (!empty($booking->FacilityNames)): ?>
                            <span class="badge bg-info text-dark"><?= htmlspecialchars($booking->FacilityNames) ?></span>
                        <?php else: ?>
                            <span class="text-muted small">Resort access only</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars(date('F j, Y', strtotime($booking->BookingDate))) ?></td>
                    <td><?= htmlspecialchars(Booking::getTimeSlotDisplay($booking->TimeSlotType)) ?></td>
                    <td>
                        <?php if (!empty($booking->TotalAmount)): ?>
                            <strong>₱<?= number_format($booking->TotalAmount, 2) ?></strong>
                            <?php if (!empty($booking->RemainingBalance) && $booking->RemainingBalance > 0): ?>
                                <br><small class="text-warning">Balance: ₱<?= number_format($booking->RemainingBalance, 2) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                            $statusColors = [
                                'Pending' => 'bg-warning text-dark',
                                'Confirmed' => 'bg-success',
                                'Cancelled' => 'bg-danger',
                                'Completed' => 'bg-primary'
                            ];
                            $statusClass = $statusColors[$booking->Status] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($booking->Status) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
