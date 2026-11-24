document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-movie');
    const genreSelect = document.getElementById('filter-genre');
    const locationSelect = document.getElementById('filter-location');
    const dateInput = document.getElementById('filter-date');
    const movieCards = document.querySelectorAll('.movie-card-item');
    const noMoviesFound = document.getElementById('no-movies-found');

    if (movieCards.length > 0) {
        const filterMovies = () => {
            const search = (searchInput ? searchInput.value : '').trim().toLowerCase();
            const genre = genreSelect ? genreSelect.value : '';
            const location = locationSelect ? locationSelect.value : '';
            const date = dateInput ? dateInput.value : '';
            let visible = 0;

            movieCards.forEach(card => {
                const title = card.dataset.title || '';
                const cardGenre = card.dataset.genre || '';
                const locations = card.dataset.locations ? card.dataset.locations.split(',') : [];
                const dates = card.dataset.dates ? card.dataset.dates.split(',') : [];

                const matchSearch = !search || title.includes(search);
                const matchGenre = !genre || cardGenre === genre;
                const matchLocation = !location || locations.includes(location);
                const matchDate = !date || dates.includes(date);

                if (matchSearch && matchGenre && matchLocation && matchDate) {
                    card.style.display = 'flex';
                    visible++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (noMoviesFound) {
                noMoviesFound.style.display = visible === 0 ? 'block' : 'none';
            }
        };

        [searchInput, genreSelect, locationSelect, dateInput].forEach(el => {
            if (el) {
                el.addEventListener('input', filterMovies);
                el.addEventListener('change', filterMovies);
            }
        });
    }

    const confirmationBox = document.getElementById('confirmation-box');
    if (confirmationBox && confirmationBox.dataset.confirmed === 'true') {
        localStorage.removeItem('mbo_pending_reservation');
    }

    const resForm = document.getElementById('reservation-form');
    if (resForm) {
        const seatsInput = document.getElementById('seats');
        const nameInput = document.getElementById('customer_name');
        const emailInput = document.getElementById('customer_email');
        const phoneInput = document.getElementById('customer_phone');
        const totalPriceEl = document.getElementById('total-price');
        const banner = document.getElementById('pending-reservation-banner');
        const restoreBtn = document.getElementById('restore-draft-btn');
        const dismissBtn = document.getElementById('dismiss-draft-btn');
        const ticketPrice = parseFloat(resForm.dataset.ticketPrice || '11.50');
        const screeningId = resForm.dataset.screeningId;

        const updateTotal = () => {
            if (!totalPriceEl || !seatsInput) return;
            const seats = Math.max(1, parseInt(seatsInput.value, 10) || 1);
            totalPriceEl.textContent = '€ ' + (seats * ticketPrice).toFixed(2).replace('.', ',');
        };

        const getCookie = (name) => {
            const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
            return match ? decodeURIComponent(match[2]) : null;
        };

        const setCookie = (name, value, days) => {
            const expires = new Date(Date.now() + days * 86400000).toUTCString();
            document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;
        };

        const savedCookie = getCookie('mbo_customer_data');
        if (savedCookie) {
            try {
                const data = JSON.parse(savedCookie);
                if (nameInput && !nameInput.value && data.name) nameInput.value = data.name;
                if (emailInput && !emailInput.value && data.email) emailInput.value = data.email;
                if (phoneInput && !phoneInput.value && data.phone) phoneInput.value = data.phone;
            } catch (e) {}
        }

        const savedDraft = localStorage.getItem('mbo_pending_reservation');
        if (savedDraft && banner) {
            try {
                const draft = JSON.parse(savedDraft);
                if (draft.screeningId === screeningId) {
                    banner.style.display = 'flex';
                    if (restoreBtn) {
                        restoreBtn.addEventListener('click', () => {
                            if (seatsInput && draft.seats) seatsInput.value = draft.seats;
                            if (nameInput && draft.name) nameInput.value = draft.name;
                            if (emailInput && draft.email) emailInput.value = draft.email;
                            if (phoneInput && draft.phone) phoneInput.value = draft.phone;
                            updateTotal();
                            banner.style.display = 'none';
                        });
                    }
                    if (dismissBtn) {
                        dismissBtn.addEventListener('click', () => {
                            localStorage.removeItem('mbo_pending_reservation');
                            banner.style.display = 'none';
                        });
                    }
                }
            } catch (e) {}
        }

        const saveDraft = () => {
            localStorage.setItem('mbo_pending_reservation', JSON.stringify({
                screeningId,
                seats: seatsInput ? seatsInput.value : 1,
                name: nameInput ? nameInput.value : '',
                email: emailInput ? emailInput.value : '',
                phone: phoneInput ? phoneInput.value : ''
            }));
        };

        [seatsInput, nameInput, emailInput, phoneInput].forEach(el => {
            if (el) {
                el.addEventListener('input', () => {
                    updateTotal();
                    saveDraft();
                });
            }
        });

        resForm.addEventListener('submit', (e) => {
            const name = nameInput ? nameInput.value.trim() : '';
            const email = emailInput ? emailInput.value.trim() : '';
            const phone = phoneInput ? phoneInput.value.trim() : '';
            const seats = seatsInput ? parseInt(seatsInput.value, 10) : 0;

            if (!name || !email || !phone || seats < 1) {
                e.preventDefault();
                alert('Vul alle velden correct in.');
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                e.preventDefault();
                alert('Vul een geldig e-mailadres in.');
                return;
            }

            setCookie('mbo_customer_data', JSON.stringify({ name, email, phone }), 30);
        });

        updateTotal();
    }
});
