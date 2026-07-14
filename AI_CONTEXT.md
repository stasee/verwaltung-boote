# Verwaltung Boote – Kontext für KI-gestützte Änderungen

Diese Datei ist die technische Arbeitsgrundlage für Änderungen am Plugin. Sie ist bei jeder funktionalen Änderung des Plugins mitzupflegen: insbesondere bei neuen Seiten, Rollen, Datenfeldern, Shortcodes, Abläufen und Versionsständen.

## Zweck

`Verwaltung Boote` verwaltet Vereinsboote eines Segelvereins. Vereinsmitglieder können Boote sehen, reservieren, ausleihen und zurückgeben. Dabei entstehen Logbucheinträge; Schäden werden getrennt und dauerhaft dokumentiert. Die Verwaltungsansicht läuft im Frontend für die Rolle **Bootswart**, nicht im WordPress-Backend.

## Einstiegspunkte

- Plugin-Datei und Versionsnummer: `verwaltung-boote.php`
- Zentrale Logik, Hooks, Shortcodes und Datenzugriffe: `includes/class-plugin.php`
- Frontend-Stile: `assets/css/frontend.css`
- Browser-Lokalisierung für Datum/Uhrzeit und Formulare: `assets/js/frontend.js`

Die Plugin-Hauptklasse ist `Verwaltung_Boote\Plugin`. Änderungen an Eingaben benötigen immer Validierung, Nonce-Prüfung, Berechtigungsprüfung, Sanitizing beim Speichern und Escaping bei der Ausgabe.

## Inhalte und Datenfelder

Custom Post Types:

- `vb_boot`: Boot. Genau ein Bootstyp über die Taxonomie `vb_bootstyp`; Liegeplatz als Freitext in `_vb_liegeplatz`; eindeutige, dauerhafte Boots-ID in `_vb_boots_id`.
- `vb_logbuch`: Ein abgeschlossener oder laufender Ausleihvorgang. Wichtige Metafelder: `_vb_boot_id`, `_vb_user_id`, `_vb_start`, `_vb_end`, `_vb_reservierung_id`, Angaben zu Schäden.
- `vb_reservierung`: Reservierung mit `_vb_boot_id`, `_vb_user_id`, `_vb_reservierung_start`, `_vb_reservierung_ende`. Stornierungen und abgeschlossene Nutzungen bleiben als Historie erhalten.
- `vb_schaden`: Schadensmeldung mit `_vb_boot_id`, `_vb_user_id`, `_vb_log_id`, `_vb_schwere`, `_vb_gemeldet_am`, sowie bei Behebung `_vb_behoben_am` und `_vb_behoben_von`. Der Versandstatus der Benachrichtigung liegt in `_vb_benachrichtigung_gesendet` und bei Erfolg der Zeitpunkt in `_vb_benachrichtigung_gesendet_am`.

Die erlaubten Schadensgrade sind:

- Boot voll benutzbar
- Boot eingeschränkt benutzbar
- Boot nicht benutzbar

## Rollen und Zugriff

- Angemeldete Mitglieder können die Seite **Nutzung Vereinsboote** verwenden.
- Nur Benutzer mit der WordPress-Rolle `bootswart` können die Bootswart-Seiten sehen. Administratoren erhalten die für Boote benötigten Bearbeitungsrechte, sind aber nicht automatisch in der Frontend-Rolle Bootswart.
- Bootswart-Seiten werden für andere Rollen aus klassischen Menüs und öffentlichen Listen ausgeblendet und beim direkten Aufruf mit HTTP 403 geschützt.

## Frontend-Seiten und Shortcodes

Mitgliederseite:

- `/bootsnutzung/` – **Nutzung Vereinsboote** – `[verwaltung_boote_liste]`
- `/boot-verwenden/?boot=<Boots-ID>` – Einstieg nach dem Scannen eines Boots-QR-Codes – `[verwaltung_boote_boot_einstieg]`

Bootswart-Bereiche (alle geschützt und mit `bootswart-`-Präfix):

- `/bootswart-verwaltung/` – Übersicht und Verweise – `[verwaltung_boote_bootswart]`
- `/bootswart-alle-boote/` – `[verwaltung_boote_bootswart_boote]`
- `/bootswart-alle-reservierungen/` – `[verwaltung_boote_bootswart_reservierungen]`
- `/bootswart-alle-nutzungen/` – `[verwaltung_boote_bootswart_nutzungen]`
- `/bootswart-alle-bootschaeden/` – `[verwaltung_boote_bootswart_schaeden]`
- `/bootswart-alle-nutzer/` – `[verwaltung_boote_bootswart_nutzer]`
- `/bootswart-qr-codes/` – lokale QR-Codes und Druckansicht – `[verwaltung_boote_bootswart_qr]`

Die Seiten werden durch `ensure_bootswart_pages()` erstellt bzw. bei Updates ergänzt. Ihre IDs liegen in der Option `verwaltung_boote_bootswart_pages`; die Übersichtsseite zusätzlich in `verwaltung_boote_bootswart_page_id`.

## Fachliche Regeln

