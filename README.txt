2010-06-01_10-10-30

=Display GOK=

==Dateien erzeugen/ GOK auslesen==
===PICA==
* PICA Normadatensätze via Service-Script (außerhalb von TYPO3)
* XML-Dateien werden erzeugt 
===GOK Geschichte===
* asdf

==Dateien für die Aktualisierung bereitstellen==
* erzeugte Dateien in den Ordner /fileadmin/gok/ schrieben
* bestehende Dateien überschreiben

==(Automatische) Aktualisierung==
* Scheduler schaut im Ordner /fileadmin/gok/ nach XML-Dateien
* liest diese in die Datenbank (Tabelle: tx_nkwgok_data)
