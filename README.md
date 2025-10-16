# Planning Agenda

Een lichte PHP-agenda geïnspireerd op Google Agenda met rollen voor **admin** en **instructeur**. De interface is gebouwd met Tailwind CSS en maakt het mogelijk om leerlingen in te plannen via een soepele modal workflow.

## Functies

- Inloggen met rolgebaseerde permissies (admin of instructeur).
- Weekkalender met Google Agenda-look & feel, inclusief drag-grid voor uren en dagen.
- Admin ziet de planning gegroepeerd per instructeur en kan afspraken voor iedere instructeur maken.
- Instructeurs zien alleen hun eigen afspraken en kunnen enkel voor zichzelf plannen.
- Snelle leerlingzoeker binnen de modal inclusief voertuig-, pakket-, contact- en locatie-informatie.
- SQLite database met eenvoudige migratie- en seed-scripts.

## Installatie

1. **Installeer PHP 8+** (CLI) in je omgeving.
2. Installeer de afhankelijkheden (Tailwind wordt via CDN geladen, er zijn dus geen npm stappen nodig).
3. Maak en seed de database:

   ```bash
   php database/migrate.php
   php database/seed.php
   ```

   Dit genereert `database/app.sqlite` met:
   - 1 admin gebruiker (admin@example.com / secret123)
   - 2 instructeurs (iris@example.com, bram@example.com / secret123)
   - 4 voorbeeldleerlingen
   - Een paar voorbeeldlessen voor de huidige week

4. Start de ingebouwde PHP server vanuit de `public` map:

   ```bash
   php -S localhost:8000 -t public
   ```

5. Bezoek [http://localhost:8000](http://localhost:8000) en log in met één van de seed-accounts.

## Structuur

```
app/
  bootstrap.php       # Globale helper functies en database bootstrap
  views/
    login.php         # Tailwind login scherm
    calendar.php      # Hoofdkalender met modal planner
public/
  index.php           # Front controller en routing (API + views)
database/
  migrate.php         # Maak/initialiseer SQLite schema
  seed.php            # Vul database met demo data
```

## Notities

- Alle data wordt opgeslagen in `database/app.sqlite`. Verwijder het bestand om met een lege database te starten.
- Pas gemakkelijk rollen of voorbeelddata aan door de seeders te wijzigen.
- Voor productie-omgevingen is het aan te raden wachtwoorden te wijzigen en validatie/toegang verder uit te breiden.