- Ein Boot hat genau einen Bootstyp und einen Liegeplatz als Freitext.
- Bootslisten werden nach Bootstyp gruppiert, innerhalb einer Gruppe alphabetisch.
- Reservierungen haben Start und Ende; das Ende ist im Formular standardmäßig eine Stunde nach Beginn.
- Eine Nutzung aus Reservierung darf von einer Stunde vor bis 45 Minuten nach Reservierungsbeginn gestartet werden.
- Bei Rückgabe sind Nutzungsbeginn und -ende sichtbar, aber nicht editierbar. Die Schadensfrage ist standardmäßig **Nein**.
- Bei Rückgabe einer reservierten Nutzung wird auch die zugehörige Reservierung als beendet markiert.
- Stornierte oder vergangene Reservierungen werden nicht gelöscht.
- Bootsschäden bleiben nach der Markierung als behoben gespeichert; offene Schäden stehen in der Bootswart-Liste zuerst.
- Die Bootswart-Seiten „Alle Nutzungen“, „Alle Reservierungen“ und „Alle Bootsschäden“ enthalten jeweils einen browserseitigen Freitextfilter über alle sichtbaren Angaben eines Eintrags.
- Beim Melden eines Schadens erhalten alle Benutzer mit der Rolle `bootswart` eine E-Mail. Sie enthält Boot, Typ, Liegeplatz, meldendes Mitglied, Zeitpunkt, Nutzungszeiten, Reservierungsbezug, Logbuchreferenz, Schwere und Beschreibung.
- Zustandsverändernde Bootswart-Aktionen (Stornieren, Nutzung beenden, Schaden beheben) dürfen nur als `POST`-Anfragen mit Nonce und Berechtigungsprüfung umgesetzt werden.
- Jedes Boot hat eine eindeutige, URL-taugliche und dauerhafte Boots-ID. Ihr Standardwert wird aus dem Bootsnamen ohne Leerzeichen oder Sonderzeichen gebildet und enthält nur Kleinbuchstaben und Ziffern, z. B. `H-Boot 1` → `hboot1`. Sie kann in der Bootsmaske gepflegt werden. Nach dem Drucken der QR-Codes darf sie nicht mehr geändert werden.
- Jeder Boots-QR-Code verweist auf die einzelne Seite `/boot-verwenden/` mit der aktuellen Boots-ID als Query-Parameter. QR-Codes aus den Versionen 1.5.0 und 1.5.1 werden nicht unterstützt. Der Code berechtigt nicht zur Nutzung; Anmeldung, Nonce und Berechtigungen bleiben erforderlich. Die QR-Codes werden lokal mit `assets/js/qrcode.min.js` erzeugt; die zugehörige MIT-Lizenz liegt in `LICENSE-qrcodejs.txt`.
- Datum und Uhrzeit werden im bevorzugten Format des Browsers ausgegeben. Die gespeicherten Zeitpunkte sind UTC.

## Aktueller Stand

Plugin-Version: **1.7.1**

Reservierungen können einen optionalen Freitextgrund im Metafeld `_vb_reservierung_grund` enthalten. Der Text ist auf 100 Zeichen begrenzt und wird in allen Reservierungsansichten sowie im Bezug einer Mitgliedsnachricht angezeigt.

Namen reservierender Mitglieder werden überall dort als Link auf `/mitglied-nachricht-schreiben/` ausgegeben, wo eine Reservierung dargestellt wird und das Mitglied eine gültige E-Mail-Adresse hinterlegt hat. Das Formular ermittelt den Empfänger ausschließlich aus der Reservierungs-ID, zeigt die E-Mail-Adresse nicht an und versendet die Nachricht Nonce-geschützt über `wp_mail()`.

Die Bootswart-Verwaltung wurde in eine Übersichtsseite mit fünf getrennten Verwaltungsseiten für Boote, Reservierungen, Nutzungen, Schäden und Nutzer aufgeteilt.

Neue Schadensmeldungen lösen zusätzlich eine E-Mail-Benachrichtigung an alle Bootswarte aus.

Abgeschlossene Datenmigrationen aus früheren Plugin-Versionen sind entfernt; Liegeplätze werden ausschließlich als Freitext und Bootstypen ausschließlich als Einzelauswahl geführt.

Die Bootswart-Seite „Alle Nutzer“ zeigt alle Personen mit mindestens einer Bootsnutzung und deren Nutzungsanzahl.

Die QR-Code-Druckseite für den Bootswart erzeugt Codes lokal im Browser und überträgt keine Daten an einen externen QR-Dienst.

QR-Codes verwenden die dauerhafte Boots-ID statt der internen WordPress-Beitrags-ID.

Die Listen „Alle Nutzungen“, „Alle Reservierungen“ und „Alle Bootsschäden“ lassen sich jeweils über ein Freitext-Suchfeld filtern.

## Plugin-Paket für andere WordPress-Installationen

Die Installationsdatei heißt `verwaltung-boote.zip`. Sie muss im Archiv die Hauptdatei unter `verwaltung-boote/verwaltung-boote.php` enthalten.

Wichtig: Die Einträge im ZIP-Archiv müssen Vorwärts-Schrägstriche (`/`) als Pfadtrenner verwenden, niemals Windows-Rückwärts-Schrägstriche (`\`). Andernfalls legt WordPress beim Upload Dateien mit dem Ordnernamen im Dateinamen an und kann das Plugin nicht aktivieren. Die ZIP daher mit einer ZIP-Bibliothek erzeugen, die explizite Archivpfade wie `verwaltung-boote/includes/class-plugin.php` schreibt.

## Prüfungen nach Änderungen

- Bei PHP-Änderungen Plugin-Status und Laden mit `studio wp` prüfen (niemals `wp` ohne `studio`).
- Nach Änderungen an Seiten oder Shortcodes die zugehörigen Inhalte und Zugriffsrechte als Mitglied und als Bootswart prüfen.
- Bei Änderungen am Datenmodell Migrationspfad für bestehende Einträge vorsehen.
- Diese Datei und bei einer Versionserhöhung auch `readme.txt` aktualisieren.
- Vor einer Veröffentlichung die ZIP-Struktur prüfen: `verwaltung-boote/verwaltung-boote.php` muss als eigener Archivpfad enthalten sein.
