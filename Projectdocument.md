# Projectdocument — MBO Cinemas

## 1. Sitemap

- `/` (`index.php`) — Homepage met filmaanbod en live filters (zoekterm, genre, bioscoop, datum)
- `/reserve.php` — Filmdetails, voorstellingstijden, reserveringsformulier en directe bevestiging
- `/my-reservations.php` — Reservering opzoeken op code en eventueel annuleren
- `/login.php` — Inlogpagina voor medewerkers
- `/logout.php` — Uitloggen voor medewerkers
- `/admin/index.php` — Beheerdersdashboard: reserveringsoverzicht (filterbaar per bioscoop, aantal kaartjes aanpassen/annuleren) en accountbeheer (wachtwoord wijzigen)
- `/admin/movies.php` — Films toevoegen, bewerken en verwijderen
- `/admin/screenings.php` — Voorstellingen inplannen per zaal en verwijderen

## 2. User Stories

### Bezoeker / Klant
1. Als bezoeker wil ik op de homepage alle films zien en kunnen filteren op zoekterm, genre, bioscoop en datum, zodat ik snel een geschikte film vind.
2. Als bezoeker wil ik op `reserve.php` de filminformatie, beschikbare tijden en vrije stoelen zien, zodat ik direct een voorstelling kan kiezen.
3. Als bezoeker wil ik een reservering kunnen plaatsen met mijn naam, e-mail, telefoonnummer en aantal kaartjes, en direct op dezelfde pagina mijn reserveringscode en bevestiging zien.
4. Als bezoeker wil ik dat mijn contactgegevens via cookies worden onthouden en een half afgeronde reservering via `localStorage` bewaard blijft, zodat ik niet alles opnieuw hoef in te vullen.
5. Als bezoeker wil ik via `my-reservations.php` mijn reservering kunnen opzoeken met mijn reserveringscode en deze indien nodig kunnen annuleren.

### Medewerker / Beheerder
6. Als medewerker wil ik veilig kunnen inloggen via `login.php` met sessiebeveiliging.
7. Als medewerker wil ik in `admin/index.php` alle reserveringen zien, kunnen filteren per bioscoop, het aantal kaartjes aanpassen of een reservering annuleren.
8. Als medewerker wil ik onderaan `admin/index.php` mijn wachtwoord kunnen wijzigen.
9. Als medewerker wil ik in `admin/movies.php` films kunnen beheren en in `admin/screenings.php` voorstellingen kunnen inplannen.

## 3. Paginabeschrijvingen (Wireframes)

### `index.php` (Homepage)
- **Header**: Logo + navigatie (`Films`, `Mijn Reserveringen`, `Medewerkers` of `Beheer`).
- **Filterbalk**: Zoekveld, dropdown `Genre`, dropdown `Bioscoop`, datumkiezer `Datum`, knop `Filteren`. Live filtering via Vanilla JS.
- **Filmoverzicht**: Grid met filmkaarten (poster, genre-badge, speelduur, titel, korte beschrijving, knop `Bekijk tijden & Reserveer` naar `reserve.php?movie_id=...`).

### `reserve.php` (Filmdetails, Reserveren & Bevestiging)
- **Filminfo**: Poster, genre, speelduur, titel en volledige beschrijving.
- **Voorstellingen**: Knoppen met locatie, datum en tijd om een voorstelling te kiezen; toont zaal en beschikbare plaatsen.
- **Reserveringsformulier**: Aantal kaartjes, naam, e-mailadres, telefoonnummer, live totaalprijs en verzendknop. Ondersteunt `localStorage`-herstelbanner en cookies.
- **Bevestiging (na POST)**: Vervangt het formulier direct door een bevestigingsoverzicht met de unieke reserveringscode (`MBO-XXXXXX`) en alle gegevens.

### `my-reservations.php` (Mijn Reserveringen)
- **Zoekformulier**: Invoerveld voor de reserveringscode.
- **Resultaat**: Overzicht van de gevonden reservering met mogelijkheid om te annuleren.

### `admin/index.php` (Beheerdersdashboard & Accountbeheer)
- **Admin-navigatie**: Links naar `Reserveringen`, `Films Beheren` en `Zaalplanning`.
- **Bioscoopfilter**: Dropdown om reserveringen per vestiging te filteren.
- **Reserveringstabel**: HTML-tabel met code, klant, film, bioscoop/zaal, datum/tijd, inline formulier om aantal kaartjes aan te passen, statusbadge en annuleerknop.
- **Accountbeheer (onderaan)**: Formulier met huidig wachtwoord, nieuw wachtwoord en bevestiging.
