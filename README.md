# CapyWorld

Prosty prosty serwis galerii zdjęć "CapyWorld" przygotowany do uruchomienia w XAMPP (ustawić docroot na `public/`).

Struktura projektu:

- public/ - pliki publiczne (router, assets, uploads, strony)
- src/ - backend (konfiguracja, połączenie DB, autoryzacja, modele)
- sql/ - skrypty SQL (tworzenie tabel)
- tests/ - proste testy pomocnicze

Szybki start (Windows + XAMPP):

1. Skopiuj katalog `capyworld/public` do `C:\xampp\htdocs\capyworld` lub ustaw VirtualHost wskazujący na `public`.
2. Utwórz bazę danych (np. `capyworld`) i zaimportuj `sql/schema.sql`.
3. Skonfiguruj połączenie DB w `src/config.php`.
4. Otwórz `http://localhost/capyworld/` w przeglądarce.

Uwaga: folder `public/uploads` jest ignorowany przez Git (używany do uploadów użytkowników).