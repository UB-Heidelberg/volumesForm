## Bedienungsanleitung für das OMP-Plugin
# Mehrbändige Werke

Das Mehrbändige Werke Plugin für OMP 3.4 ist eine Erweiterung und Weiterentwicklung des ursprünglich von der WLB entwickelten Plugins für OMP 3.3.  

## 1. Funktionsumfang
Das Plugin führt einen neuen Objekttyp in OMP ein: Mehrbändige Werke (Volumes). Diese funktionieren ähnlich wie Reihen, so ist es möglich einem, einmal angelegten mehrbändigen Werk, beliebig viele Submissions zu zu weisen.  

### 1.1 Gesamtaufnahme
Das mehrbändige Werk selbst verfügt über folgende Felder:  
- Titel [Title] (Pflichfeld, mehrsprachig)
- Beschreibung [Description] (mehrsprachig)
- Cover [Cover Image]
- PPN
- ISBN-13 & ISBN-10
- Sortierung der Teilbände [Order of monographs] unter Berücksichtigung des Sortierplugins (sofern vorhanden) nach:
  - Titel (A-Z) [Title (A-Z)]
  - Titel (Z-A) [Title (Z-A)]
  - Erscheinungsdatum (älteste zuerst) [Publication date (oldest first)]
  - Erscheinungsdatum (neueste zuerst) [Publication date (newest first)]
  - Bandnummer (niedrigste zuerst) [Volume position (lowest first)]
  - Bandnummer (höchste zuerst) [Volume position (highest first)]
- Erscheinungsverlauf [Course of publication] (Freitextfeld), z.B.: (2002-2013) oder (2002 -) oder jährlich
- Pfad [Path] (Pflichtfeld)
  > Wichtig:  
  > Hierbei geht es um den Pfad zur Gesamtaufnahme des mehrbändigen Werkes. Dieser wird Teil der URL. Die URL setzt sich zusammen aus: 	
  > https://Domain/press/catalog/volume/Pfad  
  > Pfad darf keine Umlaute, Sonderzeichen (außer - _ .) oder Leerzeichen enthalten.  
   
Für jedes mehrbändige Werk wird eine Seite mit der Gesamtaufnahme erzeugt, sobald der erste Teilband veröffentlicht ist. Eine Vorschau existiert für jene angemeldeten Benutzer, die das Recht haben die Vorschau für einen der Teilbände zu sehen.

### 1.2 Teilband
Jeder Submission kann nun analog zur Reihe ein mehrbändiges Werk (Volume) und eine Bandnummer (Volume Position) zugewiesen werden.

### 1.3 Katalog
Im Katalog findet sich bei jedem Teilband der Titel der Gesamtaufnahme. Der Titel der Gesamtaufnahme dient auch als Link zur Seite der Gesamtaufnahme. Einen eigenen Katalogeintrag hat die Gesamtaufnahme nicht.

### 1.4 Band- und Kapitelseiten
Auf den Band- und Kapitelseiten findet sich ebenfalls der Titel der Gesamtaufnahme, sowie die Bandnummer. Auch Titel und Bandnummern der anderen Teilbände werden angezeigt. Hier sind ebenfalls die entsprechenden Links hinterlegt.

### 1.5 Zitierhinweis
Beim Zitierhinweis wird aus dem Bandtitel:
> Titel der Gesamtaufnahme [, Bandnummer][:] Bandtitel

Der Doppelpunkt wird nur verwendet, wenn davor kein ? ! / oder & steht. Es handelt sich dabei um jene Regelung, die seitens PKP angewendet wird um Titel miteinander zu verbinden.
Sofern der Zitationsstil das Anzeigen der ISBN fordert, werden die Nummern der Gesamtaufnahme ergänzt.

### 1.6 OAI
Die OAI Daten werden analog zum Zitierhinweis angepasst. Betroffen ist hierbei nur das Feld „dc:title“.

## 2. Bedienung
Im Folgenden wird eine Installation und Aktivierung des Plugins vorausgesetzt. Hinweise dazu entnehmen Sie bitte der README Datei.

### 2.1 Anlegen einer Gesamtaufnahme
- Im Backend findet sich unter „Press“ der Reiter „Volumes“.
- Mit der Schaltfläche „Add Volume“ öffnet sich eine leere Eingabemaske zum Anlegen einer Gesamtaufnahme.
- Wenigstens die Felder Titel und Pfad müssen ausgefüllt werden.
- Mit der Schaltfläche „OK“ werden die eingegebenen Daten gespeichert und die Eingabemaske geschlossen. „Cancel“ schließt die Maske ohne die Daten zu speichern.

> Wichtig:  
> Solange kein veröffentlichter Teilband zugewiesen ist, wird im Frontend keine Seite für die Gesamtaufnahme erzeugt. 

### 2.2 Bearbeiten oder Löschen einer Gesamtaufnahme
- Wieder begibt man sich im Backend unter „Press“ auf den Reiter „Volumes“.
- Dort findet man eine Liste aller vorhandenen mehrbändigen Werke.
- Mit einem Klick auf den Pfeil vor dem Titel der gewünschten Gesamtaufnahme werden die möglichen Optionen eingeblendet:  
  „Edit“ und „Delete“

  #### 2.2.1 Edit
  - „Edit“ öffnet die vom Anlegen einer Gesamtaufnahme bekannt Eingabemaske, ausgefüllt mit den aktuellen Daten.  
  - Änderungen werden mit der Schaltfläche „OK“ gespeichert. „Cancel“ verwirft die Änderungen.

  #### 2.2.2 Delete
  „Delete“ wird generell nur angezeigt, wenn der Gesamtaufnahme noch keine Teilbände zugewiesen sind. Eine Gesamtaufnahme mit Teilbänden kann also nicht gelöscht werden.  

### 2.3 Teilband zuweisen
- Zu einem Teilband kann jede Submission gemacht werden, sobald man auf den Reiter „Publication“ zugreifen kann.  
- Unter „Publication“ findet sich der Menüpunkt „Catalog Entry“.  
- Dort kann man unter den Feldern zur Reihe nun im Feld „Volume“ aus den vorhandenen Gesamtaufnahmen wählen.
- Das Feld „Volume Position“ ist analog zu den Reihen ein Freitextfeld.
- Mit der Schaltfläche „Save“ werden die Daten wie gewohnt gespeichert.

### 2.4 Plugin-Settings
In den Settings des Plugins kann definiert werden, welche Rolle als Autor und welche als Herausgeber verwendet werden soll. Die Settings sind nur vorhanden, wenn das „Enhanced Roles (UB Heidelberg)“ Plugin nicht aktiv ist. Ist das „Enhanced Roles (UB Heidelberg)“ Plugin aktiv, werden die dort getätigten Einstellungen übernommen.