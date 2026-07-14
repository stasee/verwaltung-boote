=== Verwaltung Boote ===
Contributors: website-administration
Requires at least: 6.9
Requires PHP: 7.2.24
Stable tag: 1.7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Verwaltung von Motor- und Segelbooten fuer einen Segelverein.

== Description ==

Das Plugin verwaltet Boote mit Bootstyp und Liegeplatz. Die Rolle Bootswart kann
Bootsdaten pflegen. Angemeldete Vereinsmitglieder sehen die Liste ueber den
Shortcode [verwaltung_boote_liste].

== Changelog ==

= 1.7.2 =
* Reservierungslisten nach Reservierungsbeginn absteigend sortiert.

= 1.7.1 =
* Reservierungsgrund im Formular und serverseitig auf 100 Zeichen begrenzt.

= 1.7.0 =
* Optionalen Freitextgrund beim Reservieren ergänzt.
* Reservierungsgrund in allen Reservierungsansichten und im Nachrichtenbezug sichtbar gemacht.

= 1.6.0 =
* Reservierende Mitglieder mit hinterlegter E-Mail-Adresse können über ihre verlinkten Namen kontaktiert werden.
* Geschützte Nachrichtenseite ergänzt; die Empfängeradresse bleibt verborgen.

= 1.5.5 =
* Freitextfilter auf den Bootswart-Seiten „Alle Reservierungen“ und „Alle Bootsschäden“ ergänzt.

= 1.5.4 =
* Die Bootswart-Seite „Alle Nutzungen“ um einen Freitextfilter erweitert.

= 1.5.3 =
* Kompatibilitaet mit QR-Codes aus den Versionen 1.5.0 und 1.5.1 entfernt; QR-Codes werden ausschliesslich ueber die aktuelle Boots-ID aufgeloest.

= 1.5.2 =
* Standard-Boots-IDs werden ohne Leerzeichen oder Sonderzeichen aus dem Bootsnamen gebildet, z. B. `H-Boot 1` zu `hboot1`.

= 1.5.1 =
* Jedes Boot erhaelt eine eindeutige, dauerhafte Boots-ID; QR-Codes verwenden diese Kennung statt der WordPress-Beitrags-ID.

= 1.5.0 =
* Lokale QR-Codes mit gemeinsamer Boot-Einstiegsseite und geschützter Druckansicht für den Bootswart ergänzt.

= 1.4.5 =
* Zustandsverändernde Bootswart-Aktionen auf geschützte POST-Anfragen umgestellt.

= 1.4.4 =
* Neue Bootswart-Seite mit allen Nutzern der Boote und ihrer jeweiligen Nutzungsanzahl ergänzt.

= 1.4.3 =
* Abgeschlossene Migrationen für Liegeplätze und mehrere Bootstypen entfernt; die alte Liegeplatz-Taxonomie wird nicht mehr registriert.

= 1.4.2 =
* Nicht mehr benötigte Migration für alte Reservierungen aus dem Papierkorb entfernt.

= 1.4.1 =
* Bootswarte werden bei einer neuen Schadensmeldung per E-Mail informiert.

= 1.4.0 =
* Bootswart-Verwaltung in eine Übersichtsseite mit getrennten Seiten für Boote, Reservierungen, Nutzungen und Bootsschäden aufgeteilt.

= 1.3.2 =
* Bootslisten nach Bootstyp gruppiert und innerhalb der Gruppen alphabetisch sortiert.

= 1.3.1 =
* Liste aller Boote auf der Bootswart-Verwaltung ergaenzt.

= 1.3.0 =
* Alte Liegeplatzliste entfernt und Bootstyp auf genau eine Auswahl beschraenkt.

= 1.2.0 =
* Liegeplaetze von einer festen Liste auf ein Freitextfeld je Boot umgestellt.

= 1.1.1 =
* Verknuepfte Reservierungen werden beim Beenden der Nutzung ebenfalls abgeschlossen.

= 1.1.0 =
* Reservierungen werden dauerhaft historisiert und im Logbuch referenziert.

= 1.0.3 =
* Bootsschaeden nach offenem Status und anschliessend nach Erstellungsdatum sortiert.

= 1.0.2 =
* Meldedatum in der Bootsschaeden-Tabelle der Bootswart-Seite ergaenzt.

= 1.0.1 =
* Alle Tabellen ohne horizontales Scrollen auf die verfuegbare Breite eingepasst.

= 1.0.0 =
* Eigene geschuetzte Frontend-Verwaltungsseite fuer den Bootswart ergaenzt.

= 0.9.0 =
* Bootswart kann laufende Nutzungen beenden und Schaeden dauerhaft als behoben markieren.

= 0.8.0 =
* Datums- und Zeitangaben werden nach den bevorzugten Browserformaten dargestellt.

= 0.7.4 =
* Heutige Reservierungen mit Mitglied und Uhrzeit direkt in der Bootsliste angezeigt.

= 0.7.3 =
* Name des aktuell ausleihenden Mitglieds in der Bootsliste ergaenzt.

= 0.7.2 =
* Ueberschrift Liste der Boote oberhalb der Bootstabelle ergaenzt.

= 0.7.1 =
* Schadensfrage bei der Rueckgabe standardmaessig auf Nein gesetzt.

= 0.7.0 =
* Reservierungssystem mit Zeitfenster, Mitgliederansicht und Verwaltungsliste ergaenzt.

= 0.6.1 =
* Frontend-Tabellen kompakter und auf kleinen Bildschirmen vollstaendig scrollbar gestaltet.

= 0.6.0 =
* Bootsschaeden-Liste um Schadensschwere, Kommentar, Ausleihenden und Logbuchreferenz erweitert.

= 0.5.4 =
* Persoenliche Ausleihhistorie unter die Bootsliste verschoben.

= 0.5.3 =
* Seitenadresse von bootsliste auf bootsnutzung geaendert.

= 0.5.2 =
* Seite Bootsliste in Nutzung Vereinsboote umbenannt.

= 0.5.1 =
* Schreibgeschuetzte persoenliche Ausleihhistorie fuer Mitglieder ergaenzt.

= 0.5.0 =
* Logbuchuebersicht und Detailansicht zeigen den vollstaendigen Ausleih- und Rueckgabevorgang.

= 0.4.1 =
* Rueckgabeformular mit nicht editierbarem Nutzungsbeginn und Nutzungsende ergaenzt.

= 0.4.0 =
* Schadensabfrage bei Rueckgabe und gesonderte Bootsschaeden-Liste ergaenzt.

= 0.3.0 =
* Ausleihe mit Start, Ende, aktueller Nutzung und privatem Logbuch ergaenzt.

= 0.2.1 =
* Liegeplatz auf eine einzelne Auswahl pro Boot beschraenkt.

= 0.2.0 =
* Bootsverwaltung, Rolle Bootswart und geschuetzte Mitgliederliste ergaenzt.

= 0.1.0 =
* Erstes Plugin-Grundgeruest.
