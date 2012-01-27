# GOK
Importiert Daten aus Fächerhierarchien wie der Göttinger Online
Klassifikation (GOK) und zeigt sie an.
Die Anzeige kann als Baum oder über Menüs erfolgen.
Es gibt Scheduler Tasks, um die benötigten Daten zu importieren.


## Datenimport
Die Extension kann aus zwei Quellen Fachhierarchien importieren:

* Durch Auslesen der GOK Normdaten (Tev)-Sätze im XML-Format aus dem Opac
* Über CSV-Dateien mit den zu importierenden Informationen

Hierfür gibt es verschiedene TYPO3 Scheduler Tasks, die die notwendigen
Schritte durchführen.

Beim Ausführen der Tasks auftretende Fehler werden in das TYPO3 Developer
Log geschrieben.


### 1+2+3: GOK Daten laden, konvertieren und importieren
Dieser Scheduler Task führt die anderen drei Scheduler Tasks in der benötigten
Reihenfolge aus:

1. GOK Daten aus Opac laden
2. CSV Dateien laden und zu XML konvertieren
3. GOK XML Dateien importieren

Er sollte im regulären Betrieb nachts ausgeführt werden, da die GOK Daten
während des Neuimports in Schritt 3 (ca. 30 Sekunden) nicht verfügbar sind.


### auto 2+3: Prüfen, ob die CSV Dateien aktualisiert wurden und konvertieren/reimportieren wenn sie es sind
Dieser Scheduler Task prüft, ob es zu allen CSV Dateien in `fileadmin/gok/csv/`
entsprechende XML Dateien in `fileadmin/gok/xml/` mit einem neueren Änderungsdatum
gibt. Ist dies nicht der Fall, werden die CSV Dateien erneut konvertiert und
importiert.

Dieser Scheduler Task kann häufig aufgerufen werden, damit Änderungen an den CSV
Dateien schnell auf der Seite verfügbar sind.


### 2+3: CSV Dateien konvertieren und alle XML Dateien neu importieren
Dieser Scheduler Task führt nur die bei einer Aktualisierung der CSV Dateien
nötigen Schritte aus:

1. CSV Dateien laden und zu XML konvertieren
2. GOK XML Dateien importieren


### 1: GOK XML-Daten importieren
Dieser Scheduler Task lädt die GOK (Tev) Normdatensätze, sowie die Anzahl der
Treffer pro GOK aus dem Opac.

Die Abfrage für die Normdaten ist `MAK tev NOT LKL p*`, also alle GOK
Normdatensätze außer denen, deren GOK mit P beginnt. Der Bereich P (Geschichte)
wird im folgenden Scheduler Task aus einer CSV Datei gelesen.

Die geladenen Daten sind im XML-Format des Opac (URL-Optionen `XML=1/PRS=XML`)
und enthalten Pica-Daten. Sie werden im Ordner `fileadmin/gok/xml/` abgelegt. Der
Inhalt dieses Ordners wird beim Start des Scheduler Tasks gelöscht.

Die Abfrage der Trefferzahlen geschieht über ein Browsing des LKL Index. Die 
resultierenden XML Dateien werden im Ordner `fileadmin/gok/hitcounts/` abgelegt.
Der Inhalt dieses Ordners wird beim Start des Scheduler Tasks gelöscht.


### 2: CSV Daten laden und zu XML konvertieren
Dieser Scheduler Task konvertiert spezielle CSV-Dateien mit Fachinformationen
in das Pica-XML Format.

Solche CSV-Dateien liegen momentan vor für:

* das Fach Geschichte (GOK P*) mit einer feinsinnigeren Aufteilung als die reine GOK
* SSG-FI Guides
* Neuerwerbungslisten

Eingabedateien kommen aus zwei Quellen:

1. können CSV Dateien heruntergeladen werden. Hierzu muß:
	1. im Setup der Root-Seite der Site ein Array der zu ladenden Dateien in der
		TypoScript Einstellung `plugin.tx_nkwgok_pi1.downloadUrl` hinterlegt werden.

		Beispiel:

			plugin.tx_nkwgok_pi1.downloadUrl {
				Neuerwerbungen = http://vlib-aac.sub.uni-goettingen.de/fileadmin/gok/csv/AACNeuerwerbungen.csv
				NeuerwerbungenHist = http://vlib-aac.sub.uni-goettingen.de/fileadmin/gok/csv/AACNeuerwerbungenHist.csv
				NeuerwerbungenLit = http://vlib-aac.sub.uni-goettingen.de/fileadmin/gok/csv/AACNeuerwerbungenLit.csv
			}
	2. in den Optionen des verwandten Scheduler-Tasks die ID der Root-Seite
		eingetragen sein

	Mit diesen Einstellungen werden die Dateien an den hinterlegten URLs beim
		Ausführen des Scheduler Tasks in den Ordner fileadmin/gok/csv geladen
		und ersetzen dabei ältere Dateien mit denselben Namen.
