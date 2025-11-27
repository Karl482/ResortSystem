<?php
$pageTitle = "Dashboard";
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/partials/header.php';
} else {
    require_once __DIR__ . '/partials/guest_header.php';
}
?>

<div class="container mt-5">
    <?php if (!isset($_SESSION['user_id'])): ?>
    <div class="alert alert-info" role="alert">
        Please <a href="?action=login">login</a> or <a href="?action=showRegisterForm">register</a> to make a reservation and manage your bookings.
    </div>
    <?php endif; ?>
    <div class="row">
        <!-- Welcome Card -->
        <div class="col-lg-7 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-header text-center">
                    <h3 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i><?php if (isset($_SESSION['user_id'])): ?>
                            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                        <?php else: ?>
                            Welcome, Guest!
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <p class="card-text mb-3">🏖️ Explore our collection of beautiful resorts and book your next unforgettable getaway with just a few clicks! </p>
                    <p class="card-text mb-3">Our platform is designed to make your booking experience seamless and enjoyable. </p>
                    <p class="card-text">Should you have any questions or require assistance, we are here to help. 😊</p>
                </div>
            </div>
        </div>

        <!-- Admin Contact Card -->
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header text-center">
                    <h4 class="mb-0"><i class="fas fa-address-card me-2"></i>Admin Contact</h4>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <?php if (isset($_SESSION['user_id']) && $admin): ?>
                        <div class="text-center">
                            <img src="<?= $admin['ProfileImageURL'] ? BASE_URL . '/' . htmlspecialchars($admin['ProfileImageURL']) : 'https://via.placeholder.com/100' ?>" class="img-fluid rounded-circle mb-3" alt="Admin" style="width: 100px; height: 100px; object-fit: cover;">
                            <h6><?= htmlspecialchars($admin['FirstName'] . ' ' . $admin['LastName']) ?></h6>
                            <p class="mb-1"><i class="fas fa-phone me-2"></i><?= htmlspecialchars($admin['PhoneNumber']) ?></p>
                            <p class="mb-1"><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($admin['Email']) ?></p>
                            <?php if (!empty($admin['Socials'])): ?>
                                <?php
                                $socials = preg_split("/\r\n|\n|\r/", $admin['Socials']);
                                foreach ($socials as $socialLink):
                                    if (!empty(trim($socialLink))):
                                ?>
                                        <p class="mb-1"><i class="fas fa-link me-2"></i><a href="<?= htmlspecialchars(trim($socialLink)) ?>" target="_blank"><?= htmlspecialchars(trim($socialLink)) ?></a></p>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center">
                            <img src="https://via.placeholder.com/100" class="img-fluid rounded-circle mb-3" alt="Admin" style="width: 100px; height: 100px; object-fit: cover;">
                            <p class="mb-1"><i>Admin details are available for registered users only.</i></p>
                            <br>
                            <p class="mb-1"><a href="?action=login">Login</a> or <a href="?action=showRegisterForm">Register</a> to view contact information.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <br>
    <h2 class="text-center mb-4">Our Resorts</h2>

    <!-- Filter and Sort Controls -->
    <div class="row mb-4">
        <div class="col-lg-9">
            <div class="accordion" id="filterAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilter" aria-expanded="false" aria-controls="collapseFilter">
                            <i class="fas fa-filter me-2"></i> Filter Resorts
                        </button>
                    </h2>
                    <div id="collapseFilter" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#filterAccordion">
                        <div class="accordion-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="filterPrice" class="form-label">Max Starting Price (₱)</label>
                                    <input type="range" class="form-range" min="0" max="50000" step="1000" id="filterPrice" value="50000">
                                    <div class="text-center fw-bold" id="priceValue">₱50,000+</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="filterRating" class="form-label">Minimum Rating</label>
                                    <select id="filterRating" class="form-select">
                                        <option value="0">Any</option>
                                        <option value="5">⭐⭐⭐⭐⭐</option>
                                        <option value="4">⭐⭐⭐⭐ & up</option>
                                        <option value="3">⭐⭐⭐ & up</option>
                                        <option value="2">⭐⭐ & up</option>
                                        <option value="1">⭐ & up</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="filterBookings" class="form-label">Minimum Completed Bookings</label>
                                    <input type="number" class="form-control" id="filterBookings" placeholder="e.g., 10" min="0">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12 text-end">
                                    <button class="btn btn-secondary btn-sm" id="resetFilters">Reset Filters</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 d-flex align-items-stretch">
            <select id="sortResorts" class="form-select w-100">
                <option value="default" selected>Sort by...</option>
                <option value="name-asc">Name (A-Z)</option>
                <option value="name-desc">Name (Z-A)</option>
                <option value="price-asc">Price (Low to High)</option>
                <option value="price-desc">Price (High to Low)</option>
                <option value="rating-desc">Rating (High to Low)</option>
                <option value="bookings-desc">Popularity</option>
            </select>
        </div>
    </div>

    <?php if (empty($resorts)): ?>
        <p class="text-center">No resorts available at the moment.</p>
    <?php else: ?>
        <div class="row" id="resorts-list">
            <?php foreach ($resorts as $resort): ?>
                <div class="col-md-4 mb-4 resort-card"
                     data-name="<?= htmlspecialchars($resort['Name']) ?>"
                     data-price="<?= !empty($resort['StartingPrice']) ? $resort['StartingPrice'] : '0' ?>"
                     data-rating="<?= !empty($resort['AverageRating']) ? floatval($resort['AverageRating']) : '0' ?>"
                     data-bookings="<?= !empty($resort['CompletedBookingsCount']) ? intval($resort['CompletedBookingsCount']) : '0' ?>">
                    <div class="card h-100">
                        <img src="<?= $resort['MainPhotoURL'] ? BASE_URL . '/' . htmlspecialchars($resort['MainPhotoURL']) : 'https://via.placeholder.com/300x200' ?>" class="card-img-top" alt="<?= htmlspecialchars($resort['Name']) ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($resort['Name']) ?></h5>
                            <p class="card-text"><?= nl2br(htmlspecialchars($resort['ShortDescription'])) ?></p>

                            <p class="card-text small mb-1"><strong>Address:</strong> <?= htmlspecialchars($resort['Address']) ?></p>
                            <p class="card-text small"><strong>Contact:</strong> <?= htmlspecialchars($resort['ContactPerson']) ?></p>
                            
                            <?php if (!empty($resort['StartingPrice'])): ?>
                                <h5 class="card-subtitle mb-2 text-success fw-bold">Starting at ₱<?= number_format($resort['StartingPrice'], 0) ?></h5>
                            <?php endif; ?>

                            <div class="mt-auto">
                                <?php
                                $avgRating = !empty($resort['AverageRating']) ? floatval($resort['AverageRating']) : 0;
                                $feedbackCount = !empty($resort['FeedbackCount']) ? intval($resort['FeedbackCount']) : 0;
                                $completedBookings = !empty($resort['CompletedBookingsCount']) ? intval($resort['CompletedBookingsCount']) : 0;

                                if ($feedbackCount > 0): ?>
                                    <span class="badge bg-light text-dark">
                                        ⭐ <?= number_format($avgRating, 1) ?>
                                        <span class="text-muted">(<?= $feedbackCount ?> reviews)</span>
                                    </span>
                                <?php endif; ?>

                                <?php if ($completedBookings > 0): ?>
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-check-circle"></i> <?= $completedBookings ?> Completed Bookings
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#resortModal" data-resort-id="<?= $resort['ResortID'] ?>">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Resort Modal -->
<div class="modal fade" id="resortModal" tabindex="-1" aria-labelledby="resortModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resortModalLabel">Resort Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
               <ul class="nav nav-tabs" id="resortTab" role="tablist">
                   <li class="nav-item" role="presentation">
                       <button class="nav-link active" id="resort-details-tab" data-bs-toggle="tab" data-bs-target="#resort-details-content" type="button" role="tab" aria-controls="resort-details-content" aria-selected="true">Resort</button>
                   </li>
                   <li class="nav-item" role="presentation">
                       <button class="nav-link" id="resort-facilities-tab" data-bs-toggle="tab" data-bs-target="#resort-facilities-content" type="button" role="tab" aria-controls="resort-facilities-content" aria-selected="false">Facilities</button>
                   </li>
                   <li class="nav-item" role="presentation">
                       <button class="nav-link" id="resort-map-tab" data-bs-toggle="tab" data-bs-target="#resort-map-content" type="button" role="tab" aria-controls="resort-map-content" aria-selected="false">Map</button>
                   </li>
                   <li class="nav-item" role="presentation">
                       <button class="nav-link" id="resort-feedback-tab" data-bs-toggle="tab" data-bs-target="#resort-feedback-content" type="button" role="tab" aria-controls="resort-feedback-content" aria-selected="false">Feedback</button>
                   </li>
               </ul>
               <div class="tab-content pt-3" id="resortTabContent">
                   <div class="tab-pane fade show active" id="resort-details-content" role="tabpanel" aria-labelledby="resort-details-tab">
                       <!-- Resort Details content will be loaded here by JavaScript -->
                   </div>
                   <div class="tab-pane fade" id="resort-facilities-content" role="tabpanel" aria-labelledby="resort-facilities-tab">
                       <!-- Resort Facilities content will be loaded here by JavaScript -->
                   </div>
                   <div class="tab-pane fade" id="resort-map-content" role="tabpanel" aria-labelledby="resort-map-tab">
                       <!-- Resort Map content will be loaded here by JavaScript -->
                   </div>
                   <div class="tab-pane fade" id="resort-feedback-content" role="tabpanel" aria-labelledby="resort-feedback-tab">
                       <!-- Resort Feedback content will be loaded here by JavaScript -->
                   </div>
               </div>
            </div>
            <div class="modal-footer">
                <!-- Buttons will be loaded here by JavaScript -->
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

