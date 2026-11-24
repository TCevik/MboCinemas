// Wacht tot de volledige HTML-pagina is geladen voordat JavaScript wordt uitgevoerd
document.addEventListener('DOMContentLoaded', function () {
    // Sleutel waaronder een niet-afgeronde reservering in localStorage wordt bewaard
    const DRAFT_KEY = 'mbo_cinemas_reservation_draft';

    // 1. CONCEPT WISSEN NA BEVESTIGING
    // Als de reservering is bevestigd (reserve.php), verwijder dan het opgeslagen concept
    const confirmedBox = document.getElementById('reservation-confirmed');
    if (confirmedBox && confirmedBox.dataset.clearDraft === 'true') {
        localStorage.removeItem(DRAFT_KEY);
    }

    // 2. BANNER VOOR NIET-AFGERONDE RESERVERING (index.php)
    // Controleert of er nog een concept in localStorage staat en toont zo ja de hervat-banner
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
                // Verwijder corrupte data uit localStorage
                localStorage.removeItem(DRAFT_KEY);
            }
        }
        // Knop om het opgeslagen concept handmatig te wissen
        if (discardBtn) {
            discardBtn.addEventListener('click', function () {
                localStorage.removeItem(DRAFT_KEY);
                banner.classList.add('hidden');
            });
        }
    }

    // 3. LIVE FILTEREN VAN FILMS (index.php)
    // Filtert de filmkaarten direct in de browser zonder de pagina opnieuw te laden
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

            // Toon de kaart alleen als alle actieve filters overeenkomen
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

    // 4. RESERVERINGSFORMULIER: CONCEPT OPSLAAN, TOTAALPRIJS & VALIDATIE (reserve.php)
    const reservationForm = document.getElementById('reservation-form');
    if (reservationForm) {
        const screeningId = reservationForm.dataset.screeningId;
        const nameInput = document.getElementById('customer_name');
        const emailInput = document.getElementById('customer_email');
        const phoneInput = document.getElementById('customer_phone');
        const seatsInput = document.getElementById('seats');
        const priceDisplay = document.getElementById('total-price-display');

        // Herstel eerder ingevulde gegevens als het concept bij deze voorstelling hoort
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

        // Berekent en toont de totaalprijs live (aantal kaartjes * €11,50)
        function updateTotalPrice() {
            const count = parseInt(seatsInput.value, 10) || 0;
            const total = (count * 11.50).toFixed(2).replace('.', ',');
            priceDisplay.textContent = '\u20AC' + total;
        }

        // Slaat de huidige invoer tussentijds op in localStorage
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

        // Luister naar wijzigingen in alle invoervelden
        [nameInput, emailInput, phoneInput, seatsInput].forEach(function (input) {
            input.addEventListener('input', function () {
                saveDraft();
                if (input === seatsInput) {
                    updateTotalPrice();
                }
            });
        });

        updateTotalPrice();

        // Client-side validatie en bevestigingspop-up bij het versturen van het formulier
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

    // 5. BEVESTIGINGSPOP-UP VOOR VERWIJDEREN / ANNULEREN
    // Voorkomt dat bezoekers of medewerkers per ongeluk iets verwijderen of annuleren
    document.querySelectorAll('.confirm-cancel').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm('Weet u zeker dat u deze actie wilt uitvoeren?')) {
                e.preventDefault();
            }
        });
    });
});