2. können CSV Dateien im Ordner fileadmin/gok/csv hinterlegt werden. Ihre Dateinamen
	sollten sich nicht mit denen aus Schritt 1 überschneiden.

Jede der CSV Dateien sollte höchstens 500 Zeilen umfassen, da sonst – abhängig
vom Speicherbedarf der genutzten TYPO3 Version – der Import an der Standardspeichergrenze
von 128MB für PHP/apache scheitern kann.

Dateiformat: Als Spaltentrenner wird ein Semikolon (;) erwartet, Spalteninhalte
können von Anführungszeichen (") umschlossen sein.

Jede Zeile muß mindestens 5 Spalten enthalten:

1. PPN des Datensatzen (wie `003@ $0` in Tev-Sätzen)
2. egal
3. GOK (`045A $a`)
4. PPN der Eltern-GOK (`038D $9`)
5. deutscher Name der GOK (`044E $a`)
6. CCL Suchabfrage für diesen Eintrag [möglicherweise leer/nicht vorhanden]
	Stücke dieser Suchabfragen haben die Form Suchschlüssel=Begriff?. Sie können
	geklammert und mit `and`, `not` und `or` verbunden werden. Es ist wichtig, für die
	Suchschlüssel dieselbe Groß- und Kleinschreibung wie in den pazpar2 Einstellungen
	zu verwenden (unsere Konvention: Kleinbuchstaben).
7. englischer Name der GOK (`044K $a`) [möglicherweise leer/nicht vorhanden]
8. komma-separierte Liste von Tags zur beliebigen Nutzung [möglicherweise leer/nicht vorhanden]

Ausgabedateien: Die CSV-Dateien werden zunächst in XML-Dateien im Format der
Pica-Opac-Ausgabe umgewandelt. Die umgewandelten Dateien werden in den Ordner
fileadmin/gok/xml/ geschrieben, der Dateiname ist der der Ausgangsdatei, in dem
das abschließende ‘csv’ durch ‘xml’ ersetzt ist.


### 3: GOK XML Daten importieren
Überschreibt die Daten in der GOK Tabelle in der TYPO3-Datenbank und
mit neuen Daten aus den XML-Dateien in fileadmin/gok/xml/*.xml.

Der Vorgang dauert je nach Rechnergeschwindigkeit und Datenbankanbindung
30-300 Sekunden.


## Grundeinstellungen
Wird die Extension im TYPO3 Extension-Manager ausgewählt, gibt es zwei
Grundeinstellungen:

1. Opac Base URL with trailing /: Aus dieser URL werden die Links in den Opac
gebaut. [Standardwert: https://opac.sub.uni-goettingen.de/DB=1/]
2. replace included CSS with path: Pfad einer eigenen CSS-Datei für die Baum-
und Menüdarstellung [Standardwert: leer, die CSS-Datei des Plug-Ins wird genutzt]


## Einstellungen
Jeder Seitenhinhalt mit GOK Plug-In hat drei Einstellungsmöglichkeiten:

1. Startknoten: Der Startknoten kann auf zwei Arten festgelegt werden:
	1. Durch Auswahl eines GOK Wurzelknotens aus dem Popup-Menü ‘GOK-Hierarchie beginnen mit’
	2. Durch Eingabe der GOK des Wurzelknotens in das Feld ‘Eigene GOK als Startknoten angeben’
2. Anzeigestil:
	1. Baum - hierarchische Baumstruktur
	2. Menüs - es erscheint ein Menü mit den Untergebieten. Nach Auswahl eines
	Menüpunktes erscheint ein weiteres Menü mit den Untergebieten des ausgewählten Faches
3. GOK-ID anzeigen: hiermit kann die Anzeige der GOK-IDs wie ‘IA 663’ an- bzw. abgestellt werden

Weiterhin kann über TypoScript konfiguriert mit der Einstellung	`plugin.tx_nkwgok_pi1.shallowSearch`
werden, ob die Anzeige vom Typ ‘Baum’ für eine GOK aus dem Opac

* verlinkt auf eine Suche nach Büchern aller Kindelemente der GOK [0, Standard]
* verlinkt auf eine Suche nach Büchern speziell zu dieser GOK [1]
