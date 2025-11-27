<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/Helpers/Database.php';
require_once __DIR__ . '/../../app/Models/Booking.php';
require_once __DIR__ . '/../../app/Models/BookingFacilities.php';

$targetCount = 1000;
$db = Database::getInstance();

$customerIds = $db->query("SELECT UserID FROM Users WHERE Role IN ('Customer', 'Guest')")->fetchAll(PDO::FETCH_COLUMN);
if (empty($customerIds)) {
    exit("Seeding aborted: no customers found.\n");
}

$resortFacilitiesStmt = $db->query("
    SELECT r.ResortID, f.FacilityID
    FROM Resorts r
    JOIN Facilities f ON f.ResortID = r.ResortID
");
$facilityRows = $resortFacilitiesStmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($facilityRows)) {
    exit("Seeding aborted: no resorts with facilities available.\n");
}

$facilitiesByResort = [];
foreach ($facilityRows as $row) {
    $resortId = (int) $row['ResortID'];
    $facilitiesByResort[$resortId][] = (int) $row['FacilityID'];
}
$resortIds = array_keys($facilitiesByResort);

$statuses = ['Pending', 'Confirmed', 'Cancelled', 'Completed'];
$timeSlots = ['12_hours', 'overnight', '24_hours'];

$inserted = 0;

for ($i = 0; $i < $targetCount; $i++) {
    $booking = new Booking();

    $booking->customerId = (int) $customerIds[array_rand($customerIds)];
    $resortId = (int) $resortIds[array_rand($resortIds)];
    $booking->resortId = $resortId;

    $resortFacilities = $facilitiesByResort[$resortId];
    shuffle($resortFacilities);
    $selectedFacilities = array_slice($resortFacilities, 0, mt_rand(1, min(3, count($resortFacilities))));
    $booking->facilityId = $selectedFacilities[0];

    $daysOffset = mt_rand(-150, 150);
    $bookingDate = (new DateTimeImmutable())
        ->modify(($daysOffset >= 0 ? '+' : '') . $daysOffset . ' days')
        ->format('Y-m-d');
    $booking->bookingDate = $bookingDate;

    $booking->timeSlotType = $timeSlots[array_rand($timeSlots)];

    $status = $statuses[$i % count($statuses)];
    $booking->status = $status;

    $totalAmount = mt_rand(45000, 200000) / 10; // ₱4,500 - ₱20,000
    $booking->totalAmount = number_format($totalAmount, 2, '.', '');

    $maybeReference = function (): string {
        return 'SEED-' . strtoupper(bin2hex(random_bytes(3)));
    };

    $booking->paymentProofURL = null;
    $booking->paymentReference = $maybeReference();
    $booking->expiresAt = null;

    if ($status === 'Pending') {
        $booking->expiresAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->modify('+' . mt_rand(2, 6) . ' hours')
            ->format('Y-m-d H:i:s');
    }

    $remainingBalance = $totalAmount;

    switch ($status) {
        case 'Completed':
            $booking->paymentProofURL = 'seeded-proof-completed.jpg';
            $booking->paymentReference = $maybeReference();
            $remainingBalance = 0;
            break;

        case 'Confirmed':
            $booking->paymentProofURL = 'seeded-proof-confirmed.jpg';
            $booking->paymentReference = $maybeReference();
            $amountPaid = mt_rand((int) ($totalAmount * 60), (int) ($totalAmount * 100)) / 100;
            $remainingBalance = max(0, $totalAmount - $amountPaid);
            break;

        case 'Cancelled':
            if (mt_rand(0, 100) < 30) {
                $booking->paymentProofURL = 'seeded-proof-cancelled.jpg';
                $booking->paymentReference = $maybeReference();
                $deposit = mt_rand((int) ($totalAmount * 20), (int) ($totalAmount * 40)) / 100;
                $remainingBalance = max(0, $totalAmount - $deposit);
            }
            break;

        default:
            // Pending keeps the full balance.
            break;
    }

    $booking->remainingBalance = number_format($remainingBalance, 2, '.', '');

    $bookingId = Booking::create($booking);
    if (!$bookingId) {
        continue;
    }

    BookingFacilities::addFacilitiesToBooking($bookingId, array_unique($selectedFacilities));
    $inserted++;
}

echo "Successfully seeded {$inserted} booking records.\n";

