(function () {
	'use strict';

	var config = window.OpenConsentCMP || {};
	var services = config.services || window.OpenConsentServices || [];
	var cookieName = 'openconsent_cmp';
	var themeStorageKey = 'openconsent_cmp_theme';
	var categories = ['preferences', 'statistics', 'marketing', 'unclassified'];
	window.OpenConsentDebug = window.OpenConsentDebug || { blocked: [] };
	var themePresets = [
		{ key: 'dark-teal', label: 'Teal', accent: '#54d2bf', background: '#111827', text: '#ffffff' },
		{ key: 'dark-blue', label: 'Blue', accent: '#73a7ff', background: '#101828', text: '#ffffff' },
		{ key: 'dark-gold', label: 'Gold', accent: '#f0b84f', background: '#1f2937', text: '#ffffff' },
		{ key: 'dark-rose', label: 'Rose', accent: '#fb7185', background: '#18111f', text: '#ffffff' }
	];
	var googleSignalInfo = {
		ad_storage: true,
		ad_user_data: true,
		ad_personalization: true,
		analytics_storage: true,
		functionality_storage: true,
		personalization_storage: true,
		security_storage: true
	};
	var translations = {
		en: {
			title: 'Your privacy choices',
			message: 'We use cookies and similar technologies to keep this site reliable, measure usage, and improve marketing. Choose what you want to allow.',
			partyDisclosure: 'Google and other listed service providers may collect, receive, or use personal data when their services are enabled. Review the cookie declaration and privacy policy for details.',
			accept: 'Accept all',
			reject: 'Necessary only',
			save: 'Save choices',
			customize: 'Customize',
			revoke: 'Privacy choices',
			privacyPolicy: 'Privacy policy',
			regionStrict: 'Strict opt-in applies for your region.',
			regionNotice: 'Notice mode applies for your region. You can opt out of optional categories.',
			categories: {
				necessary: 'Necessary',
				preferences: 'Preferences',
				statistics: 'Statistics',
				marketing: 'Marketing',
				unclassified: 'Unclassified'
			},
			descriptions: {
				necessary: 'Necessary cookies keep the site secure and working. They are always active.',
				preferences: 'Preferences cookies remember choices such as language, region, and interface settings.',
				statistics: 'Statistics cookies help us understand how visitors use the site.',
				marketing: 'Marketing cookies support advertising, measurement, and embedded media.',
				unclassified: 'Unclassified services are blocked until the site owner reviews them.'
			}
		},
		fi: {
			title: 'Tietosuoja-asetukset',
			message: 'Käytämme evästeitä ja vastaavia tekniikoita sivuston toimivuuteen, käytön mittaamiseen ja markkinoinnin parantamiseen. Valitse, mitä haluat sallia.',
			partyDisclosure: 'Google ja muut listatut palveluntarjoajat voivat kerätä, vastaanottaa tai käyttää henkilötietoja, kun niiden palvelut otetaan käyttöön. Lisätiedot ovat evästeilmoituksessa ja tietosuojakäytännössä.',
			accept: 'Hyväksy kaikki',
			reject: 'Vain välttämättömät',
			save: 'Tallenna valinnat',
			customize: 'Mukauta',
			revoke: 'Tietosuoja-asetukset',
			privacyPolicy: 'Tietosuojakäytäntö',
			categories: {
				necessary: 'Välttämättömät',
				preferences: 'Asetukset',
				statistics: 'Tilastot',
				marketing: 'Markkinointi'
			},
			descriptions: {
				necessary: 'Välttämättömät evästeet pitävät sivuston turvallisena ja toimivana. Ne ovat aina käytössä.',
				preferences: 'Asetusevästeet muistavat valintoja, kuten kielen, alueen ja käyttöliittymän asetukset.',
				statistics: 'Tilastoevästeet auttavat ymmärtämään, miten kävijät käyttävät sivustoa.',
				marketing: 'Markkinointievästeet tukevat mainontaa, mittaamista ja upotettua mediaa.'
			}
		},
		de: {
			title: 'Ihre Datenschutzauswahl',
			accept: 'Alle akzeptieren',
			reject: 'Nur notwendige',
			save: 'Auswahl speichern',
			customize: 'Anpassen',
			revoke: 'Datenschutzauswahl',
			privacyPolicy: 'Datenschutzerklärung',
			categories: { necessary: 'Notwendig', preferences: 'Präferenzen', statistics: 'Statistiken', marketing: 'Marketing' }
		},
		es: {
			title: 'Tus opciones de privacidad',
			accept: 'Aceptar todo',
			reject: 'Solo necesarias',
			save: 'Guardar opciones',
			customize: 'Personalizar',
			revoke: 'Opciones de privacidad',
			privacyPolicy: 'Política de privacidad',
			categories: { necessary: 'Necesarias', preferences: 'Preferencias', statistics: 'Estadísticas', marketing: 'Marketing' }
		},
		fr: {
			title: 'Vos choix de confidentialité',
			accept: 'Tout accepter',
			reject: 'Nécessaires uniquement',
			save: 'Enregistrer les choix',
			customize: 'Personnaliser',
			revoke: 'Choix de confidentialité',
			privacyPolicy: 'Politique de confidentialité',
			categories: { necessary: 'Nécessaires', preferences: 'Préférences', statistics: 'Statistiques', marketing: 'Marketing' }
		},
		it: {
			title: 'Le tue scelte sulla privacy',
			accept: 'Accetta tutto',
			reject: 'Solo necessari',
			save: 'Salva scelte',
			customize: 'Personalizza',
			revoke: 'Scelte privacy',
			privacyPolicy: 'Informativa privacy',
			categories: { necessary: 'Necessari', preferences: 'Preferenze', statistics: 'Statistiche', marketing: 'Marketing' }
		},
		nl: {
			title: 'Uw privacykeuzes',
			accept: 'Alles accepteren',
			reject: 'Alleen noodzakelijk',
			save: 'Keuzes opslaan',
			customize: 'Aanpassen',
			revoke: 'Privacykeuzes',
			privacyPolicy: 'Privacybeleid',
			categories: { necessary: 'Noodzakelijk', preferences: 'Voorkeuren', statistics: 'Statistieken', marketing: 'Marketing' }
		},
		sv: {
			title: 'Dina integritetsval',
			accept: 'Acceptera alla',
			reject: 'Endast nödvändiga',
			save: 'Spara val',
			customize: 'Anpassa',
			revoke: 'Integritetsval',
			privacyPolicy: 'Integritetspolicy',
			categories: { necessary: 'Nödvändiga', preferences: 'Inställningar', statistics: 'Statistik', marketing: 'Marknadsföring' }
		}
	};
	var translationUpdates = {
		en: {
			regionStrict: 'Strict opt-in applies for your region.',
			regionNotice: 'Notice mode applies for your region. You can opt out of optional categories.',
			whatThisControls: 'What this controls',
			configuredServices: 'Configured services',
			noServicesConfigured: 'URL rules: no services configured in this category.',
			blockedOnPage: 'Blocked on this page',
			noBlockedItems: 'Blocked on this page: no matching scripts or embeds right now.',
			moreItems: 'more',
			googleSignalsControlled: 'Google signals controlled here',
			none: 'none',
			currentStateAllowed: 'Current state: allowed after saving.',
			currentStateBlocked: 'Current state: blocked or denied until allowed.',
			selectedOptionalCategories: 'Selected optional categories',
			onlyNecessarySelected: 'Only necessary cookies are selected.',
			changeThemeAria: 'Change consent dialog theme color',
			changeThemeTitle: 'Change theme color',
			configuredService: 'Configured service',
			providerLabel: 'provider',
			purposeLabel: 'purpose',
			policyLabel: 'policy',
			blockedEmbed: 'Blocked embed',
			configuredScript: 'Configured script',
			inlineScript: 'Inline script',
			embedBlocked: 'This embed is blocked until you allow its cookie category.',
			themeLabels: { 'dark-teal': 'Teal', 'dark-blue': 'Blue', 'dark-gold': 'Gold', 'dark-rose': 'Rose' }
		},
		fi: {
			title: 'Tietosuoja-asetukset',
			message: 'Käytämme evästeitä ja vastaavia tekniikoita sivuston toimivuuteen, käytön mittaamiseen ja markkinoinnin parantamiseen. Valitse, mitä haluat sallia.',
			partyDisclosure: 'Google ja muut listatut palveluntarjoajat voivat kerätä, vastaanottaa tai käyttää henkilötietoja, kun niiden palvelut otetaan käyttöön. Lisätiedot ovat evästeilmoituksessa ja tietosuojakäytännössä.',
			accept: 'Hyväksy kaikki',
			reject: 'Vain välttämättömät',
			save: 'Tallenna valinnat',
			customize: 'Mukauta',
			revoke: 'Tietosuoja-asetukset',
			privacyPolicy: 'Tietosuojakäytäntö',
			regionStrict: 'Alueellasi sovelletaan tiukkaa opt-in-suostumusta.',
			regionNotice: 'Alueellasi käytetään ilmoitustilaa. Voit kieltäytyä valinnaisista luokista.',
			categories: { necessary: 'Välttämättömät', preferences: 'Asetukset', statistics: 'Tilastot', marketing: 'Markkinointi', unclassified: 'Luokittelemattomat' },
			descriptions: {
				necessary: 'Välttämättömät evästeet pitävät sivuston turvallisena ja toimivana. Ne ovat aina käytössä.',
				preferences: 'Asetusevästeet muistavat valintoja, kuten kielen, alueen ja käyttöliittymän asetukset.',
				statistics: 'Tilastoevästeet auttavat ymmärtämään, miten kävijät käyttävät sivustoa.',
				marketing: 'Markkinointievästeet tukevat mainontaa, mittaamista ja upotettua mediaa.',
				unclassified: 'Luokittelemattomat palvelut estetään, kunnes sivuston omistaja tarkistaa ne.'
			},
			whatThisControls: 'Mitä tämä hallitsee',
			configuredServices: 'Määritetyt palvelut',
			noServicesConfigured: 'URL-säännöt: tässä luokassa ei ole määritettyjä palveluja.',
			blockedOnPage: 'Tällä sivulla estetyt',
			noBlockedItems: 'Tällä sivulla estetyt: ei vastaavia skriptejä tai upotuksia juuri nyt.',
			moreItems: 'lisää',
			googleSignalsControlled: 'Tässä hallitut Google-signaalit',
			none: 'ei mitään',
			currentStateAllowed: 'Nykyinen tila: sallitaan tallennuksen jälkeen.',
			currentStateBlocked: 'Nykyinen tila: estetty tai evätty, kunnes se sallitaan.',
			selectedOptionalCategories: 'Valitut valinnaiset luokat',
			onlyNecessarySelected: 'Vain välttämättömät evästeet on valittu.',
			changeThemeAria: 'Vaihda suostumusikkunan teemaväriä',
			changeThemeTitle: 'Vaihda teemaväri',
			configuredService: 'Määritetty palvelu',
			providerLabel: 'tarjoaja',
			purposeLabel: 'tarkoitus',
			policyLabel: 'käytäntö',
			blockedEmbed: 'Estetty upotus',
			configuredScript: 'Määritetty skripti',
			inlineScript: 'Upotettu skripti',
			embedBlocked: 'Tämä upotus on estetty, kunnes sallit sen evästeluokan.',
			themeLabels: { 'dark-teal': 'Turkoosi', 'dark-blue': 'Sininen', 'dark-gold': 'Kulta', 'dark-rose': 'Roosa' }
		},
		de: {
			message: 'Wir verwenden Cookies und ahnliche Technologien, um diese Website zuverlassig zu betreiben, die Nutzung zu messen und Marketing zu verbessern. Wahlen Sie, was Sie erlauben mochten.',
			partyDisclosure: 'Google und andere aufgefuhrte Dienstanbieter konnen personenbezogene Daten erfassen, empfangen oder verwenden, wenn ihre Dienste aktiviert sind. Details finden Sie in der Cookie-Erklarung und Datenschutzerklarung.',
			regionStrict: 'Fur Ihre Region gilt striktes Opt-in.',
			regionNotice: 'Fur Ihre Region gilt der Hinweis-Modus. Sie konnen optionale Kategorien abwahlen.',
			categories: { necessary: 'Notwendig', preferences: 'Praferenzen', statistics: 'Statistiken', marketing: 'Marketing', unclassified: 'Nicht klassifiziert' },
			descriptions: { necessary: 'Notwendige Cookies halten die Website sicher und funktionsfahig. Sie sind immer aktiv.', preferences: 'Praferenz-Cookies speichern Entscheidungen wie Sprache, Region und Oberflacheneinstellungen.', statistics: 'Statistik-Cookies helfen uns zu verstehen, wie Besucher die Website nutzen.', marketing: 'Marketing-Cookies unterstutzen Werbung, Messung und eingebettete Medien.', unclassified: 'Nicht klassifizierte Dienste werden blockiert, bis der Websitebetreiber sie gepruft hat.' },
			whatThisControls: 'Was dies steuert', configuredServices: 'Konfigurierte Dienste', noServicesConfigured: 'URL-Regeln: In dieser Kategorie sind keine Dienste konfiguriert.', blockedOnPage: 'Auf dieser Seite blockiert', noBlockedItems: 'Auf dieser Seite blockiert: derzeit keine passenden Skripte oder Einbettungen.', moreItems: 'weitere', googleSignalsControlled: 'Hier gesteuerte Google-Signale', none: 'keine', currentStateAllowed: 'Aktueller Status: nach dem Speichern erlaubt.', currentStateBlocked: 'Aktueller Status: blockiert oder verweigert, bis erlaubt.', selectedOptionalCategories: 'Ausgewahlte optionale Kategorien', onlyNecessarySelected: 'Nur notwendige Cookies sind ausgewahlt.', changeThemeAria: 'Farbe des Einwilligungsdialogs andern', changeThemeTitle: 'Designfarbe andern', configuredService: 'Konfigurierter Dienst', providerLabel: 'Anbieter', purposeLabel: 'Zweck', policyLabel: 'Richtlinie', blockedEmbed: 'Blockierte Einbettung', configuredScript: 'Konfiguriertes Skript', inlineScript: 'Inline-Skript', embedBlocked: 'Diese Einbettung ist blockiert, bis Sie ihre Cookie-Kategorie erlauben.', themeLabels: { 'dark-teal': 'Petrol', 'dark-blue': 'Blau', 'dark-gold': 'Gold', 'dark-rose': 'Rose' }
		},
		es: {
			message: 'Usamos cookies y tecnologias similares para mantener el sitio fiable, medir el uso y mejorar el marketing. Elige que quieres permitir.',
			partyDisclosure: 'Google y otros proveedores listados pueden recopilar, recibir o usar datos personales cuando sus servicios estan habilitados. Revisa la declaracion de cookies y la politica de privacidad para mas detalles.',
			regionStrict: 'Se aplica consentimiento opt-in estricto en tu region.',
			regionNotice: 'Se aplica modo de aviso en tu region. Puedes rechazar las categorias opcionales.',
			categories: { necessary: 'Necesarias', preferences: 'Preferencias', statistics: 'Estadisticas', marketing: 'Marketing', unclassified: 'Sin clasificar' },
			descriptions: { necessary: 'Las cookies necesarias mantienen el sitio seguro y funcionando. Siempre estan activas.', preferences: 'Las cookies de preferencias recuerdan opciones como idioma, region y ajustes de interfaz.', statistics: 'Las cookies de estadisticas nos ayudan a entender como usan el sitio los visitantes.', marketing: 'Las cookies de marketing admiten publicidad, medicion y medios incrustados.', unclassified: 'Los servicios sin clasificar se bloquean hasta que el propietario del sitio los revise.' },
			whatThisControls: 'Que controla esto', configuredServices: 'Servicios configurados', noServicesConfigured: 'Reglas URL: no hay servicios configurados en esta categoria.', blockedOnPage: 'Bloqueado en esta pagina', noBlockedItems: 'Bloqueado en esta pagina: no hay scripts ni inserciones coincidentes ahora.', moreItems: 'mas', googleSignalsControlled: 'Senales de Google controladas aqui', none: 'ninguna', currentStateAllowed: 'Estado actual: permitido despues de guardar.', currentStateBlocked: 'Estado actual: bloqueado o denegado hasta que se permita.', selectedOptionalCategories: 'Categorias opcionales seleccionadas', onlyNecessarySelected: 'Solo estan seleccionadas las cookies necesarias.', changeThemeAria: 'Cambiar color del dialogo de consentimiento', changeThemeTitle: 'Cambiar color del tema', configuredService: 'Servicio configurado', providerLabel: 'proveedor', purposeLabel: 'finalidad', policyLabel: 'politica', blockedEmbed: 'Insercion bloqueada', configuredScript: 'Script configurado', inlineScript: 'Script en linea', embedBlocked: 'Esta insercion esta bloqueada hasta que permitas su categoria de cookies.', themeLabels: { 'dark-teal': 'Verde azulado', 'dark-blue': 'Azul', 'dark-gold': 'Dorado', 'dark-rose': 'Rosa' }
		},
		fr: {
			message: 'Nous utilisons des cookies et des technologies similaires pour assurer la fiabilite du site, mesurer l utilisation et ameliorer le marketing. Choisissez ce que vous autorisez.',
			partyDisclosure: 'Google et les autres fournisseurs listes peuvent collecter, recevoir ou utiliser des donnees personnelles lorsque leurs services sont actives. Consultez la declaration relative aux cookies et la politique de confidentialite pour plus de details.',
			regionStrict: 'Un consentement opt-in strict s applique a votre region.',
			regionNotice: 'Le mode information s applique a votre region. Vous pouvez refuser les categories optionnelles.',
			categories: { necessary: 'Necessaires', preferences: 'Preferences', statistics: 'Statistiques', marketing: 'Marketing', unclassified: 'Non classes' },
			descriptions: { necessary: 'Les cookies necessaires assurent la securite et le fonctionnement du site. Ils sont toujours actifs.', preferences: 'Les cookies de preferences memorisent des choix comme la langue, la region et les reglages d interface.', statistics: 'Les cookies statistiques nous aident a comprendre comment les visiteurs utilisent le site.', marketing: 'Les cookies marketing prennent en charge la publicite, la mesure et les medias integres.', unclassified: 'Les services non classes sont bloques jusqu a leur examen par le proprietaire du site.' },
			whatThisControls: 'Ce que cela controle', configuredServices: 'Services configures', noServicesConfigured: 'Regles URL: aucun service configure dans cette categorie.', blockedOnPage: 'Bloque sur cette page', noBlockedItems: 'Bloque sur cette page: aucun script ou contenu integre correspondant pour le moment.', moreItems: 'autres', googleSignalsControlled: 'Signaux Google controles ici', none: 'aucun', currentStateAllowed: 'Etat actuel: autorise apres enregistrement.', currentStateBlocked: 'Etat actuel: bloque ou refuse jusqu a autorisation.', selectedOptionalCategories: 'Categories optionnelles selectionnees', onlyNecessarySelected: 'Seuls les cookies necessaires sont selectionnes.', changeThemeAria: 'Changer la couleur du dialogue de consentement', changeThemeTitle: 'Changer la couleur du theme', configuredService: 'Service configure', providerLabel: 'fournisseur', purposeLabel: 'finalite', policyLabel: 'politique', blockedEmbed: 'Contenu integre bloque', configuredScript: 'Script configure', inlineScript: 'Script integre', embedBlocked: 'Ce contenu integre est bloque jusqu a ce que vous autorisiez sa categorie de cookies.', themeLabels: { 'dark-teal': 'Sarcelle', 'dark-blue': 'Bleu', 'dark-gold': 'Or', 'dark-rose': 'Rose' }
		},
		it: {
			message: 'Usiamo cookie e tecnologie simili per mantenere affidabile il sito, misurare l uso e migliorare il marketing. Scegli cosa consentire.',
			partyDisclosure: 'Google e gli altri fornitori elencati possono raccogliere, ricevere o usare dati personali quando i loro servizi sono abilitati. Consulta la dichiarazione sui cookie e l informativa privacy per i dettagli.',
			regionStrict: 'Nella tua area si applica il consenso opt-in rigoroso.',
			regionNotice: 'Nella tua area si applica la modalita avviso. Puoi rifiutare le categorie opzionali.',
			categories: { necessary: 'Necessari', preferences: 'Preferenze', statistics: 'Statistiche', marketing: 'Marketing', unclassified: 'Non classificati' },
			descriptions: { necessary: 'I cookie necessari mantengono il sito sicuro e funzionante. Sono sempre attivi.', preferences: 'I cookie di preferenza ricordano scelte come lingua, area geografica e impostazioni dell interfaccia.', statistics: 'I cookie statistici ci aiutano a capire come i visitatori usano il sito.', marketing: 'I cookie di marketing supportano pubblicita, misurazione e media incorporati.', unclassified: 'I servizi non classificati sono bloccati finche il proprietario del sito non li verifica.' },
			whatThisControls: 'Cosa controlla', configuredServices: 'Servizi configurati', noServicesConfigured: 'Regole URL: nessun servizio configurato in questa categoria.', blockedOnPage: 'Bloccati in questa pagina', noBlockedItems: 'Bloccati in questa pagina: nessuno script o incorporamento corrispondente al momento.', moreItems: 'altri', googleSignalsControlled: 'Segnali Google controllati qui', none: 'nessuno', currentStateAllowed: 'Stato attuale: consentito dopo il salvataggio.', currentStateBlocked: 'Stato attuale: bloccato o negato finche non consentito.', selectedOptionalCategories: 'Categorie opzionali selezionate', onlyNecessarySelected: 'Sono selezionati solo i cookie necessari.', changeThemeAria: 'Cambia colore della finestra di consenso', changeThemeTitle: 'Cambia colore tema', configuredService: 'Servizio configurato', providerLabel: 'fornitore', purposeLabel: 'scopo', policyLabel: 'informativa', blockedEmbed: 'Incorporamento bloccato', configuredScript: 'Script configurato', inlineScript: 'Script inline', embedBlocked: 'Questo incorporamento e bloccato finche non consenti la sua categoria di cookie.', themeLabels: { 'dark-teal': 'Verde acqua', 'dark-blue': 'Blu', 'dark-gold': 'Oro', 'dark-rose': 'Rosa' }
		},
		nl: {
			message: 'We gebruiken cookies en vergelijkbare technologieen om deze site betrouwbaar te houden, gebruik te meten en marketing te verbeteren. Kies wat u wilt toestaan.',
			partyDisclosure: 'Google en andere vermelde dienstverleners kunnen persoonsgegevens verzamelen, ontvangen of gebruiken wanneer hun diensten zijn ingeschakeld. Bekijk de cookieverklaring en het privacybeleid voor details.',
			regionStrict: 'Voor uw regio geldt strikte opt-in.',
			regionNotice: 'Voor uw regio geldt de meldingsmodus. U kunt optionele categorieen weigeren.',
			categories: { necessary: 'Noodzakelijk', preferences: 'Voorkeuren', statistics: 'Statistieken', marketing: 'Marketing', unclassified: 'Niet geclassificeerd' },
			descriptions: { necessary: 'Noodzakelijke cookies houden de site veilig en werkend. Ze zijn altijd actief.', preferences: 'Voorkeurscookies onthouden keuzes zoals taal, regio en interface-instellingen.', statistics: 'Statistiekcookies helpen ons begrijpen hoe bezoekers de site gebruiken.', marketing: 'Marketingcookies ondersteunen advertenties, meting en ingesloten media.', unclassified: 'Niet-geclassificeerde diensten worden geblokkeerd totdat de site-eigenaar ze beoordeelt.' },
			whatThisControls: 'Wat dit beheert', configuredServices: 'Geconfigureerde diensten', noServicesConfigured: 'URL-regels: geen diensten geconfigureerd in deze categorie.', blockedOnPage: 'Geblokkeerd op deze pagina', noBlockedItems: 'Geblokkeerd op deze pagina: momenteel geen overeenkomende scripts of embeds.', moreItems: 'meer', googleSignalsControlled: 'Google-signalen die hier worden beheerd', none: 'geen', currentStateAllowed: 'Huidige status: toegestaan na opslaan.', currentStateBlocked: 'Huidige status: geblokkeerd of geweigerd totdat toegestaan.', selectedOptionalCategories: 'Geselecteerde optionele categorieen', onlyNecessarySelected: 'Alleen noodzakelijke cookies zijn geselecteerd.', changeThemeAria: 'Wijzig kleur van toestemmingsvenster', changeThemeTitle: 'Wijzig themakleur', configuredService: 'Geconfigureerde dienst', providerLabel: 'provider', purposeLabel: 'doel', policyLabel: 'beleid', blockedEmbed: 'Geblokkeerde embed', configuredScript: 'Geconfigureerd script', inlineScript: 'Inline script', embedBlocked: 'Deze embed is geblokkeerd totdat u de cookiecategorie toestaat.', themeLabels: { 'dark-teal': 'Zeegroen', 'dark-blue': 'Blauw', 'dark-gold': 'Goud', 'dark-rose': 'Roze' }
		},
		sv: {
			message: 'Vi anvander cookies och liknande tekniker for att halla webbplatsen tillforlitlig, mata anvandning och forbattra marknadsforing. Valj vad du vill tillata.',
			partyDisclosure: 'Google och andra listade tjansteleverantorer kan samla in, ta emot eller anvanda personuppgifter nar deras tjanster aktiveras. Se cookiedeklarationen och integritetspolicyn for detaljer.',
			regionStrict: 'Strikt opt-in galler for din region.',
			regionNotice: 'Meddelandelage galler for din region. Du kan valja bort valfria kategorier.',
			categories: { necessary: 'Nodvandiga', preferences: 'Installningar', statistics: 'Statistik', marketing: 'Marknadsforing', unclassified: 'Oklassificerade' },
			descriptions: { necessary: 'Nodvandiga cookies haller webbplatsen saker och fungerande. De ar alltid aktiva.', preferences: 'Installningscookies minns val som sprak, region och granssnittsinstallningar.', statistics: 'Statistikcookies hjalper oss att forsta hur besokare anvander webbplatsen.', marketing: 'Marknadsforingscookies stodjer annonsering, matning och inbaddade medier.', unclassified: 'Oklassificerade tjanster blockeras tills webbplatsagaren granskar dem.' },
			whatThisControls: 'Vad detta styr', configuredServices: 'Konfigurerade tjanster', noServicesConfigured: 'URL-regler: inga tjanster ar konfigurerade i denna kategori.', blockedOnPage: 'Blockerat pa denna sida', noBlockedItems: 'Blockerat pa denna sida: inga matchande skript eller inbaddningar just nu.', moreItems: 'fler', googleSignalsControlled: 'Google-signaler som styrs har', none: 'inga', currentStateAllowed: 'Aktuellt lage: tillats efter sparande.', currentStateBlocked: 'Aktuellt lage: blockerat eller nekat tills det tillats.', selectedOptionalCategories: 'Valda valfria kategorier', onlyNecessarySelected: 'Endast nodvandiga cookies ar valda.', changeThemeAria: 'Andra farg pa samtyckesdialogen', changeThemeTitle: 'Andra temafarg', configuredService: 'Konfigurerad tjanst', providerLabel: 'leverantor', purposeLabel: 'syfte', policyLabel: 'policy', blockedEmbed: 'Blockerad inbaddning', configuredScript: 'Konfigurerat skript', inlineScript: 'Inline-skript', embedBlocked: 'Denna inbaddning ar blockerad tills du tillater dess cookiekategori.', themeLabels: { 'dark-teal': 'Turkos', 'dark-blue': 'Bla', 'dark-gold': 'Guld', 'dark-rose': 'Rosa' }
		}
	};
	function mergeTranslation(base, update) {
		Object.keys(update).forEach(function (key) {
			if (update[key] && typeof update[key] === 'object' && !Array.isArray(update[key])) {
				base[key] = base[key] || {};
				mergeTranslation(base[key], update[key]);
			} else {
				base[key] = update[key];
			}
		});
	}
	Object.keys(translationUpdates).forEach(function (lang) {
		translations[lang] = translations[lang] || {};
		mergeTranslation(translations[lang], translationUpdates[lang]);
	});
	var originalCreateElement = document.createElement.bind(document);
	var originalAppendChild = Element.prototype.appendChild;
	var originalInsertBefore = Element.prototype.insertBefore;
	var originalSetAttribute = Element.prototype.setAttribute;

	function readConsent() {
		var match = document.cookie.match(new RegExp('(?:^|; )' + cookieName + '=([^;]*)'));
		if (!match) {
			return null;
		}

		try {
			return JSON.parse(decodeURIComponent(match[1]));
		} catch (error) {
			return null;
		}
	}

	function visitorSignals() {
		var signals = [];
		if (navigator.languages) {
			signals = signals.concat(Array.prototype.slice.call(navigator.languages));
		}
		if (navigator.language) {
			signals.push(navigator.language);
		}
		if (config.detectedLanguage) {
			signals.push(config.detectedLanguage);
		}
		if (config.siteLocale) {
			signals.push(config.siteLocale);
		}
		return signals;
	}

	function languageCode() {
		var languages = config.autoDetectLanguage ? visitorSignals() : [config.detectedLanguage, config.siteLocale];

		for (var i = 0; i < languages.length; i += 1) {
			var code = String(languages[i] || '').toLowerCase().split('-')[0];
			if (translations[code]) {
				return code;
			}
		}

		return 'en';
	}

	function detectRegion() {
		var defaultRegion = String(config.defaultRegion || 'eea').toLowerCase();
		var strictRegions = ['at', 'be', 'bg', 'hr', 'cy', 'cz', 'dk', 'ee', 'fi', 'fr', 'de', 'gr', 'hu', 'is', 'ie', 'it', 'lv', 'li', 'lt', 'lu', 'mt', 'nl', 'no', 'pl', 'pt', 'ro', 'sk', 'si', 'es', 'se', 'gb', 'uk', 'ch'];
		var signals = visitorSignals();
		var timezone = '';

		try {
			timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
		} catch (error) {}

		for (var i = 0; i < signals.length; i += 1) {
			var parts = String(signals[i] || '').toLowerCase().replace('_', '-').split('-');
			var region = parts.length > 1 ? parts[parts.length - 1] : '';
			if (strictRegions.indexOf(region) !== -1) {
				return 'eea';
			}
			if (region === 'us') {
				return 'us';
			}
		}

		if (timezone.indexOf('Europe/') === 0) {
			return 'eea';
		}

		return ['eea', 'us', 'other'].indexOf(defaultRegion) !== -1 ? defaultRegion : 'eea';
	}

	function strictConsentApplies() {
		var mode = String(config.regionMode || 'strict').toLowerCase();
		if (mode === 'notice') {
			return false;
		}
		if (mode === 'auto') {
			return detectRegion() === 'eea';
		}
		return true;
	}

	function defaultCategoryValue(category, currentConsent, strictMode) {
		if (category === 'necessary') {
			return true;
		}
		if (currentConsent && Object.prototype.hasOwnProperty.call(currentConsent, category)) {
			return Boolean(currentConsent[category]);
		}
		if (category === 'unclassified') {
			return false;
		}
		return !strictMode && String(config.consentModel || 'opt_in').toLowerCase() === 'opt_out';
	}

	function translatedUi() {
		var ui = config.ui || {};
		var defaults = config.defaultUi || {};
		var lang = languageCode();
		var dictionary = translations[lang] || translations.en;
		var english = translations.en;
		var resolved = {};

		['title', 'message', 'partyDisclosure', 'accept', 'reject', 'save', 'customize', 'revoke', 'regionStrict', 'regionNotice'].forEach(function (key) {
			var value = ui[key] || '';
			var defaultValue = defaults[key] || english[key] || '';
			resolved[key] = value && value !== defaultValue ? value : (dictionary[key] || english[key] || value);
		});

		[
			'privacyPolicy',
			'whatThisControls',
			'configuredServices',
			'noServicesConfigured',
			'blockedOnPage',
			'noBlockedItems',
			'moreItems',
			'googleSignalsControlled',
			'none',
			'currentStateAllowed',
			'currentStateBlocked',
			'selectedOptionalCategories',
			'onlyNecessarySelected',
			'changeThemeAria',
			'changeThemeTitle',
			'configuredService',
			'providerLabel',
			'purposeLabel',
			'policyLabel',
			'blockedEmbed',
			'configuredScript',
			'inlineScript',
			'embedBlocked'
		].forEach(function (key) {
			resolved[key] = dictionary[key] || english[key] || '';
		});
		resolved.position = ui.position;
		resolved.accent = ui.accent;
		resolved.background = ui.background;
		resolved.text = ui.text;
		resolved.theme = ui.theme;
		resolved.lang = lang;
		resolved.descriptions = {};
		resolved.categoryLabels = {};
		resolved.themeLabels = {};

		['necessary'].concat(categories).forEach(function (category) {
			var configured = ui.descriptions && ui.descriptions[category] ? ui.descriptions[category] : '';
			var fallback = defaults.descriptions && defaults.descriptions[category] ? defaults.descriptions[category] : '';
			resolved.descriptions[category] = configured && configured !== fallback ? configured : ((dictionary.descriptions && dictionary.descriptions[category]) || english.descriptions[category] || configured);
			resolved.categoryLabels[category] = (dictionary.categories && dictionary.categories[category]) || english.categories[category] || category;
		});

		themePresets.forEach(function (theme) {
			resolved.themeLabels[theme.key] = (dictionary.themeLabels && dictionary.themeLabels[theme.key]) || (english.themeLabels && english.themeLabels[theme.key]) || theme.label;
		});

		return resolved;
	}

	function readStoredTheme() {
		try {
			return window.localStorage ? window.localStorage.getItem(themeStorageKey) : '';
		} catch (error) {
			return '';
		}
	}

	function writeStoredTheme(themeKey) {
		try {
			if (window.localStorage) {
				window.localStorage.setItem(themeStorageKey, themeKey);
			}
		} catch (error) {}
	}

	function themeByKey(themeKey) {
		for (var i = 0; i < themePresets.length; i += 1) {
			if (themePresets[i].key === themeKey) {
				return themePresets[i];
			}
		}
		return themePresets[0];
	}

	function applyTheme(root, theme, ui) {
		var selected = theme || themeByKey(readStoredTheme() || ui.theme || 'dark-teal');
		root.dataset.openconsentTheme = selected.key;
		root.style.setProperty('--openconsent-accent', selected.accent || ui.accent || '#54d2bf');
		root.style.setProperty('--openconsent-background', selected.background || ui.background || '#111827');
		root.style.setProperty('--openconsent-text', selected.text || ui.text || '#ffffff');
		return selected;
	}

	function localizedThemeLabel(theme, ui) {
		return (ui.themeLabels && ui.themeLabels[theme.key]) || theme.label || theme.key;
	}

	function writeConsent(consent) {
		var expires = new Date();
		expires.setFullYear(expires.getFullYear() + 1);
		document.cookie = cookieName + '=' + encodeURIComponent(JSON.stringify(consent)) + '; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax';
	}

	function hasConsent(category) {
		var consent = readConsent();
		return category === 'necessary' || Boolean(consent && consent[category]);
	}

	function categoryForUrl(url) {
		if (!url) {
			return null;
		}

		if (config.googleConsentMode && config.googleConsentBehavior === 'advanced' && isGoogleConsentAwareUrl(url)) {
			return null;
		}

		for (var i = 0; i < services.length; i += 1) {
			if (url.toLowerCase().indexOf(String(services[i].pattern).toLowerCase()) !== -1) {
				return services[i].category;
			}
		}

		return null;
	}

	function serviceForUrl(url) {
		if (!url) {
			return null;
		}
		for (var i = 0; i < services.length; i += 1) {
			if (url.toLowerCase().indexOf(String(services[i].pattern).toLowerCase()) !== -1) {
				return services[i];
			}
		}
		return null;
	}

	function recordDebugBlocked(kind, category, src, service) {
		var item = {
			kind: kind,
			category: category || '',
			src: src || '',
			service: service && service.name ? service.name : '',
			provider: service && service.provider ? service.provider : '',
			time: new Date().toISOString()
		};

		window.OpenConsentDebug.blocked.push(item);
		if (config.debugMode && window.console && typeof window.console.info === 'function') {
			window.console.info('[OpenConsent CMP] Blocked ' + kind + ' until consent is granted.', item);
		}
		try {
			window.dispatchEvent(new CustomEvent('openconsent:debug-blocked', { detail: item }));
		} catch (error) {}
	}

	function isGoogleConsentAwareUrl(url) {
		var anchor = document.createElement('a');
		anchor.href = url;
		var host = String(anchor.hostname || '').toLowerCase();
		return host.indexOf('googletagmanager.com') !== -1 ||
			host.indexOf('google-analytics.com') !== -1 ||
			host.indexOf('googleadservices.com') !== -1 ||
			host.indexOf('doubleclick.net') !== -1;
	}

	function blockScript(node, category, src) {
		var service = serviceForUrl(src);
		node.type = 'text/plain';
		node.setAttribute('data-openconsent-category', category);
		if (service && service.name) {
			node.setAttribute('data-openconsent-service', service.name);
		}
		if (service && service.provider) {
			node.setAttribute('data-openconsent-provider', service.provider);
		}
		if (service && service.purpose) {
			node.setAttribute('data-openconsent-purpose', service.purpose);
		}
		if (src) {
			node.setAttribute('data-openconsent-src', src);
			node.removeAttribute('src');
		}
		node.setAttribute('data-openconsent-blocked', '1');
		window.OpenConsentQueue = window.OpenConsentQueue || [];
		window.OpenConsentQueue.push(node);
		recordDebugBlocked('script', category, src, service);
	}

	function blockFrame(node, category, src) {
		var service = serviceForUrl(src);
		var ui = translatedUi();
		node.setAttribute('src', 'about:blank');
		node.setAttribute('srcdoc', ui.embedBlocked || 'This embed is blocked until you allow its cookie category.');
		node.setAttribute('data-openconsent-category', category);
		node.setAttribute('data-openconsent-src', src);
		node.setAttribute('data-openconsent-blocked', '1');
		if (service && service.name) {
			node.setAttribute('data-openconsent-service', service.name);
		}
		if (service && service.provider) {
			node.setAttribute('data-openconsent-provider', service.provider);
		}
		if (service && service.purpose) {
			node.setAttribute('data-openconsent-purpose', service.purpose);
		}
		recordDebugBlocked('embed', category, src, service);
	}

	function shouldBlockScript(node) {
		var src = node.getAttribute && (node.getAttribute('src') || node.getAttribute('data-openconsent-src'));
		var category = node.getAttribute && node.getAttribute('data-openconsent-category');
		category = category || categoryForUrl(src);

		if (!category || hasConsent(category)) {
			return null;
		}

		return { category: category, src: src };
	}

	function shouldBlockFrame(node) {
		var src = node.getAttribute && (node.getAttribute('src') || node.getAttribute('data-openconsent-src'));
		var category = node.getAttribute && node.getAttribute('data-openconsent-category');
		category = category || categoryForUrl(src);

		if (!src || !category || hasConsent(category)) {
			return null;
		}

		return { category: category, src: src };
	}

	function installAutoBlocker() {
		if (config.blockingMode !== 'auto') {
			return;
		}

		document.createElement = function (tagName) {
			var element = originalCreateElement(tagName);

			if (String(tagName).toLowerCase() === 'script') {
				var pendingSrc = '';
				Object.defineProperty(element, 'src', {
					get: function () {
						return pendingSrc || element.getAttribute('src') || '';
					},
					set: function (value) {
						pendingSrc = value;
						var category = categoryForUrl(value);
						if (category && !hasConsent(category)) {
							element.setAttribute('data-openconsent-src', value);
							element.setAttribute('data-openconsent-category', category);
							element.type = 'text/plain';
						} else {
							originalSetAttribute.call(element, 'src', value);
						}
					}
				});
			}

			return element;
		};

		Element.prototype.appendChild = function (node) {
			var decision = node && node.tagName === 'SCRIPT' ? shouldBlockScript(node) : null;
			if (decision) {
				blockScript(node, decision.category, decision.src);
			} else if (node && node.tagName === 'IFRAME') {
				decision = shouldBlockFrame(node);
				if (decision) {
					blockFrame(node, decision.category, decision.src);
				}
			}
			return originalAppendChild.call(this, node);
		};

		Element.prototype.insertBefore = function (node, reference) {
			var decision = node && node.tagName === 'SCRIPT' ? shouldBlockScript(node) : null;
			if (decision) {
				blockScript(node, decision.category, decision.src);
			} else if (node && node.tagName === 'IFRAME') {
				decision = shouldBlockFrame(node);
				if (decision) {
					blockFrame(node, decision.category, decision.src);
				}
			}
			return originalInsertBefore.call(this, node, reference);
		};
	}

	function updateGoogleConsent(consent) {
		if (!config.googleConsentMode || typeof window.gtag !== 'function') {
			return;
		}

		window.gtag('consent', 'update', buildGoogleConsent(consent));
	}

	function syncWordPressConsentApi(consent, attempts) {
		if (!config.wpConsentApi || !consent) {
			return;
		}

		if (typeof window.wp_set_consent !== 'function') {
			if ((attempts || 0) < 10) {
				window.setTimeout(function () {
					syncWordPressConsentApi(consent, (attempts || 0) + 1);
				}, 250);
			}
			return;
		}

		var map = {
			functional: true,
			preferences: Boolean(consent.preferences),
			statistics: Boolean(consent.statistics),
			'statistics-anonymous': Boolean(consent.statistics),
			marketing: Boolean(consent.marketing)
		};

		Object.keys(map).forEach(function (category) {
			window.wp_set_consent(category, map[category] ? 'allow' : 'deny');
		});
	}

	function googleSignalCategory(signal) {
		var defaultMap = {
			ad_storage: 'marketing',
			ad_user_data: 'marketing',
			ad_personalization: 'marketing',
			analytics_storage: 'statistics',
			functionality_storage: 'preferences',
			personalization_storage: 'preferences'
		};
		var map = config.googleSignalMap || defaultMap;
		return map[signal] || defaultMap[signal] || 'denied';
	}

	function buildGoogleConsent(consent) {
		var state = { security_storage: 'granted' };
		['ad_personalization', 'ad_storage', 'ad_user_data', 'analytics_storage', 'functionality_storage', 'personalization_storage'].forEach(function (signal) {
			var category = googleSignalCategory(signal);
			state[signal] = category !== 'denied' && Boolean(consent && consent[category]) ? 'granted' : 'denied';
		});
		return state;
	}

	function servicesForCategory(category) {
		return services.filter(function (service) {
			return service.category === category;
		});
	}

	function readableService(service, ui) {
		var bits = [service.name || service.pattern || ui.configuredService || 'Configured service'];
		if (service.provider) {
			bits.push((ui.providerLabel || 'provider') + ': ' + service.provider);
		}
		if (service.purpose) {
			bits.push((ui.purposeLabel || 'purpose') + ': ' + service.purpose);
		}
		if (service.privacy_url) {
			bits.push((ui.policyLabel || 'policy') + ': ' + service.privacy_url);
		}
		return bits.join(' | ');
	}

	function signalsForCategory(category) {
		var signals = [];
		Object.keys(googleSignalInfo).forEach(function (signal) {
			if (signal === 'security_storage' && category === 'necessary') {
				signals.push(signal);
			} else if (googleSignalCategory(signal) === category) {
				signals.push(signal);
			}
		});
		return signals;
	}

	function blockedItemsForCategory(category) {
		var scripts = Array.prototype.slice.call(document.querySelectorAll('script[type="text/plain"][data-openconsent-category="' + category + '"]'));
		var frames = Array.prototype.slice.call(document.querySelectorAll('iframe[data-openconsent-category="' + category + '"][data-openconsent-src]'));
		return scripts.concat(frames);
	}

	function readableBlockedItem(node, ui) {
		var fallback = node.tagName === 'IFRAME' ? (ui.blockedEmbed || 'Blocked embed') : (ui.configuredScript || 'Configured script');
		return node.getAttribute('data-openconsent-service') ||
			node.getAttribute('data-openconsent-src') ||
			node.getAttribute('src') ||
			(node.textContent ? (ui.inlineScript || 'Inline script') : fallback);
	}

	function unblockScripts() {
		var blocked = Array.prototype.slice.call(document.querySelectorAll('script[type="text/plain"][data-openconsent-category]'));

		blocked.forEach(function (script) {
			var category = script.getAttribute('data-openconsent-category');
			if (!hasConsent(category)) {
				return;
			}

			var fresh = document.createElement('script');
			Array.prototype.slice.call(script.attributes).forEach(function (attr) {
				if (attr.name.indexOf('data-openconsent') === 0 || attr.name === 'type') {
					return;
				}
				fresh.setAttribute(attr.name, attr.value);
			});

			if (script.getAttribute('data-openconsent-src')) {
				fresh.src = script.getAttribute('data-openconsent-src');
			} else {
				fresh.text = script.text || script.textContent || script.innerHTML;
			}

			script.parentNode.replaceChild(fresh, script);
		});
	}

	function unblockFrames() {
		var blocked = Array.prototype.slice.call(document.querySelectorAll('iframe[data-openconsent-category][data-openconsent-src]'));

		blocked.forEach(function (frame) {
			var category = frame.getAttribute('data-openconsent-category');
			var src = frame.getAttribute('data-openconsent-src');
			if (!src || !hasConsent(category)) {
				return;
			}

			frame.setAttribute('src', src);
			frame.removeAttribute('srcdoc');
			frame.removeAttribute('data-openconsent-blocked');
		});
	}

	function logConsent(consent) {
		if (!window.fetch || !config.ajaxUrl) {
			return;
		}

		var payload = new window.FormData();
		payload.append('action', 'openconsent_log_consent');
		payload.append('nonce', config.nonce || '');
		payload.append('consent', JSON.stringify(consent));
		payload.append('page_url', window.location ? window.location.href : '');
		payload.append('referrer_url', document.referrer || '');

		window.fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload
		}).catch(function () {});
	}

	function saveConsent(values, actionName) {
		var consent = {
			id: (readConsent() && readConsent().id) || String(Date.now()) + Math.random().toString(16).slice(2),
			necessary: true,
			preferences: Boolean(values.preferences),
			statistics: Boolean(values.statistics),
			marketing: Boolean(values.marketing),
			unclassified: Boolean(values.unclassified),
			region: detectRegion(),
			regionMode: String(config.regionMode || 'strict').toLowerCase(),
			action: actionName || 'save_choices',
			language: languageCode(),
			updated: new Date().toISOString()
		};

		writeConsent(consent);
		updateGoogleConsent(consent);
		syncWordPressConsentApi(consent);
		unblockScripts();
		unblockFrames();
		logConsent(consent);
		renderFloatingControl();
		window.dispatchEvent(new CustomEvent('openconsent:updated', { detail: consent }));
		return consent;
	}

	function makeButton(label, className, onClick) {
		var button = document.createElement('button');
		button.type = 'button';
		button.className = className;
		button.textContent = label;
		button.addEventListener('click', onClick);
		return button;
	}

	function renderBanner(options) {
		options = options || {};
		if ((readConsent() && !options.force) || !config.ui) {
			var existing = readConsent();
			if (existing) {
				updateGoogleConsent(existing);
				syncWordPressConsentApi(existing);
				unblockScripts();
				unblockFrames();
				renderFloatingControl();
			}
			return;
		}

		var ui = translatedUi();
		var currentConsent = readConsent();
		var strictMode = strictConsentApplies();
		var existingBanner = document.querySelector('.openconsent');
		if (existingBanner) {
			existingBanner.remove();
		}
		var root = document.createElement('div');
		root.className = 'openconsent openconsent--' + (ui.position || 'center');
		root.setAttribute('lang', ui.lang || 'en');
		root.setAttribute('translate', 'yes');
		root.setAttribute('role', 'dialog');
		root.setAttribute('aria-modal', 'true');
		root.setAttribute('aria-labelledby', 'openconsent-title');
		var selectedTheme = applyTheme(root, null, ui);

		var panel = document.createElement('div');
		panel.className = 'openconsent__panel';

		var title = document.createElement('h2');
		title.id = 'openconsent-title';
		title.textContent = ui.title || 'Privacy choices';

		var message = document.createElement('p');
		message.className = 'openconsent__message';
		message.textContent = ui.message || '';

		var partyDisclosure = document.createElement('p');
		partyDisclosure.className = 'openconsent__disclosure';
		partyDisclosure.textContent = ui.partyDisclosure || '';

		var regionNotice = document.createElement('p');
		regionNotice.className = 'openconsent__region';
		regionNotice.textContent = strictMode
			? (ui.regionStrict || 'Strict opt-in applies for your region.')
			: (ui.regionNotice || 'Notice mode applies for your region. You can opt out of optional categories.');

		var categoriesWrap = document.createElement('div');
		categoriesWrap.className = 'openconsent__categories';
		categoriesWrap.hidden = false;

		var inputs = {};
		['necessary'].concat(categories).forEach(function (category) {
			var label = document.createElement('label');
			label.className = 'openconsent__category';
			var input = document.createElement('input');
			var descriptionId = 'openconsent-desc-' + category;
			input.type = 'checkbox';
			input.checked = defaultCategoryValue(category, currentConsent, strictMode);
			input.disabled = category === 'necessary';
			input.name = 'openconsent-' + category;
			input.setAttribute('aria-describedby', descriptionId);
			inputs[category] = input;

			var copy = document.createElement('span');
			var strong = document.createElement('strong');
			strong.textContent = ui.categoryLabels[category] || category;
			var small = document.createElement('small');
			small.id = descriptionId;
			small.textContent = (ui.descriptions && ui.descriptions[category]) || '';
			var details = document.createElement('details');
			details.className = 'openconsent__details';
			var summary = document.createElement('summary');
			summary.textContent = ui.whatThisControls || 'What this controls';
			var detailList = document.createElement('ul');
			var serviceItem = document.createElement('li');
			var runtimeItem = document.createElement('li');
			var signalItem = document.createElement('li');

			function updateCategoryDetails() {
				var categoryServices = servicesForCategory(category);
				var blockedItems = blockedItemsForCategory(category);
				var signals = signalsForCategory(category);
				var granted = input.checked || category === 'necessary';
				serviceItem.textContent = categoryServices.length
					? (ui.configuredServices || 'Configured services') + ': ' + categoryServices.map(function (service) {
						return readableService(service, ui);
					}).join('; ') + '.'
					: (ui.noServicesConfigured || 'URL rules: no services configured in this category.');
				runtimeItem.textContent = blockedItems.length
					? (ui.blockedOnPage || 'Blocked on this page') + ': ' + blockedItems.map(function (item) {
						return readableBlockedItem(item, ui);
					}).slice(0, 4).join(', ') + (blockedItems.length > 4 ? ', +' + (blockedItems.length - 4) + ' ' + (ui.moreItems || 'more') + '.' : '.')
					: (ui.noBlockedItems || 'Blocked on this page: no matching scripts or embeds right now.');
				signalItem.textContent = signals.length
					? (ui.googleSignalsControlled || 'Google signals controlled here') + ': ' + signals.join(', ') + '.'
					: (ui.googleSignalsControlled || 'Google signals controlled here') + ': ' + (ui.none || 'none') + '.';
				statusItem.textContent = granted ? (ui.currentStateAllowed || 'Current state: allowed after saving.') : (ui.currentStateBlocked || 'Current state: blocked or denied until allowed.');
			}

			detailList.appendChild(serviceItem);
			detailList.appendChild(runtimeItem);
			detailList.appendChild(signalItem);
			var statusItem = document.createElement('li');
			detailList.appendChild(statusItem);
			input.addEventListener('change', updateCategoryDetails);
			details.addEventListener('toggle', updateCategoryDetails);
			updateCategoryDetails();
			details.appendChild(summary);
			details.appendChild(detailList);
			copy.appendChild(strong);
			copy.appendChild(small);
			copy.appendChild(details);
			label.appendChild(input);
			label.appendChild(copy);
			categoriesWrap.appendChild(label);
		});

		var choicesSummary = document.createElement('p');
		choicesSummary.className = 'openconsent__summary';
		choicesSummary.setAttribute('aria-live', 'polite');

		function selectedOptionalLabels() {
			return categories.filter(function (category) {
				return inputs[category] && inputs[category].checked;
			}).map(function (category) {
				return ui.categoryLabels[category] || category;
			});
		}

		function updateChoicesSummary() {
			var selected = selectedOptionalLabels();
			choicesSummary.textContent = selected.length
				? (ui.selectedOptionalCategories || 'Selected optional categories') + ': ' + selected.join(', ') + '.'
				: (ui.onlyNecessarySelected || 'Only necessary cookies are selected.');
		}

		categories.forEach(function (category) {
			inputs[category].addEventListener('change', updateChoicesSummary);
		});
		updateChoicesSummary();

		var actions = document.createElement('div');
		actions.className = 'openconsent__actions';

		var reject = makeButton(ui.reject || 'Necessary only', 'openconsent__button openconsent__button--ghost', function () {
			saveConsent({}, 'necessary_only');
			root.remove();
		});
		var customize = makeButton(ui.customize || 'Customize', 'openconsent__button openconsent__button--ghost', function () {
			var nextIndex = 0;
			for (var i = 0; i < themePresets.length; i += 1) {
				if (themePresets[i].key === selectedTheme.key) {
					nextIndex = (i + 1) % themePresets.length;
					break;
				}
			}
			selectedTheme = applyTheme(root, themePresets[nextIndex], ui);
			writeStoredTheme(selectedTheme.key);
			customize.textContent = (ui.customize || 'Customize') + ': ' + localizedThemeLabel(selectedTheme, ui);
		});
		customize.textContent = (ui.customize || 'Customize') + ': ' + localizedThemeLabel(selectedTheme, ui);
		customize.setAttribute('aria-label', ui.changeThemeAria || 'Change consent dialog theme color');
		customize.title = ui.changeThemeTitle || 'Change theme color';
		var save = makeButton(ui.save || 'Save choices', 'openconsent__button openconsent__button--secondary', function () {
			saveConsent({
				preferences: inputs.preferences.checked,
				statistics: inputs.statistics.checked,
				marketing: inputs.marketing.checked,
				unclassified: inputs.unclassified.checked
			}, 'save_choices');
			root.remove();
		});
		var accept = makeButton(ui.accept || 'Accept all', 'openconsent__button openconsent__button--primary', function () {
			saveConsent({ preferences: true, statistics: true, marketing: true, unclassified: true }, 'accept_all');
			root.remove();
		});

		actions.appendChild(reject);
		actions.appendChild(customize);
		actions.appendChild(save);
		actions.appendChild(accept);

		panel.appendChild(title);
		panel.appendChild(message);
		if (ui.partyDisclosure) {
			panel.appendChild(partyDisclosure);
		}
		panel.appendChild(regionNotice);

		if (ui.privacyUrl) {
			var privacy = document.createElement('a');
			privacy.className = 'openconsent__privacy';
			privacy.href = ui.privacyUrl;
			privacy.textContent = ui.privacyPolicy || 'Privacy policy';
			panel.appendChild(privacy);
		}

		panel.appendChild(categoriesWrap);
		panel.appendChild(choicesSummary);
		panel.appendChild(actions);
		root.appendChild(panel);
		document.body.appendChild(root);
	}

	function renderFloatingControl() {
		if (document.querySelector('.openconsent-reopen') || !config.ui) {
			return;
		}

		var ui = translatedUi();
		var button = document.createElement('button');
		button.type = 'button';
		button.className = 'openconsent-reopen';
		button.setAttribute('lang', ui.lang || 'en');
		button.setAttribute('translate', 'yes');
		button.textContent = ui.revoke || 'Privacy choices';
		button.addEventListener('click', function () {
			var existing = document.querySelector('.openconsent');
			if (existing) {
				existing.remove();
			}
			button.remove();
			renderBanner({ force: true, showCategories: true });
		});
		document.body.appendChild(button);
	}

	window.OpenConsent = {
		getConsent: readConsent,
		setConsent: saveConsent,
		showBanner: renderBanner,
		debug: window.OpenConsentDebug,
		showPreferences: function () {
			renderBanner({ force: true, showCategories: true });
		},
		revoke: function () {
			document.cookie = cookieName + '=; Max-Age=0; path=/; SameSite=Lax';
			syncWordPressConsentApi({
				necessary: true,
				preferences: false,
				statistics: false,
				marketing: false,
				unclassified: false
			});
			renderBanner({ force: true, showCategories: true });
		}
	};

	installAutoBlocker();
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', renderBanner);
	} else {
		renderBanner();
	}
}());
