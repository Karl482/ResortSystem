<?php
$pageTitle = "View All Feedback";
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4"><?= htmlspecialchars($pageTitle) ?></h1>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <form action="" method="GET" id="resortFilterForm">
                <input type="hidden" name="controller" value="feedback">
                <input type="hidden" name="action" value="listAllFeedback">
                <select name="resort_id" class="form-select" onchange="this.form.submit()">
                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'Admin' || count($resorts) > 1)): ?>
                        <option value="">All Resorts</option>
                    <?php endif; ?>
                    <?php foreach ($resorts as $resort): ?>
                        <option value="<?= $resort->resortId ?>" <?= (isset($_GET['resort_id']) && $_GET['resort_id'] == $resort->resortId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($resort->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <!-- Resort Feedbacks Section -->
            <div class="mb-5">
                <h3 class="text-info">Resort Experience Feedback</h3>
                <?php if (empty($resortFeedbacks)): ?>
                    <div class="alert alert-info">No resort feedback has been submitted yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-info">
                                <tr>
                                    <th>Booking Date</th>
                                    <th>Customer</th>
                                    <th>Resort</th>
                                    <th>Included Facilities</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Media</th>
                                    <th>Submitted At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resortFeedbacks as $feedback): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('F j, Y', strtotime($feedback['BookingDate']))) ?></td>
                                        <td>
                                            <?= htmlspecialchars($feedback['CustomerName']) ?>
                                            <br>
                                            <small class="text-muted">(<?= Booking::countCompletedBookingsByCustomer($feedback['CustomerID']) ?> Completed Bookings)</small>
                                        </td>
                                        <td><strong><?= htmlspecialchars($feedback['ResortName']) ?></strong></td>
                                        <td><?= htmlspecialchars($feedback['IncludedFacilities'] ?? 'N/A') ?></td>
                                        <td>
                                            <div class="text-warning">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $feedback['Rating']): ?>
                                                        &#9733;
                                                    <?php else: ?>
                                                        &#9734;
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                        <td><?= nl2br(htmlspecialchars($feedback['Comment'])) ?></td>
                                        <td>
                                            <?php if (!empty($feedback['Media'])): ?>
                                                <?php
                                                $mediaJson = htmlspecialchars(json_encode($feedback['Media']), ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <?php foreach ($feedback['Media'] as $index => $media): ?>
                                                    <?php if ($media['MediaType'] === 'Image'): ?>
                                                        <img src="<?= BASE_URL . '/' . htmlspecialchars($media['MediaURL']) ?>"
                                                             alt="Feedback Image"
                                                             class="img-thumbnail gallery-item"
                                                             style="max-width: 75px; height: 75px; object-fit: cover; cursor: pointer;"
                                                             data-bs-toggle="modal"
                                                             data-bs-target="#mediaModal"
                                                             data-media='<?= $mediaJson ?>'
                                                             data-start-index="<?= $index ?>"
                                                             data-gallery-title="Resort Feedback from <?= htmlspecialchars($feedback['CustomerName']) ?>"
                                                             data-gallery-subtitle="<?= str_repeat('⭐', $feedback['Rating']) ?> - <?= htmlspecialchars(date('F j, Y', strtotime($feedback['BookingDate']))) ?>">
                                                   <?php elseif ($media['MediaType'] === 'Video'): ?>
                                                       <div class="video-thumbnail-wrapper"
                                                            style="display: inline-block; max-width: 75px; height: 75px; object-fit: cover; cursor: pointer; position: relative;"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#mediaModal"
                                                            data-media='<?= $mediaJson ?>'
                                                            data-start-index="<?= $index ?>"
                                                            data-gallery-title="Resort Feedback from <?= htmlspecialchars($feedback['CustomerName']) ?>"
                                                            data-gallery-subtitle="<?= str_repeat('⭐', $feedback['Rating']) ?> - <?= htmlspecialchars(date('F j, Y', strtotime($feedback['BookingDate']))) ?>">
                                                           <video class="img-thumbnail">
                                                                <source src="<?= BASE_URL . '/' . htmlspecialchars($media['MediaURL']) ?>#t=0.5" type="video/mp4">
                                                            </video>
                                                            <i class="fas fa-play-circle" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 1.5rem; color: rgba(255,255,255,0.8);"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars(date('F j, Y, g:i a', strtotime($feedback['CreatedAt']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Facility Feedbacks Section -->
            <div>
                <h3 class="text-success">Facility-Specific Feedback</h3>
                <?php if (empty($facilityFeedbacks)): ?>
                    <div class="alert alert-info">No facility feedback has been submitted yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="table-success">
                                <tr>
                                    <th>Booking Date</th>
                                    <th>Customer</th>
                                    <th>Resort</th>
                                    <th>Facility</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Media</th>
                                    <th>Submitted At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($facilityFeedbacks as $feedback): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('F j, Y', strtotime($feedback['BookingDate']))) ?></td>
                                        <td>
                                            <?= htmlspecialchars($feedback['CustomerName']) ?>
                                            <br>
                                            <small class="text-muted">(<?= Booking::countCompletedBookingsByCustomer($feedback['CustomerID']) ?> Completed Bookings)</small>
                                        </td>
                                        <td><strong><?= htmlspecialchars($feedback['ResortName']) ?></strong></td>
                                        <td><?= htmlspecialchars($feedback['FacilityName']) ?></td>
                                        <td>
                                            <div class="text-warning">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $feedback['Rating']): ?>
                                                        &#9733;
                                                    <?php else: ?>
                                                        &#9734;
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                        <td><?= nl2br(htmlspecialchars($feedback['Comment'])) ?></td>
                                        <td>
                                            <?php if (!empty($feedback['Media'])): ?>
                                                <?php
                                                $mediaJson = htmlspecialchars(json_encode($feedback['Media']), ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <?php foreach ($feedback['Media'] as $index => $media): ?>
                                                    <?php if ($media['MediaType'] === 'Image'): ?>
                                                        <img src="<?= BASE_URL . '/' . htmlspecialchars($media['MediaURL']) ?>"
                                                             alt="Feedback Image"
                                                             class="img-thumbnail gallery-item"
                                                             style="max-width: 150px; height: 110px; object-fit: cover; cursor: pointer;"
                                                             data-bs-toggle="modal"
                                                             data-bs-target="#mediaModal"
                                                             data-media='<?= $mediaJson ?>'
                                                             data-start-index="<?= $index ?>"
                                                             data-gallery-title="Facility Feedback for <?= htmlspecialchars($feedback['FacilityName']) ?>"
                                                             data-gallery-subtitle="<?= str_repeat('⭐', $feedback['Rating']) ?> | <?= htmlspecialchars(date('F j, Y', strtotime($feedback['BookingDate']))) ?> | Customer: <?= htmlspecialchars($feedback['CustomerName']) ?>">
                                                    <?php elseif ($media['MediaType'] === 'Video'): ?>
                                                        <div class="video-thumbnail-wrapper"
                                                             style="display: inline-block; max-width: 150px; height: 110px; object-fit: cover; cursor: pointer; position: relative;"
                                                             data-bs-toggle="modal"
                                                             data-bs-target="#mediaModal"
                                                             data-media='<?= $mediaJson ?>'
                                                             data-start-index="<?= $index ?>"
                                                             data-gallery-title="Facility Feedback for <?= htmlspecialchars($feedback['FacilityName']) ?>"
                                                             data-gallery-subtitle="<?= str_repeat('⭐', $feedback['Rating']) ?> | <?= htmlspecialchars(date('F j, Y', strtotime($feedback['BookingDate']))) ?> | Customer: <?= htmlspecialchars($feedback['CustomerName']) ?>">
                                                            <video class="img-thumbnail">
                                                                <source src="<?= BASE_URL . '/' . htmlspecialchars($media['MediaURL']) ?>#t=0.5" type="video/mp4">
                                                            </video>
                                                            <i class="fas fa-play-circle" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 1.5rem; color: rgba(255,255,255,0.8);"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars(date('F j, Y, g:i a', strtotime($feedback['CreatedAt']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Media Gallery Modal -->
<div class="modal fade" id="mediaModal" tabindex="-1" aria-labelledby="mediaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaModalLabel">Media Gallery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="galleryCarousel" class="carousel slide" data-bs-interval="false">
                    <div class="carousel-inner" id="galleryCarouselInner">
                        <!-- Carousel items will be injected here -->
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
                <div id="galleryThumbnails" class="mt-3 d-flex justify-content-center flex-wrap">
                    <!-- Thumbnails will be injected here -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .gallery-thumbnail.active {
        border: 3px solid #0d6efd; /* Primary Bootstrap Blue */
        transform: scale(1.1);
        opacity: 1;
    }
    .gallery-thumbnail {
        transition: all 0.2s ease-in-out;
        opacity: 0.8;
    }
</style>

<script>
    const BASE_URL = '<?= BASE_URL ?>'; // Ensure BASE_URL is available in JS
    
    document.addEventListener('DOMContentLoaded', function () {
        // Media Gallery Modal Handler
        const mediaModal = document.getElementById('mediaModal');
        if (mediaModal) {
            const carouselInner = mediaModal.querySelector('#galleryCarouselInner');
            const thumbnailsContainer = mediaModal.querySelector('#galleryThumbnails');
            
            // Use a function to initialize the carousel manually
            const getCarouselInstance = () => {
                 return bootstrap.Carousel.getInstance(document.getElementById('galleryCarousel')) || new bootstrap.Carousel(document.getElementById('galleryCarousel'), { interval: false, ride: false });
            };

            const modalTitleEl = mediaModal.querySelector('#mediaModalLabel');
            
            mediaModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const mediaJson = button.getAttribute('data-media');
                const startIndex = parseInt(button.getAttribute('data-start-index'), 10);
                const mediaItems = JSON.parse(mediaJson);

                const galleryTitle = button.getAttribute('data-gallery-title');
                const gallerySubtitle = button.getAttribute('data-gallery-subtitle');

                if (galleryTitle) {
                    modalTitleEl.innerHTML = `${galleryTitle} ${gallerySubtitle ? `<br><small class='text-muted fw-normal'>${gallerySubtitle}</small>` : ''}`;
                } else {
                    modalTitleEl.textContent = 'Feedback Gallery';
                }

                // Clear previous content
                carouselInner.innerHTML = '';
                thumbnailsContainer.innerHTML = '';

                mediaItems.forEach((item, index) => {
                    // Create carousel item
                    const carouselItem = document.createElement('div');
                    carouselItem.className = `carousel-item ${index === startIndex ? 'active' : ''}`;
                    
                    if (item.MediaType === 'Image') {
                        carouselItem.innerHTML = `<img src="${BASE_URL}/${item.MediaURL}" class="d-block w-100" style="max-height: 70vh; object-fit: contain;" alt="Feedback Image">`;
                    } else if (item.MediaType === 'Video') {
                        carouselItem.innerHTML = `<video class="d-block w-100" style="max-height: 70vh;" controls ${index === startIndex ? 'autoplay' : ''}><source src="${BASE_URL}/${item.MediaURL}" type="video/mp4">Your browser does not support the video tag.</video>`;
                    }
                    carouselInner.appendChild(carouselItem);

                    // Create thumbnail
                    const thumbnail = document.createElement('img');
                    thumbnail.src = item.MediaType === 'Image' ? `${BASE_URL}/${item.MediaURL}` : 'assets/img/video_placeholder.png'; // Placeholder for video
                    thumbnail.className = `img-thumbnail gallery-thumbnail m-1 ${index === startIndex ? 'active' : ''}`;
                    thumbnail.style.width = '80px';
                    thumbnail.style.height = '60px';
                    thumbnail.style.objectFit = 'cover';
                    thumbnail.style.cursor = 'pointer';
                    thumbnail.setAttribute('data-bs-target', '#galleryCarousel');
                    thumbnail.setAttribute('data-bs-slide-to', index);
                    thumbnailsContainer.appendChild(thumbnail);
                });
                
                // Initialize or update carousel
                const carouselInstance = getCarouselInstance();
                carouselInstance.to(startIndex);

                // Handle thumbnail clicks
                thumbnailsContainer.querySelectorAll('.gallery-thumbnail').forEach(thumb => {
                    thumb.addEventListener('click', function() {
                        const slideTo = parseInt(this.getAttribute('data-bs-slide-to'), 10);
                        getCarouselInstance().to(slideTo);
                    });
                });
            });

            // Pause videos when modal is closed or slide changes
            mediaModal.addEventListener('hidden.bs.modal', function () {
                mediaModal.querySelectorAll('video').forEach(video => video.pause());
            });

            const galleryCarousel = document.getElementById('galleryCarousel');
            galleryCarousel.addEventListener('slide.bs.carousel', function (e) {
                // Pause any playing video in the current slide
                const currentSlide = carouselInner.children[e.from];
                if (currentSlide) {
                    const video = currentSlide.querySelector('video');
                    if (video) video.pause();
                }
                // Autoplay video in the new slide
                const nextSlide = carouselInner.children[e.to];
                if (nextSlide) {
                    const video = nextSlide.querySelector('video');
                    if (video) video.play();
                }

                // Update active thumbnail
                const currentActive = thumbnailsContainer.querySelector('.active');
                if(currentActive) currentActive.classList.remove('active');
                thumbnailsContainer.children[e.to].classList.add('active');
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