<!-- Facility Modal -->
<div class="modal fade" id="facilityModal" tabindex="-1" aria-labelledby="facilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="facilityModalLabel">Facility Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
               <ul class="nav nav-tabs" id="facilityTab" role="tablist">
                   <li class="nav-item" role="presentation">
                       <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details-content" type="button" role="tab" aria-controls="details-content" aria-selected="true">Details</button>
                   </li>
                   <li class="nav-item" role="presentation">
                       <button class="nav-link" id="feedback-tab" data-bs-toggle="tab" data-bs-target="#feedback-content" type="button" role="tab" aria-controls="feedback-content" aria-selected="false">Feedback</button>
                   </li>
               </ul>
               <div class="tab-content pt-3" id="facilityTabContent">
                   <div class="tab-pane fade show active" id="details-content" role="tabpanel" aria-labelledby="details-tab">
                       <!-- Details content will be loaded here by JavaScript -->
                   </div>
                   <div class="tab-pane fade" id="feedback-content" role="tabpanel" aria-labelledby="feedback-tab">
                       <!-- Feedback content will be loaded here by JavaScript -->
                   </div>
               </div>
            </div>
            <div class="modal-footer">
                <!-- Buttons will be loaded here by JavaScript -->
            </div>
        </div>
    </div>
</div>