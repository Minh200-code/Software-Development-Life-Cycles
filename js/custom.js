// Custom JavaScript for Harbor Lights Hotel

// Star Rating Functionality
function initStarRating() {
    const stars = document.querySelectorAll('.star');
    if (stars.length > 0) {
        stars.forEach((star, index) => {
            star.addEventListener('mouseenter', function() {
                highlightStars(this.getAttribute('data-rating'));
            });
            
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                const radioInput = document.querySelector(`input[name="rating"][value="${rating}"]`);
                if (radioInput) {
                    radioInput.checked = true;
                    highlightStars(rating);
                }
            });
        });
        
        const ratingContainer = document.querySelector('.rating-input');
        if (ratingContainer) {
            ratingContainer.addEventListener('mouseleave', function() {
                const selectedRating = document.querySelector('input[name="rating"]:checked');
                if (selectedRating) {
                    highlightStars(selectedRating.value);
                } else {
                    resetStars();
                }
            });
        }
        
        // Initialize with selected rating
        const selectedRating = document.querySelector('input[name="rating"]:checked');
        if (selectedRating) {
            highlightStars(selectedRating.value);
        }
    }
}

function highlightStars(rating) {
    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        star.style.color = index < rating ? '#ffc107' : '#ddd';
    });
}

function resetStars() {
    const stars = document.querySelectorAll('.star');
    stars.forEach(star => {
        star.style.color = '#ddd';
    });
}

// Price Calculation for Room Booking
function initPriceCalculation() {
    const checkInInput = document.getElementById('check_in_date');
    const checkOutInput = document.getElementById('check_out_date');
    const priceElement = document.getElementById('total_price');
    const roomPrice = parseFloat(document.getElementById('room_price').getAttribute('data-price') || 0);
    
    if (checkInInput && checkOutInput && priceElement) {
        function calculatePrice() {
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);
            
            if (checkIn && checkOut && checkOut > checkIn) {
                const timeDiff = checkOut.getTime() - checkIn.getTime();
                const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
                const totalPrice = nights * roomPrice;
                priceElement.textContent = totalPrice.toLocaleString('vi-VN') + ' VND';
            } else {
                priceElement.textContent = '0 VND';
            }
        }
        
        checkInInput.addEventListener('change', calculatePrice);
        checkOutInput.addEventListener('change', calculatePrice);
        
        // Initial calculation
        calculatePrice();
    }
}

// Form Validation
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    
                    // Remove error message if exists
                    const existingError = field.parentNode.querySelector('.error-message');
                    if (existingError) {
                        existingError.remove();
                    }
                    
                    // Add error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.textContent = 'This field is required.';
                    field.parentNode.appendChild(errorDiv);
                } else {
                    field.classList.remove('is-invalid');
                    const errorDiv = field.parentNode.querySelector('.error-message');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}

// Booking Confirmation
function confirmBooking() {
    return confirm('Are you sure you want to book this room?');
}

function confirmCancellation() {
    return confirm('Are you sure you want to cancel this booking? This action cannot be undone.');
}

function confirmPayment() {
    return confirm('Are you sure you want to proceed with the payment?');
}

// Alert Auto-hide
function initAlertAutoHide() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

// Smooth Scrolling
function initSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Loading Spinner
function showLoading() {
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    `;
    document.body.appendChild(spinner);
}

function hideLoading() {
    const spinner = document.querySelector('.loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

// Search Functionality
function initSearchFunctionality() {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    }
}

// Dropdown Menu Enhancement
function initDropdownEnhancement() {
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (toggle && menu) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                menu.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target)) {
                    menu.classList.remove('show');
                }
            });
        }
    });
}

// Image Gallery Enhancement
function initImageGallery() {
    const galleryImages = document.querySelectorAll('.room-image-gallery img');
    galleryImages.forEach(img => {
        img.addEventListener('click', function() {
            // Create lightbox effect
            const lightbox = document.createElement('div');
            lightbox.className = 'lightbox';
            lightbox.innerHTML = `
                <div class="lightbox-content">
                    <img src="${this.src}" alt="${this.alt}">
                    <button class="lightbox-close">&times;</button>
                </div>
            `;
            
            document.body.appendChild(lightbox);
            
            // Close lightbox
            lightbox.addEventListener('click', function() {
                this.remove();
            });
        });
    });
}

// Initialize all functions when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initStarRating();
    initPriceCalculation();
    initFormValidation();
    initAlertAutoHide();
    initSmoothScrolling();
    initSearchFunctionality();
    initDropdownEnhancement();
    initImageGallery();
});

// Utility Functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export functions for use in other scripts
window.HarborLights = {
    initStarRating,
    initPriceCalculation,
    initFormValidation,
    confirmBooking,
    confirmCancellation,
    confirmPayment,
    formatCurrency,
    formatDate,
    debounce
}; 