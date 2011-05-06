= GOK =

Importiert Daten aus Fächerhierarchien wie der Göttinger Online
Klassifikation (GOK) und zeigt sie an.
Die Anzeige kann als Baum oder über Menüs erfolgen.
Es gibt Scheduler Tasks, um die notwendigen Daten zu importieren.

== Datenimport ==
Das Plug-In kann aus zwei Quellen GOK Normdaten importieren:
* Durch Auslesen der Tev-Sätze im XML-Format aus dem Opac
* Über CSV-Dateien mit den zu importierenden Informationen

Hierfür gibt es verschiedene Typo3 Scheduler Tasks, die die notwendigen
Schritte durchführen.

Von den Tasks entdeckte Fehler werden im Typo3 Developer Log abgelegt.


=== GOK Daten laden, konvertieren und importieren ===
Dieser Scheduler Task führt die anderen drei Scheduler Tasks in der benötigten
Reihenfolge aus:

1. GOK XML-Daten importieren
2. CSV Daten zu XML konvertieren
3. GOK XML Daten importieren

Er sollte im regulären Betrieb nachts ausgeführt werden, da die GOK Daten
während des Neuimports (ca. 30 Sekunden) nicht verfügbar sind. In der Regel
sollte nur die Nutzung dieses Tasks notwendig sein.


=== GOK XML-Daten importieren ===
Dieser Scheduler Task lädt die GOK (Tev) Normdatensätze, sowie die Anzahl der
Treffer pro GOK aus dem Opac.

Die Abfrage für die Normdaten ist "MAK tev NOT LKL p*", also alle GOK
Normdatensätze außer denen, deren GOK mit P beginnt. Der Bereich P (Geschichte)
wird im folgenden Scheduler Task aus einer CSV Datei gelesen.

Die geladenen Daten sind im XML-Format des Opac (URL-Optionen XML=1 PRS=XML)
und enthalten Pica-Daten. Sie werden im Ordner fileadmin/gok/xml/ abgelegt. Der
Inhalt dieses Ordners wird beim Start des Scheduler Tasks gelöscht.

Die Abfrage der Trefferzahlen geschieht über ein Browsing des LKL Index. Die 
resultierenden XML Dateien werden im Ordner fileadmin/gok/hitcounts/ abgelegt.
Der Inhalt dieses Ordners wird beim Start des Scheduler Tasks gelöscht.


=== CSV Daten zu XML konvertieren ===
Dieser Scheduler Task konvertiert spezielle CSV-Dateien mit Fachinformationen
in das Pica-XML Format.

Solche CSV-Dateien liegen momentan vor für:
* das Fach Geschichte (GOK P*) mit einer feinsinnigeren Aufteilung als die reine GOK
* den History Guide und Anglistik Guide
* die Neuerwerbungslisten aus dem Bereich angloamerikanischer Kulturraum


Eingabedateien: fileadmin/csv/*.csv
Dateinamen sollen mit einem Buchstaben beginnen.

Dateiformat: Als Spaltentrenner wird ein Semikolon (;) erwartet
Jede Zeile muß mindestens 5 Spalten enthalten:
1. PPN des Datensatzen (wie 003@ $0 in Tev-Sätzen)
2. Hierarchiestufe (009B $a)
3. GOK (045A $a)
4. PPN der Eltern-GOK (038D $9)
5. deutscher Name der GOK (044E $a)
6. CCL Suchabfrage für diesen Eintrag [möglicherweise leer/nicht vorhanden]
	Stücke dieser Suchabfragen haben die Form Suchschlüssel=Begriff*. Sie können
	geklammert und mit 'and' bzw. 'or' verbunden werden. Es ist wichtig, für die
	Suchschlüssel dieselbe Groß- und Kleinschreibung wie in den pazpar2 Einstellungen
	zu verwenden (unsere Konvention: Kleinbuchstaben).
7. englischer Name der GOK (044F $a) [möglicherweise leer/nicht vorhanden]
8. komma-separierte Liste von Tags zur beliebigen Nutzung

Ausgabedateien: fileadmin/gok/xml/*.xml


=== GOK XML Daten importieren ===
Dieser Scheduler Task leert zunächst die GOK Tabelle in der Typo3-Datenbank und
füllt sie dann mit den Daten aus den XML-Dateien in fileadmin/gok/xml/*.xml.

Der Vorgang dauert 15-30 Sekunden. Während dieser Zeit kann Typo3 den Baum nicht
korrekt darstellen. Darum wäre ein Ausführen dieses Tasks nachts sinnvoll.


== Grundeinstellungen ==
Wird das Plug-In im Typo3 Extension-Manager ausgewählt, gibt es zwei
Grundeinstellungen:

# Opac Base URL with trailing /: Aus dieser URL werden die Links in den Opac
gebaut. [Standardwert: http://opac.sub.uni-goettingen.de/DB=1/]
# replace included CSS with path: Pfad einer eigenen CSS-Datei für die Baum-
und Menüdarstellung [Standardwert: leer, die CSS-Datei des Plug-Ins wird genutzt)


== Einstellungen ==
Jeder Seitenhinhalt mit GOK Plug-In hat drei Einstellungsmöglichkeiten:

# Startknoten: Der Startknoten kann auf zwei Arten festgelegt werden:
## Durch Auswahl eines GOK Wurzelknotens aus dem Popup-Menü ‘GOK-Hierarchie beginnen mit’
## Durch Eingabe der GOK des Wurzelknotens in das Feld ‘Eigene GOK als Startknoten angeben’
# Anzeigestil:
## Baum - hierarchische Baumstruktur
## unpraktischer Baum - hierarchische Baumstruktur mit ursprünglich geplantem Layout
## Menüs - es erscheint ein Menü mit den Untergebieten. Nach Auswahl eines Menüpunktes erscheint ein weiteres Menü mit den Untergebieten des ausgewählten Faches
# GOK-ID anzeigen: hiermit kann die Anzeige der GOK-IDs wie 'IA 663' an- bzw. abgestellt werden

