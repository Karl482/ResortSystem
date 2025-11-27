<?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['Admin', 'Staff'])): ?>
                </div> <!-- Close container-fluid py-4 -->
            </main> <!-- Close admin-main-content -->
        </div> <!-- Close admin-layout-wrapper -->
<?php else: ?>
</div> <!-- Close container -->
<?php endif; ?>


<style>
    #main-photo-container {
        height: 400px;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: .25rem;
        overflow: hidden;
    }
    #main-photo-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .thumbnail-gallery {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding: 10px 0;
    }
    .thumbnail-gallery img {
        width: 100px;
        height: 75px;
        object-fit: cover;
        cursor: pointer;
        border-radius: .25rem;
        border: 2px solid transparent;
        transition: border-color 0.2s ease-in-out;
    }
    .thumbnail-gallery img:hover, .thumbnail-gallery img.active {
        border-color: #0d6efd;
    }
    </style>
    <!-- Resort Media Gallery Modal -->
    <div class="modal fade" id="resortMediaGalleryModal" tabindex="-1" aria-labelledby="resortMediaGalleryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resortMediaGalleryModalLabel">Media Gallery</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="resortGalleryCarousel" class="carousel slide" data-bs-interval="false">
                        <div class="carousel-inner" id="resortGalleryCarouselInner">
                            <!-- Carousel items will be injected here -->
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#resortGalleryCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#resortGalleryCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                    <div id="resortGalleryThumbnails" class="mt-3 d-flex justify-content-center flex-wrap">
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
            cursor: pointer;
        }
        /* Increase size for feedback thumbnails specifically inside the resort modal */
        #resort-feedback-content .feedback-media .img-thumbnail,
        #resort-feedback-content .feedback-media .video-thumbnail-wrapper {
            width: 150px !important;
            height: 110px !important;
            object-fit: cover;
        }
        #main-photo-container {
            cursor: pointer;
        }
    </style>
    <script>
    const BASE_URL = '<?= BASE_URL ?>';
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const resortModalEl = document.getElementById('resortModal');
    const facilityModalEl = document.getElementById('facilityModal');
    const resortMediaGalleryModalEl = document.getElementById('resortMediaGalleryModal');

    if (!resortModalEl || !facilityModalEl || !resortMediaGalleryModalEl) return;

    const resortModal = new bootstrap.Modal(resortModalEl);
    const facilityModal = new bootstrap.Modal(facilityModalEl);
    const resortMediaGalleryModal = new bootstrap.Modal(resortMediaGalleryModalEl);

    // --- Resort Modal Logic ---
    if (resortModalEl) {
        resortModalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const resortId = button.getAttribute('data-resort-id');
            resortModalEl.dataset.currentResortId = resortId;

            const detailsTabTrigger = resortModalEl.querySelector('#resort-details-tab');
            if (detailsTabTrigger) new bootstrap.Tab(detailsTabTrigger).show();

            const modalTitle = resortModalEl.querySelector('.modal-title');
            const detailsTab = resortModalEl.querySelector('#resort-details-content');
            const facilitiesTab = resortModalEl.querySelector('#resort-facilities-content');
            const mapTab = resortModalEl.querySelector('#resort-map-content');
            const feedbackTab = resortModalEl.querySelector('#resort-feedback-content');
            const modalFooter = resortModalEl.querySelector('.modal-footer');

            modalTitle.textContent = 'Loading...';
            detailsTab.innerHTML = '<p class="text-center">Loading resort details...</p>';
            facilitiesTab.innerHTML = '<p class="text-center">Loading facilities...</p>';
            if (mapTab) mapTab.innerHTML = '<p class="text-center">Loading map...</p>';
            feedbackTab.innerHTML = '<p class="text-center">Loading feedback...</p>';
            modalFooter.innerHTML = '';

            // Fetch Resort Details
            fetch('?controller=user&action=getResortDetails&id=' + resortId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        modalTitle.textContent = 'Error';
                        detailsTab.innerHTML = `<p class="text-danger">${data.error}</p>`;
                        return;
                    }
                    modalTitle.textContent = data.name;

                    let allPhotos = [];
                    if (data.mainPhotoURL) allPhotos.push({ MediaType: 'Image', MediaURL: data.mainPhotoURL });
                    if (data.photos) data.photos.forEach(p => {
                        if (p.PhotoURL !== data.mainPhotoURL) allPhotos.push({ MediaType: 'Image', MediaURL: p.PhotoURL })
                    });
                    
                    const mediaJson = JSON.stringify(allPhotos);
                    const galleryTitle = `Photos of ${data.name}`;

                    const mainPhotoHTML = allPhotos.length > 0 ? `<img src="${BASE_URL}/${allPhotos[0].MediaURL}" id="main-resort-photo" alt="Main Resort Photo" class="resort-gallery-item" data-media='${mediaJson}' data-start-index="0" data-gallery-title="${galleryTitle}">` : '<p>No photo available.</p>';
                    const thumbnailsHTML = allPhotos.map((photo, index) => `<img src="${BASE_URL}/${photo.MediaURL}" class="thumbnail-item-resort resort-gallery-item ${index === 0 ? 'active' : ''}" alt="Thumbnail ${index + 1}" data-media='${mediaJson}' data-start-index="${index}" data-gallery-title="${galleryTitle}">`).join('');

                    detailsTab.innerHTML = `
                        <div id="main-photo-container" class="mb-3">${mainPhotoHTML}</div>
                        <div class="thumbnail-gallery">${thumbnailsHTML}</div><hr>
                        <h5>About ${data.name}</h5><p>${data.fullDescription || 'No description available.'}</p>
                        <hr>
                        <p><strong>Address:</strong> ${data.address}</p>
                        <p><strong>Contact:</strong> ${data.contactPerson}</p>
                        ${data.startingPrice ? `<hr><h5 class="text-success fw-bold">Starting at ₱${Number(data.startingPrice).toLocaleString('en-US', { maximumFractionDigits: 0 })}</h5>` : ''}
                        `;
                    
                    const mainPhoto = detailsTab.querySelector('#main-resort-photo');
                    const thumbnails = detailsTab.querySelectorAll('.thumbnail-item-resort');
                    thumbnails.forEach(thumb => {
                        thumb.addEventListener('click', function(e) {
                            if (!e.target.classList.contains('resort-gallery-item')) {
                                if(mainPhoto) mainPhoto.src = this.src;
                                thumbnails.forEach(t => t.classList.remove('active'));
                                this.classList.add('active');
                            }
                        });
                    });

                    // --- Populate Map Tab ---
                    const mapTab = resortModalEl.querySelector('#resort-map-content');
                    const mapLink = data.googleMapsLink;
                    if (mapTab) {
                        if (mapLink) {
                            let finalEmbedLink = mapLink;
                            if (!mapLink.includes('embed')) {
                                finalEmbedLink = `https://maps.google.com/maps?q=${encodeURIComponent(data.address)}&t=&z=15&ie=UTF8&iwloc=&output=embed`;
                            }
                            mapTab.innerHTML = `<div class="ratio ratio-16x9"><iframe src="${finalEmbedLink}" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>`;
                        } else {
                            mapTab.innerHTML = '<div class="alert alert-warning text-center">Map link is not available for this resort.</div>';
                        }
                    }

                }).catch(error => {
                    modalTitle.textContent = 'Error';
                    detailsTab.innerHTML = '<p class="text-danger">Failed to load resort details.</p>';
                });

            // Fetch Resort Facilities
            fetch('?controller=user&action=getResortFacilities&id=' + resortId)
                .then(response => response.json())
                .then(data => {
                     if (data.length === 0) {
                        facilitiesTab.innerHTML = '<p class="text-center">No facilities available for this resort.</p>';
                    } else {
                        let facilitiesHtml = '<div class="row">';
                        data.forEach(facility => {
                            const avgRating = parseFloat(facility.AverageRating).toFixed(1);
                            const feedbackCount = parseInt(facility.FeedbackCount) || 0;
                            const completedBookings = parseInt(facility.CompletedBookingsCount) || 0;

                            let badgesHtml = '<div class="mt-auto">';
                            if (feedbackCount > 0 && !isNaN(avgRating)) {
                                badgesHtml += `<span class="badge bg-light text-dark">⭐ ${avgRating} <span class="text-muted">(${feedbackCount} reviews)</span></span>`;
                            }
                            if (completedBookings > 0) {
                                badgesHtml += `<span class="badge bg-light text-dark"><i class="fas fa-check-circle"></i> ${completedBookings} Completed Bookings</span>`;
                            }
                            badgesHtml += '</div>';

                            facilitiesHtml += `
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100 d-flex flex-column">
                                        <img src="${facility.MainPhotoURL ? BASE_URL + '/' + facility.MainPhotoURL : 'https://via.placeholder.com/300x200'}" class="card-img-top" alt="${facility.Name}" style="height: 150px; object-fit: cover;">
                                        <div class="card-body d-flex flex-column">
                                            <h6 class="card-title">${facility.Name}</h6>
                                            <p class="card-text small">${facility.shortDescription}</p>
                                            ${badgesHtml}
                                        </div>
                                        <div class="card-footer">
                                            <button class="btn btn-secondary btn-sm w-100 view-facility-details" data-facility-id="${facility.FacilityID}">View Details</button>
                                        </div>
                                    </div>
                                </div>`;
                        });
                        facilitiesHtml += '</div>';
                        facilitiesTab.innerHTML = facilitiesHtml;
                    }
                }).catch(error => {
                    facilitiesTab.innerHTML = '<p class="text-danger">Could not load facilities.</p>';
                });

            // Fetch Resort Feedback
            fetch('?controller=user&action=getResortFeedback&id=' + resortId)
                .then(response => response.ok ? response.json() : [])
                .then(data => {
                    const feedbackTabTrigger = resortModalEl.querySelector('#resort-feedback-tab');
                    if (!data || data.length === 0) {
                        feedbackTab.innerHTML = '<p class="text-center">No feedback available for this resort yet.</p>';
                        if (feedbackTabTrigger) feedbackTabTrigger.textContent = 'Feedback (0)';
                    } else {
                        let feedbackHtml = `<h5>Customer Reviews (${data.length})</h5>`;
                        data.forEach(review => {
                            const completedBookingsBadge = review.completedBookings > 0 ? `<span class="badge bg-light text-dark"><i class="fas fa-check-circle"></i> ${review.completedBookings} Completed Bookings</span>` : '';
                            const facilitiesBadge = review.IncludedFacilities ? `<span class="badge bg-info text-dark">${review.IncludedFacilities}</span>` : '';
                            
                            let mediaHtml = '';
                            if (review.Media && review.Media.length > 0) {
                                const mediaJson = JSON.stringify(review.Media);
                                const reviewDate = new Date(review.CreatedAt).toLocaleDateString([], { year: 'numeric', month: 'long', day: 'numeric' });
                                const galleryTitle = `Feedback from ${review.CustomerName}`;
                                let gallerySubtitle = `${'⭐'.repeat(review.Rating)} - ${reviewDate}`;
                                if (review.IncludedFacilities) {
                                    gallerySubtitle += ` (${review.IncludedFacilities})`;
                                }

                                mediaHtml += '<div class="feedback-media mt-2 d-flex flex-wrap gap-2">';
                                review.Media.forEach((media, index) => {
                                    const commonDataAttributes = `data-media='${mediaJson}' data-start-index="${index}" data-gallery-title="${galleryTitle}" data-gallery-subtitle="${gallerySubtitle}"`;
                                    if (media.MediaType === 'Image') {
                                        mediaHtml += `<img src="${BASE_URL}/${media.MediaURL}" alt="Feedback Image" class="img-thumbnail resort-gallery-item" ${commonDataAttributes}>`;
                                    } else if (media.MediaType === 'Video') {
                                        mediaHtml += `
                                            <div class="video-thumbnail-wrapper resort-gallery-item" ${commonDataAttributes}>
                                                <video class="img-thumbnail"><source src="${BASE_URL}/${media.MediaURL}#t=0.5" type="video/mp4"></video>
                                                <i class="fas fa-play-circle" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 2rem; color: rgba(255,255,255,0.8);"></i>
                                            </div>`;
                                    }
                                });
                                mediaHtml += '</div>';
                            }

                            feedbackHtml += `
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="card-title mb-0">${review.CustomerName}</h6>
                                            <span class="text-warning">${'⭐'.repeat(review.Rating)}</span>
                                        </div>
                                        <div class="mb-2">${facilitiesBadge} ${completedBookingsBadge}</div>
                                        <p class="card-text">${review.Comment || '<em>No comment provided.</em>'}</p>
                                        ${mediaHtml}
                                        <small class="text-muted">Posted on ${new Date(review.CreatedAt).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' })}</small>
                                    </div>
                                </div>`;
                        });
                        feedbackTab.innerHTML = feedbackHtml;
                        if (feedbackTabTrigger) feedbackTabTrigger.textContent = `Feedback (${data.length})`;
                    }
                }).catch(error => {
                    feedbackTab.innerHTML = '<p class="text-danger">Could not load feedback.</p>';
                    const feedbackTabTrigger = resortModalEl.querySelector('#resort-feedback-tab');
                    if (feedbackTabTrigger) feedbackTabTrigger.textContent = 'Feedback (Error)';
                });

            modalFooter.innerHTML = `<a href="?controller=booking&action=showBookingForm&resort_id=${resortId}" class="btn btn-success">Book Resort Experience</a>`;
        });

        // Use event delegation for facility and gallery buttons
        resortModalEl.addEventListener('click', function(event) {
            const facilityButton = event.target.closest('.view-facility-details');
            const galleryItem = event.target.closest('.resort-gallery-item');

            if (facilityButton) {
                const facilityId = facilityButton.dataset.facilityId;
                const resortId = resortModalEl.dataset.currentResortId;
                resortModal.hide();
                const handleFacilityModalClose = () => {
                    resortModal.show();
                    facilityModalEl.removeEventListener('hidden.bs.modal', handleFacilityModalClose);
                };
                facilityModalEl.addEventListener('hidden.bs.modal', handleFacilityModalClose);
                populateAndShowFacilityModal(facilityId, resortId);
            } else if (galleryItem) {
                resortModal.hide();
                populateAndShowGalleryModal(galleryItem);
            }
        });
    }

    // --- Facility Modal Logic ---
    function populateAndShowFacilityModal(facilityId, resortId) {
        // ... (existing facility modal logic remains largely unchanged) ...
        // Note: The feedback media inside the facility modal will need a similar gallery implementation if desired.
        // For now, focusing on the main resort modal as requested.
        const detailsTabTrigger = facilityModalEl.querySelector('#details-tab');
        if (detailsTabTrigger) new bootstrap.Tab(detailsTabTrigger).show();

        const modalTitle = facilityModalEl.querySelector('.modal-title');
        const detailsTab = facilityModalEl.querySelector('#details-content');
        const feedbackTab = facilityModalEl.querySelector('#feedback-content');
        const modalFooter = facilityModalEl.querySelector('.modal-footer');

        modalTitle.textContent = 'Loading...';
        detailsTab.innerHTML = '<p class="text-center">Loading facility details...</p>';
        feedbackTab.innerHTML = '<p class="text-center">Loading feedback...</p>';
        modalFooter.innerHTML = '';
        
        facilityModal.show();

        // Fetch Facility Details
        fetch('?controller=user&action=getFacilityDetails&id=' + facilityId)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    modalTitle.textContent = 'Error';
                    detailsTab.innerHTML = `<p class="text-danger">${data.error}</p>`;
                    return;
                }
                modalTitle.textContent = data.name;

                let allPhotos = [ ...(data.mainPhotoURL ? [data.mainPhotoURL] : []), ...(data.photos ? data.photos.map(p => p.PhotoURL) : []) ];
                allPhotos = [...new Set(allPhotos)];

                const mainPhotoHTML = allPhotos.length > 0 ? `<img src="${BASE_URL}/${allPhotos[0]}" id="main-facility-photo" alt="Main Facility Photo">` : '<p>No photo available.</p>';
                const thumbnailsHTML = allPhotos.map((photoURL, index) => `<img src="${BASE_URL}/${photoURL}" class="thumbnail-item ${index === 0 ? 'active' : ''}" alt="Thumbnail ${index + 1}">`).join('');

                detailsTab.innerHTML = `
                    <div id="main-photo-container" class="mb-3">${mainPhotoHTML}</div>
                    <div class="thumbnail-gallery">${thumbnailsHTML}</div><hr>
                    <h5>Description</h5><p>${data.fullDescription || 'No description available.'}</p><hr>
                    <p><strong>Price:</strong> ₱${parseFloat(data.rate).toFixed(2)}</p>`;

                modalFooter.innerHTML = `<a href="?controller=booking&action=showBookingForm&resort_id=${resortId}&facility_id=${data.facilityId}" class="btn btn-success">Book This Facility</a>`;

                const mainPhoto = detailsTab.querySelector('#main-facility-photo');
                const thumbnails = detailsTab.querySelectorAll('.thumbnail-item');
                thumbnails.forEach(thumb => {
                    thumb.addEventListener('click', function() {
                        if (mainPhoto) mainPhoto.src = this.src;
                        thumbnails.forEach(t => t.classList.remove('active'));
                        this.classList.add('active');
                    });
                });
            }).catch(error => {
                modalTitle.textContent = 'Error';
                detailsTab.innerHTML = '<p class="text-danger">Failed to load facility details.</p>';
            });

        // Fetch Facility Feedback
        fetch('?controller=feedback&action=getFacilityFeedback&facility_id=' + facilityId)
            .then(response => response.json())
            .then(data => {
                const feedbackTabTrigger = facilityModalEl.querySelector('#feedback-tab');
                if (!data.success) {
                    feedbackTab.innerHTML = `<p class="text-danger">${data.error}</p>`;
                    if (feedbackTabTrigger) feedbackTabTrigger.textContent = 'Feedback (Error)';
                    return;
                }
                const reviews = data.feedback.reviews || [];
                const totalBookings = parseInt(data.feedback.totalCompletedBookings) || 0;
                if (reviews.length === 0) {
                    let emptyHtml = '<p class="text-center">No reviews available for this facility yet.</p>';
                    if (totalBookings > 0) emptyHtml += `<p class="text-center text-muted mt-2">Used in ${totalBookings} Completed Bookings.</p>`;
                    feedbackTab.innerHTML = emptyHtml;
                    if (feedbackTabTrigger) feedbackTabTrigger.textContent = 'Feedback (0)';
                } else {
                    let feedbackHtml = `<h5>Customer Reviews (${reviews.length})</h5>`;
                    if (totalBookings > 0) feedbackHtml += `<p class="text-muted">This facility has been part of ${totalBookings} completed bookings.</p>`;
                    feedbackHtml += '<hr>';
                    reviews.forEach(review => {
                        const completedBookingsBadge = review.completedBookings > 0 ? `<span class="badge bg-light text-dark ms-2"><i class="fas fa-check-circle"></i> ${review.completedBookings} Completed Bookings</span>` : '';
                        let mediaHtml = '';
                        if (review.Media && review.Media.length > 0) {
                            mediaHtml += '<div class="feedback-media mt-2">';
                            review.Media.forEach(media => {
                                if (media.MediaType === 'Image') {
                                    mediaHtml += `<img src="${BASE_URL}/${media.MediaURL}" alt="Feedback Image" class="img-thumbnail" style="max-width: 100px;">`;
                                } else if (media.MediaType === 'Video') {
                                    mediaHtml += `<video controls class="img-thumbnail" style="max-width: 100px;"><source src="${BASE_URL}/${media.MediaURL}" type="video/mp4"></video>`;
                                }
                            });
                            mediaHtml += '</div>';
                        }
                        feedbackHtml += `
                            <div class="card mb-2">
                                <div class="card-body">
                                    <h6 class="card-title">${review.CustomerName} ${completedBookingsBadge}<span class="text-warning float-end">${'⭐'.repeat(review.Rating)}</span></h6>
                                    <p class="card-text">${review.Comment || '<em>No comment provided.</em>'}</p>
                                    ${mediaHtml}
                                    <small class="text-muted">Posted on ${new Date(review.CreatedAt).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' })}</small>
                                </div>
                            </div>`;
                    });
                    feedbackTab.innerHTML = feedbackHtml;
                    if (feedbackTabTrigger) feedbackTabTrigger.textContent = `Feedback (${reviews.length})`;
                }
            }).catch(error => {
                const feedbackTabTrigger = facilityModalEl.querySelector('#feedback-tab');
                feedbackTab.innerHTML = '<p class="text-danger">Could not load feedback.</p>';
                if (feedbackTabTrigger) feedbackTabTrigger.textContent = 'Feedback (Error)';
            });
    }

    // --- Gallery Modal Logic ---
    function populateAndShowGalleryModal(triggerElement) {
        const modalTitleEl = resortMediaGalleryModalEl.querySelector('.modal-title');
        const carouselInner = resortMediaGalleryModalEl.querySelector('#resortGalleryCarouselInner');
        const thumbnailsContainer = resortMediaGalleryModalEl.querySelector('#resortGalleryThumbnails');
        
        const mediaJson = triggerElement.getAttribute('data-media');
        const startIndex = parseInt(triggerElement.getAttribute('data-start-index'), 10);
        const mediaItems = JSON.parse(mediaJson);
        
        const galleryTitle = triggerElement.getAttribute('data-gallery-title');
        const gallerySubtitle = triggerElement.getAttribute('data-gallery-subtitle');

        if (galleryTitle) {
            modalTitleEl.innerHTML = `${galleryTitle} ${gallerySubtitle ? `<br><small class='text-muted fw-normal'>${gallerySubtitle}</small>` : ''}`;
        } else {
            modalTitleEl.textContent = 'Media Gallery';
        }

        carouselInner.innerHTML = '';
        thumbnailsContainer.innerHTML = '';

        mediaItems.forEach((item, index) => {
            const carouselItem = document.createElement('div');
            carouselItem.className = `carousel-item ${index === startIndex ? 'active' : ''}`;
            if (item.MediaType === 'Image') {
                carouselItem.innerHTML = `<img src="${BASE_URL}/${item.MediaURL}" class="d-block w-100" style="max-height: 70vh; object-fit: contain;" alt="Gallery Image">`;
            } else if (item.MediaType === 'Video') {
                carouselItem.innerHTML = `<video class="d-block w-100" style="max-height: 70vh;" controls><source src="${BASE_URL}/${item.MediaURL}" type="video/mp4"></video>`;
            }
            carouselInner.appendChild(carouselItem);

            const thumbnail = document.createElement('img');
            thumbnail.src = item.MediaType === 'Image' ? `${BASE_URL}/${item.MediaURL}` : 'assets/img/video_placeholder.png'; // Replace with a real placeholder
            thumbnail.className = `img-thumbnail gallery-thumbnail m-1 ${index === startIndex ? 'active' : ''}`;
            thumbnail.style.width = '80px';
            thumbnail.style.height = '60px';
            thumbnail.style.objectFit = 'cover';
            thumbnail.setAttribute('data-bs-target', '#resortGalleryCarousel');
            thumbnail.setAttribute('data-bs-slide-to', index);
            thumbnailsContainer.appendChild(thumbnail);
        });

        const carouselInstance = bootstrap.Carousel.getInstance(document.getElementById('resortGalleryCarousel')) || new bootstrap.Carousel(document.getElementById('resortGalleryCarousel'), { interval: false, ride: false });
        carouselInstance.to(startIndex);

        resortMediaGalleryModal.show();
    }

    // Handle nested modal behavior
    resortMediaGalleryModalEl.addEventListener('hidden.bs.modal', function () {
        resortMediaGalleryModalEl.querySelectorAll('video').forEach(video => video.pause());
        resortModal.show();
    });

    const galleryCarousel = document.getElementById('resortGalleryCarousel');
    galleryCarousel.addEventListener('slide.bs.carousel', function (e) {
        const carouselInner = galleryCarousel.querySelector('.carousel-inner');
        const thumbnailsContainer = resortMediaGalleryModalEl.querySelector('#resortGalleryThumbnails');
        
        const currentSlide = carouselInner.children[e.from];
        if (currentSlide) {
            const video = currentSlide.querySelector('video');
            if (video) video.pause();
        }
        
        const currentActive = thumbnailsContainer.querySelector('.active');
        if(currentActive) currentActive.classList.remove('active');
        thumbnailsContainer.children[e.to].classList.add('active');
    });

    // Handle thumbnail clicks inside gallery
    resortMediaGalleryModalEl.addEventListener('click', function(e) {
        if (e.target.classList.contains('gallery-thumbnail')) {
            const slideTo = parseInt(e.target.getAttribute('data-bs-slide-to'), 10);
            bootstrap.Carousel.getInstance(galleryCarousel).to(slideTo);
        }
    });
});

    // --- Resort Filter and Sort Logic ---
    const resortsList = document.getElementById('resorts-list');
    if (resortsList) {
        const filterPrice = document.getElementById('filterPrice');
        const priceValue = document.getElementById('priceValue');
        const filterRating = document.getElementById('filterRating');
        const filterBookings = document.getElementById('filterBookings');
        const sortResorts = document.getElementById('sortResorts');
        const resetFiltersBtn = document.getElementById('resetFilters');

        const applyFiltersAndSort = () => {
            const maxPrice = parseInt(filterPrice.value, 10);
            const minRating = parseFloat(filterRating.value);
            const minBookings = filterBookings.value ? parseInt(filterBookings.value, 10) : 0;
            const sortValue = sortResorts.value;

            const resortCards = Array.from(resortsList.querySelectorAll('.resort-card'));

            // 1. Filter
            resortCards.forEach(card => {
                const price = parseInt(card.dataset.price, 10);
                const rating = parseFloat(card.dataset.rating);
                const bookings = parseInt(card.dataset.bookings, 10);

                const priceMatch = maxPrice === 50000 ? true : price <= maxPrice;
                const ratingMatch = rating >= minRating;
                const bookingsMatch = bookings >= minBookings;

                if (priceMatch && ratingMatch && bookingsMatch) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            // 2. Sort
            const visibleCards = resortCards.filter(card => card.style.display !== 'none');
            
            visibleCards.sort((a, b) => {
                const nameA = a.dataset.name.toLowerCase();
                const nameB = b.dataset.name.toLowerCase();
                const priceA = parseInt(a.dataset.price, 10);
                const priceB = parseInt(b.dataset.price, 10);
                const ratingA = parseFloat(a.dataset.rating);
                const ratingB = parseFloat(b.dataset.rating);
                const bookingsA = parseInt(a.dataset.bookings, 10);
                const bookingsB = parseInt(b.dataset.bookings, 10);

                switch (sortValue) {
                    case 'name-asc': return nameA.localeCompare(nameB);
                    case 'name-desc': return nameB.localeCompare(nameA);
                    case 'price-asc': return priceA - priceB;
                    case 'price-desc': return priceB - priceA;
                    case 'rating-desc': return ratingB - ratingA;
                    case 'bookings-desc': return bookingsB - bookingsA;
                    default: return 0;
                }
            });

            // 3. Re-append to DOM
            visibleCards.forEach(card => resortsList.appendChild(card));
        };

        const updatePriceLabel = () => {
            const value = parseInt(filterPrice.value, 10);
            if (value === 50000) {
                priceValue.textContent = '₱50,000+';
            } else {
                priceValue.textContent = `₱${value.toLocaleString()}`;
            }
        };

        const resetFilters = () => {
            filterPrice.value = 50000;
            filterRating.value = 0;
            filterBookings.value = '';
            sortResorts.value = 'default';
            updatePriceLabel();
            applyFiltersAndSort();
        };

        filterPrice.addEventListener('input', updatePriceLabel);
        filterPrice.addEventListener('change', applyFiltersAndSort);
        filterRating.addEventListener('change', applyFiltersAndSort);
        filterBookings.addEventListener('input', applyFiltersAndSort);
        sortResorts.addEventListener('change', applyFiltersAndSort);
        resetFiltersBtn.addEventListener('click', resetFilters);
        
        // Initial setup
        updatePriceLabel();
    }

    // --- Admin Sidebar Toggle Logic ---
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const adminSidebar = document.getElementById('admin-sidebar');

    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', () => {
            adminSidebar.classList.add('show');
            if (sidebarOverlay) sidebarOverlay.classList.add('show');
            document.body.classList.add('sidebar-open');
        });
    }

    if (sidebarClose && adminSidebar) {
        sidebarClose.addEventListener('click', () => {
            adminSidebar.classList.remove('show');
            if (sidebarOverlay) sidebarOverlay.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        });
    }

    if (sidebarOverlay && adminSidebar) {
        sidebarOverlay.addEventListener('click', () => {
            adminSidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        });
    }

    // --- Theme Switcher Logic (supports multiple switchers) ---
    const themeSwitchers = document.querySelectorAll('.theme-switcher');
    const body = document.body;

    // Function to update all theme switcher icons
    const updateAllThemeIcons = () => {
        themeSwitchers.forEach(switcher => {
            const sunIcon = switcher.querySelector('.fa-sun');
            const moonIcon = switcher.querySelector('.fa-moon');
            if (body.classList.contains('dark-theme')) {
                if (sunIcon) sunIcon.classList.remove('active');
                if (moonIcon) moonIcon.classList.add('active');
            } else {
                if (moonIcon) moonIcon.classList.remove('active');
                if (sunIcon) sunIcon.classList.add('active');
            }
        });
    };

    // Check for saved theme in localStorage
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme === 'dark') {
        body.classList.add('dark-theme');
    }
    updateAllThemeIcons(); // Set initial icon state

    // Add click listeners to all theme switchers
    themeSwitchers.forEach(switcher => {
        switcher.addEventListener('click', () => {
            body.classList.toggle('dark-theme');
            
            // Save theme preference
            if (body.classList.contains('dark-theme')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.removeItem('theme');
            }
            updateAllThemeIcons(); // Update icons on all switchers
        });
    });
</script>
<!-- Bootstrap JS Bundle with Popper -->
</body>
</html>
