<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/Helpers/Database.php';

$options = getopt(
    '',
    [
        'created-after::',
        'created-before::',
        'booking-id-gte::',
        'booking-id-lte::',
        'limit::',
        'include-untagged',
        'dry-run',
    ]
);

$createdAfter = $options['created-after'] ?? null;
$createdBefore = $options['created-before'] ?? null;
$bookingIdGte = $options['booking-id-gte'] ?? null;
$bookingIdLte = $options['booking-id-lte'] ?? null;
$limit = isset($options['limit']) ? (int) $options['limit'] : null;
$includeUntagged = array_key_exists('include-untagged', $options);
$dryRun = array_key_exists('dry-run', $options);

$db = Database::getInstance();

$conditions = [];
$params = [];

if (!$includeUntagged) {
    $conditions[] = "(PaymentReference LIKE 'SEED-%' OR PaymentProofURL LIKE 'seeded-proof-%')";
}

if ($createdAfter) {
    $conditions[] = "CreatedAt >= :createdAfter";
    $params[':createdAfter'] = $createdAfter;
}

if ($createdBefore) {
    $conditions[] = "CreatedAt <= :createdBefore";
    $params[':createdBefore'] = $createdBefore;
}

if ($bookingIdGte) {
    $conditions[] = "BookingID >= :bookingIdGte";
    $params[':bookingIdGte'] = (int) $bookingIdGte;
}

if ($bookingIdLte) {
    $conditions[] = "BookingID <= :bookingIdLte";
    $params[':bookingIdLte'] = (int) $bookingIdLte;
}

if ($includeUntagged && empty($conditions)) {
    exit("Refusing to delete all bookings: add a filter such as --created-after or --booking-id-gte.\n");
}

$whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$selectSql = "SELECT BookingID FROM Bookings {$whereClause} ORDER BY BookingID ASC";
if ($limit) {
    $selectSql .= " LIMIT :limit";
}

$stmt = $db->prepare($selectSql);
foreach ($params as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $paramType);
}

if ($limit) {
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
}

$stmt->execute();
$bookingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($bookingIds)) {
    exit("No bookings matched the filter.\n");
}

if ($dryRun) {
    echo "Matched " . count($bookingIds) . " bookings. Run again without --dry-run to delete.\n";
    exit(0);
}

$db->beginTransaction();

try {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));

    $tablesToClean = [
        'BookingFacilities' => 'BookingID',
        'Payments' => 'BookingID',
        'PaymentSchedules' => 'BookingID',
        'BookingAuditTrail' => 'BookingID',
    ];

    foreach ($tablesToClean as $table => $column) {
        $stmt = $db->prepare("DELETE FROM {$table} WHERE {$column} IN ({$placeholders})");
        $stmt->execute($bookingIds);
    }

    $deleteBookingsStmt = $db->prepare("DELETE FROM Bookings WHERE BookingID IN ({$placeholders})");
    $deleteBookingsStmt->execute($bookingIds);

    $db->commit();
    echo "Deleted " . count($bookingIds) . " bookings.\n";
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Failed to clear bookings: {$e->getMessage()}\n");
    exit(1);
}

