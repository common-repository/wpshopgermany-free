=== wpShopGermany Free ===
Contributors: maennchen1.de
Donate link: http://wpshopgermany.de
Tags: shop, commerce, wpShopGermany, verkaufen, produkte, artikel, waren, bestellungen, PayPal, checkout, warenkorb, payment, deutsch, deutschland, kunden, gutschein, MP3, PDF
Requires PHP: 5.6
Requires at least: 3.0.0
Tested up to: 6.0.0
Stable tag: 4.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Verkaufen Sie in Ihrem WordPress elektronische Waren (Downloads) und physische Waren sowie Dienstleistungen kostenlos mit diesem Plugin! Ohne Einschränkungen der Anzahl der Produkte.

== Description ==
wpShopGermany Free erlaubt das Verkaufen von elektronischen Waren/Gütern (Downloads von PDF, MP3, etc.) und physische Waren sowie Dienstleistungen/Software. Es bietet einen klassischen Checkout-Prozess mit Warenkorb und Kundenverwaltung.
Enthalten und voll funktionsfähig sind alle rechtlich vorgeschriebenen Funktionen für Deutschland, Österreich und Schweiz (D-A-CH), z.B. Buttonlösung, Bestellbedingungen, Widerrufsrecht, Preisauszeichnung, und so weiter. Ebenfalls ist die Integration von zusätzlichen Plugins der Rechtstexteanbieter (Händlerbund, IT-Recht-Kanzlei München, Protected Shops) gewährleistet.

> Mit einem kostenpflichtigen Upgrade der Lizenz auf Pro oder Enterprise haben Sie folgende Vorteile:
>
> * Zugriff auf weitere nützliche wpShopGermany-Kauf-Module wie Rechnungen, Lieferscheine, Payment Module (PayPal Plus, Amazon Pay, u.v.m.), Statistik, Sofort Ident, u.s.w.
> * 1 Jahr Updates und Support

[Dokumentation wpShopGermany](https://doc.maennchen1.de/docs/wpshopgermany4/ueber-wpshopgermany/ "Dokumentation wpShopGermany")

= Funktionsumfang =
wpShopGermany Free hat keinen reduzierteren Funktionsumfang. Es fehlt lediglich die Integrationsmöglichkeit von wpShopGermany-Kauf-Modulen.
Folgende Funktionen sind enthalten:

* Zahlungsarten: PayPal, Vorkasse, Bankeinzug
* Versandart: Download (E-Mail), frei einstellbar nach Bestellwert, Stückzahl oder Gewicht, oder Selbstabholung
* Einbindung Warenkorb mittels Widget
* Übersicht aller Bestellungen, inkl. Protokoll
* Übersicht aller Kunden (Kundenverwaltung)
* Übersicht aller Produkte (Produktverwaltung)
* Frei konfigurierbare Artikel
* Artikelanzahl: unlimitiert
* Anzahl der Produktbilder: unlimitiert
* Einfügen eines Produktes in einen/eine Artikel/Seite mittels RTE
* Staffelpreise
* CSV-, XML-Export / -Import der Produkte
* CSV-, XML-Export / -Import der Kunden
* CSV-, XML-Export / -Import der Bestellungen
* Templatesystem zur freien Anpassung mittels HTML/PHP
* Unterstützung von Mehrsprachigkeit via WPML
* Gutscheinsystem
* Rechtstexte Integration ([Händlerbund](https://wordpress.org/plugins/wpshopgermany-handlerbund/ "wpShopGermany Plugin: Händlerbund"), [IT-Recht-Kanzlei München](https://wordpress.org/plugins/wpshopgermany-it-recht-kanzlei/ "wpShopGermany Plugin: IT-Recht-Kanzlei München"), [Protected Shops](https://wordpress.org/plugins/wpshopgermany-protectedshops// "wpShopGermany Plugin: Protected Shops"))
* Benutzerverwaltung im Backend
* Kundenlogin im Frontend
* keine Sondermodule (separate Module können nur mit der Pro-/Enterpise Version erworben werden)

= Shortcode Beispiele =
1. Standard-Shortcode (Produkt-ID = 1): `[wpshopgermany product="1"]`
1. Shortcode mit eigenem Produkttemplate: `[wpshopgermany product="1" template="Template.phtml"]`
1. Darstellung einer vordefinierten Produktgruppe (ID = 1), aufsteigend nach Preis sortiert: `[wpshopgermany produktgruppe="1" sortierung="preis" richtung="asc"]`

Der vereinfachte Shortcode wird unter "Produktverwaltung > Produkt bearbeiten > Allgemein > Shortcode" angezeigt – sofern das Produkt bereits angelegt wurde. Im RTE gibt es den Warenkorbbutton, welcher einen Dialog erscheinen lässt. Dort wird automatische der richtige Shortcode erzeugt.
Mit dem kostenpflichtigen Modul Produktartikel können direkt WordPress Beiträge angelegt werden, die Nutzung von Shortcodes ist dann nicht notwendig.

[Dokumentation "Produkt einfügen"](https://doc.maennchen1.de/docs/wpshopgermany4/tutorials/produkt-einfuegen/ "Dokumentation: Produkt einfügen")

== Installation ==

1. Lade den Ordner `wpshopgermany-free` in dein WordPress Plugin Verzeichnis (`wp-config/plugins/`)
1. Aktiviere das Plugin
1. Setze Schreibberechtigungen für Webserver auf unten stehende Ordner
1. Konfiguriere das Plugin (Seiteneinstellungen, Shopinfo, etc.)
1. Lege ein Produkt an
1. Platziere den Shortcode in einem Beitrag

= Schreibberechtigungen für Webserver =
(empfohlen: chmod 0755 oder 777)
`/uploads/wpsg/
/wp-content/plugins/wpshopgermany-free/lib/`

[Dokumentation "wpShopGermany installieren"](https://doc.maennchen1.de/docs/wpshopgermany4/installation/installation/ "Dokumentation: wpShopGermany installieren")

= Systemanforderungen = 

* Apache Webserver
* PHP ab 5.6 mit Bibliotheken für:
 * OpenSSL
 * Curl
 * SimpleXML
 * Soap Client (nur für Modul Micropayment)
 * Zip
* empfohlene PHP-Einstellungen:
 * memory_limit = 128M*
 * max_execution_time = 600*
 * max_input_vars = 5000*
 * upload_max_filesize = 40M*
 * register_globals = Off
 * allow_url_fopen = On oder cURL
 * extension=php_openssl.dll (nur auf Windows-Server)
* Verarbeitung der .htaccess-Datei um Verzeichnisse zu schützen

*) je nach Serververwendung kann dieser Wert zu gering ausfallen und muss ggf. korrigiert werden

== Screenshots ==

1. Frontend: Darstellung einer Shopseite mit Produkt + Warenborb-Button sowie Warenkorb-Widget (im ShopThemeOne)
2. Frontend: gefüllter Warenkorb
3. Backend: Pflege eines Produktes (Ansicht "Allgemein")
4. Backend: Einstellungen für das Modul Downloadprodukte
5. Backend: optionale Anpassung der Bestellbedingungen für bestimmte Produkte
6. Backend: Optionen der Kundenverwaltung

== Changelog ==
 
= 4.2.1 =

* [Veränderte Templates](https://plugins.trac.wordpress.org/changeset?old=0&old_path=wpshopgermany-free%2Ftrunk%2Fviews&new=2228988&new_path=wpshopgermany-free%2Ftrunk%2Fviews)