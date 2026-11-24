document.addEventListener('DOMContentLoaded', function () {
    const DRAFT_KEY = 'mbo_cinemas_reservation_draft';

    const confirmedBox = document.getElementById('reservation-confirmed');
    if (confirmedBox && confirmedBox.dataset.clearDraft === 'true') {
        localStorage.removeItem(DRAFT_KEY);
    }

    const banner = document.getElementById('unfinished-reservation-banner');
    const resumeLink = document.getElementById('resume-reservation-link');
    const discardBtn = document.getElementById('discard-draft-btn');
    if (banner && resumeLink) {
        const saved = localStorage.getItem(DRAFT_KEY);
        if (saved) {
            try {
                const draft = JSON.parse(saved);
                if (draft && draft.screening_id) {
                    resumeLink.href = '/reserve.php?screening_id=' + encodeURIComponent(draft.screening_id);
                    banner.classList.remove('hidden');
                }
            } catch (e) {
                localStorage.removeItem(DRAFT_KEY);
            }
        }
        if (discardBtn) {
            discardBtn.addEventListener('click', function () {
                localStorage.removeItem(DRAFT_KEY);
                banner.classList.add('hidden');
            });
        }
    }

    const searchInput = document.getElementById('filter-search');
    const genreSelect = document.getElementById('filter-genre');
    const locationSelect = document.getElementById('filter-location');
    const dateInput = document.getElementById('filter-date');
    const movieCards = document.querySelectorAll('.movie-card');

    function filterMovies() {
        const search = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const genre = genreSelect ? genreSelect.value : '';
        const location = locationSelect ? locationSelect.value : '';
        const date = dateInput ? dateInput.value : '';

        movieCards.forEach(function (card) {
            const matchTitle = search === '' || card.dataset.title.includes(search);
            const matchGenre = genre === '' || card.dataset.genre === genre;
            const matchLocation = location === '' || card.dataset.location === location;
            const matchDate = date === '' || card.dataset.date === date;

            if (matchTitle && matchGenre && matchLocation && matchDate) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    }

    if (movieCards.length > 0) {
        if (searchInput) searchInput.addEventListener('input', filterMovies);
        if (genreSelect) genreSelect.addEventListener('change', filterMovies);
        if (locationSelect) locationSelect.addEventListener('change', filterMovies);
        if (dateInput) dateInput.addEventListener('change', filterMovies);
    }

    const reservationForm = document.getElementById('reservation-form');
    if (reservationForm) {
        const screeningId = reservationForm.dataset.screeningId;
        const nameInput = document.getElementById('customer_name');
        const emailInput = document.getElementById('customer_email');
        const phoneInput = document.getElementById('customer_phone');
        const seatsInput = document.getElementById('seats');
        const priceDisplay = document.getElementById('total-price-display');

        const saved = localStorage.getItem(DRAFT_KEY);
        if (saved) {
            try {
                const draft = JSON.parse(saved);
                if (draft && String(draft.screening_id) === String(screeningId)) {
                    if (draft.name && nameInput.value === '') nameInput.value = draft.name;
                    if (draft.email && emailInput.value === '') emailInput.value = draft.email;
                    if (draft.phone) phoneInput.value = draft.phone;
                    if (draft.seats) seatsInput.value = draft.seats;
                }
            } catch (e) {
                localStorage.removeItem(DRAFT_KEY);
            }
        }

        function updateTotalPrice() {
            const count = parseInt(seatsInput.value, 10) || 0;
            const total = (count * 11.50).toFixed(2).replace('.', ',');
            priceDisplay.textContent = '\u20AC' + total;
        }

        function saveDraft() {
            const draft = {
                screening_id: screeningId,
                name: nameInput.value,
                email: emailInput.value,
                phone: phoneInput.value,
                seats: seatsInput.value
            };
            localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
        }

        [nameInput, emailInput, phoneInput, seatsInput].forEach(function (input) {
            input.addEventListener('input', function () {
                saveDraft();
                if (input === seatsInput) {
                    updateTotalPrice();
                }
            });
        });

        updateTotalPrice();

        reservationForm.addEventListener('submit', function (e) {
            const seats = parseInt(seatsInput.value, 10);
            const maxSeats = parseInt(seatsInput.max, 10);
            if (nameInput.value.trim() === '' || emailInput.value.trim() === '' || phoneInput.value.trim() === '') {
                e.preventDefault();
                alert('Vul alle verplichte velden in.');
                return;
            }
            if (isNaN(seats) || seats < 1 || seats > maxSeats) {
                e.preventDefault();
                alert('Kies een geldig aantal kaartjes (maximaal ' + maxSeats + ').');
                return;
            }
            if (!confirm('Weet u zeker dat u ' + seats + ' kaartje(s) wilt reserveren?')) {
                e.preventDefault();
            }
        });
    }

    document.querySelectorAll('.confirm-cancel').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm('Weet u zeker dat u deze actie wilt uitvoeren?')) {
                e.preventDefault();
            }
        });
    });
});
