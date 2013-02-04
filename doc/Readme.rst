Fachgebiete
===========

Importiert Daten aus Fachhierarchien und zeigt sie in einem
Content-Element an. Die Fachhierarchien können in einer CSV-Datei
hinterlegt werden. Für die SUB Göttingen ist ein automatischer Import
aus dem OPAC implementiert.

Die Hierarchien können als Baum, Spalten oder Menüs dargestellt werden.
Es gibt Scheduler Tasks, um die benötigten Daten automatisch neu zu
importieren.

Grundeinstellungen im Extension Manager
---------------------------------------

Im TYPO3 Extension-Manager gibt es zwei Grundeinstellungen für die
Extension:

1. OPAC Base URL with trailing /: Aus dieser URL werden die Links in den
   OPAC gebaut. [Standardwert: https://opac.sub.uni-goettingen.de/DB=1/]
2. replace included CSS with path: Pfad einer eigenen CSS-Datei für die
   Baum- und Menüdarstellung [Standardwert: leer, die CSS-Datei der
   Extension wird genutzt]
3. URLs of CSV files to download: Liste von URLs, von denen CSV Dateien
   geladen werden. Einträge der Liste sind durch Leerzeichen getrennt.
   Die einzelnen zu ladenden Dateien müssen unterschiedliche Namen
   haben. [Standardwer: leer]

Einstellungen für das Content Element
-------------------------------------

Jedes Content-Element mit dem Plug-In hat drei
Einstellungsmöglichkeiten:

1. Startknoten: Der Startknoten kann auf zwei Arten festgelegt werden:

   1. Fachhierarchie beginnen mit: Knoten der oberen 2 Ebenen der
      Hierarchie auswählbar
   2. Notation(en) für die Startknoten angeben: Eine durch Komma
      getrennte Liste der Notationen für die Anzeige eingeben

2. Anzeigestil:

   1. Baum – hierarchische Baumstruktur
   2. Spalten – hierarchische Struktur als Spalten abgebildet. Nach
      Auswahl eines Themengebietes, werden dessen Untergebiete in einer
      neuen Spalte angezeigt.
   3. Menüs – es erscheint ein Menü mit den Untergebieten. Nach Auswahl
      eines Menüpunktes erscheint ein weiteres Menü mit den
      Untergebieten des ausgewählten Faches.

3. Notation zusammen mit der Beschreibung anzeigen: hiermit wird die
   Anzeige der zum Fachgebiet gehörenden Notation wie ‘IA 663’
   gesteuert.

Weitere Einstellungsmöglichkeiten mit TypoScript
``plugin.tx_nkwgok_pi1.``

-  ``shallowSearch`` konfiguriert die Art der Kataloglinks, die im
   Anzeigestil ‘Baum’ verwendet werden:

   -  0 [Standard]: Suche nach Büchern aller Kindelemente des
      Normdatensatzes
   -  1: Suche nach Büchern speziell zu dieser Notation

-  ``menuInlineThreshold`` [2]: Hat ein Element im Anzeigestil ‘Menü’
   höchstens so viele Kindelemente, werden diese Kindelemente direkt im
   Menü der übergeordneten Ebene angezeigt.

Datenimport im Scheduler
------------------------

Die Extension kann aus zwei Quellen Fachhierarchien importieren:

-  Durch Auslesen von Normdatensätzen (Pica Typen Tev und Tov) im
   XML-Format aus dem OPAC
-  Über CSV-Dateien mit den zu importierenden Informationen

Hierfür gibt es verschiedene TYPO3 Scheduler Tasks, die die notwendigen
Schritte durchführen.

Beim Ausführen der Tasks auftretende Fehler werden in das TYPO3
Developer Log geschrieben.

1+2+3: Fachhierarchiedaten laden, konvertieren und importieren
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Dieser Scheduler Task führt die anderen drei Scheduler Tasks in der
benötigten Reihenfolge aus:

1. Normdatensätze für Themengebiete aus OPAC laden
2. CSV Dateien zu XML konvertieren
3. Fachhierarchie aus XML Dateien importieren

Im regulären Betrieb sollte dieser Task regelmäßig, z.B. einmal
wöchentlich, ausgeführt werden. Wegen der vielen Zugriffe auf OPAC und
TYPO3 Datenbank, erscheint eine Ausführung außerhalb der starken
Nutzungszeiten sinnvoll.

auto 2+3: Prüfen, ob die CSV Dateien aktualisiert wurden und konvertieren/reimportieren wenn sie es sind
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Dieser Scheduler Task prüft, ob es zu allen CSV Dateien in
``fileadmin/gok/csv/`` entsprechende XML Dateien in
``fileadmin/gok/xml/`` mit einem neueren Änderungsdatum gibt. Ist dies
nicht der Fall, werden die CSV Dateien erneut konvertiert und alle XML
Daten neu importiert.

Dieser Scheduler Task kann häufig aufgerufen werden, damit Änderungen an
den CSV Dateien schnell auf der Seite verfügbar sind.

2+3: CSV Dateien konvertieren und alle XML Dateien neu importieren
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Dieser Scheduler Task führt nur die bei einer Aktualisierung der CSV
Dateien nötigen Schritte aus:

1. CSV Dateien laden und zu XML konvertieren
2. XML Dateien importieren

1: Normdatensätze für Themengebiete aus OPAC laden
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Dieser Scheduler Task lädt die Normdatensätze sowie die Anzahl der
Treffer pro GOK, BRK und MSC Notation aus dem OPAC.

Die Abfragen für die Normdaten sind ``MAK tev NOT LKL p*`` und
``MAK tov``, also alle GOK Normdatensätze außer denen, deren GOK mit P
beginnt und alle BRK Normdatensätze. Der Bereich P der GOK (Geschichte)
liegt in einer verfeinerten Fassung as CSV Datei vor (Ansprechpartner
hierfür ist Herr Enderle).

Die geladenen Daten sind im XML-Format des OPAC (URL-Optionen
``XML=1/PRS=XML/XMLSAVE=N/``) und enthalten Pica-Daten. Sie werden im
Ordner ``fileadmin/gok/xml/`` abgelegt. Der Inhalt dieses Ordners wird
beim Start des Scheduler Tasks gelöscht.

Die Abfrage der Trefferzahlen geschieht über ein Browsing der LKL (für
GOK), BRK und MSC Indexe. Die resultierenden XML Dateien werden im
Ordner ``fileadmin/gok/hitcounts/`` abgelegt. Der Inhalt dieses Ordners
wird beim Start des Scheduler Tasks gelöscht.

2: CSV Dateien zu XML konvertieren
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Dieser Scheduler Task konvertiert spezielle CSV-Dateien mit
Fachinhierarchie Informationen in das Pica-XML Format.

Solche CSV-Dateien liegen momentan vor für:

-  das Fach Geschichte (GOK P\*) mit einer feinsinnigeren Aufteilung als
   die reine GOK
-  Mathematics Subject Classification (MSC)
-  GEO-LEO mit Konkordanz zur Freiberger Klassifikation
-  SSG-FI Guides: *\* Angloamerikanischer Kulturraum (SPrache &
   Literatur, Geschichte)*\ \* Geographie
-  Neuerwerbungslisten: *\* Angloamerikanischer Kulturraum*\ \*
   Mathematik \*\* alle Fachreferate an der SUB

Eingabedateien kommen aus zwei Quellen:

1. Es können CSV Dateien im Ordner fileadmin/gok/csv hinterlegt werden.
   Ihre Dateinamen sollten sich nicht mit denen aus Schritt 1
   überschneiden.
2. Es können CSV Dateien heruntergeladen werden. Hierzu muß in der
   Konfiguration der Extension eine leerzeichenseparierte Liste von URLs
   hinterlegt werden.

   Mit diesen Einstellungen werden die Dateien an den hinterlegten URLs
   beim Ausführen des Scheduler Tasks in den Ordner fileadmin/gok/csv
   geladen und ersetzen dabei ältere Dateien mit denselben Namen.

Dateiformat: Als Spaltentrenner wird ein Semikolon (;) erwartet,
Spalteninhalte können von Anführungszeichen (") umschlossen sein.

Jede Zeile muß mindestens 3 Spalten enthalten:

1. Identifier des Datensatzes (wie PPN in ``003@ $0`` in Normsätzen)
2. Identifier des Eltern-Datensatzes (wie ``045C $9`` wenn ``$4 nueb``)
3. deutscher Name der Themengebiets (``045A $j``)
4. CCL Suchabfrage für diesen Eintrag [möglicherweise leer/nicht
   vorhanden] Stücke dieser Suchabfragen haben die Form
   Suchschlüssel=Begriff?. Sie können geklammert und mit ``and``,
   ``not`` und ``or`` verbunden werden. Bei Nutzung mit pazpar2 ist es
   wichtig, für die Suchschlüssel dieselbe Groß- und Kleinschreibung wie
   in den pazpar2 Einstellungen zu verwenden (unsere Konvention:
   Kleinbuchstaben).
5. englischer Name des Themengebiets (``044F $a`` wenn ``$S d``)
   [möglicherweise leer/nicht vorhanden]
6. komma-separierte Liste von Tags zur beliebigen Nutzung
   [möglicherweise leer/nicht vorhanden]

Ausgabedateien: Die CSV-Dateien werden zunächst in XML-Dateien im Format
der Pica-OPAC-Ausgabe umgewandelt. Die umgewandelten Dateien werden in
den Ordner fileadmin/gok/xml/ geschrieben, der Dateiname ist der der
Ausgangsdatei, in dem das abschließende ‘csv’ durch ‘xml’ ersetzt ist.

3: Fachhierarchie aus XML Dateien importieren
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Überschreibt die Datensätze in der Fachhierarchia-Tabelle in der
TYPO3-Datenbank mit neuen Daten aus den XML-Dateien in
fileadmin/gok/xml/\*.xml.

Die Dauer dieses Imports hängt von der Anzahl der Datensätze, der
Rechnergeschwindigkeit und der Datenbankanbindung ab. 5-10 Minuten sind
nicht ungewöhnlich.
