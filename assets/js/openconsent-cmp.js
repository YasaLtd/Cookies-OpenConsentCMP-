(function () {
	'use strict';

	var config = window.OpenConsentCMP || {};
	var services = config.services || window.OpenConsentServices || [];
	var cookieName = 'openconsent_cmp';
	var themeStorageKey = 'openconsent_cmp_theme';
	var categories = ['preferences', 'statistics', 'marketing', 'unclassified'];
	var themePresets = [
		{ key: 'dark-teal', label: 'Teal', accent: '#54d2bf', background: '#111827', text: '#ffffff' },
		{ key: 'dark-blue', label: 'Blue', accent: '#73a7ff', background: '#101828', text: '#ffffff' },
		{ key: 'dark-gold', label: 'Gold', accent: '#f0b84f', background: '#1f2937', text: '#ffffff' },
		{ key: 'dark-rose', label: 'Rose', accent: '#fb7185', background: '#18111f', text: '#ffffff' }
	];
	var googleSignalInfo = {
		ad_storage: 'Advertising cookies and ad measurement.',
		ad_user_data: 'User data sent to Google for advertising.',
		ad_personalization: 'Personalized ads and remarketing.',
		analytics_storage: 'Analytics cookies and measurement.',
		functionality_storage: 'Functional storage for site features.',
		personalization_storage: 'Personalization storage for saved preferences.',
		security_storage: 'Security storage is always granted.'
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
			googleGuide: 'Google Consent Mode guide',
			consentPolicy: 'Google user consent policy',
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

		resolved.privacyPolicy = dictionary.privacyPolicy || english.privacyPolicy;
		resolved.googleGuide = dictionary.googleGuide || english.googleGuide;
		resolved.consentPolicy = dictionary.consentPolicy || english.consentPolicy;
		resolved.position = ui.position;
		resolved.accent = ui.accent;
		resolved.background = ui.background;
		resolved.text = ui.text;
		resolved.theme = ui.theme;
		resolved.lang = lang;
		resolved.descriptions = {};
		resolved.categoryLabels = {};

		['necessary'].concat(categories).forEach(function (category) {
			var configured = ui.descriptions && ui.descriptions[category] ? ui.descriptions[category] : '';
			var fallback = defaults.descriptions && defaults.descriptions[category] ? defaults.descriptions[category] : '';
			resolved.descriptions[category] = configured && configured !== fallback ? configured : ((dictionary.descriptions && dictionary.descriptions[category]) || english.descriptions[category] || configured);
			resolved.categoryLabels[category] = (dictionary.categories && dictionary.categories[category]) || english.categories[category] || category;
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
		if (src) {
			node.setAttribute('data-openconsent-src', src);
			node.removeAttribute('src');
		}
		node.setAttribute('data-openconsent-blocked', '1');
		window.OpenConsentQueue = window.OpenConsentQueue || [];
		window.OpenConsentQueue.push(node);
	}

	function blockFrame(node, category, src) {
		var service = serviceForUrl(src);
		node.setAttribute('src', 'about:blank');
		node.setAttribute('srcdoc', 'This embed is blocked until you allow its cookie category.');
		node.setAttribute('data-openconsent-category', category);
		node.setAttribute('data-openconsent-src', src);
		node.setAttribute('data-openconsent-blocked', '1');
		if (service && service.name) {
			node.setAttribute('data-openconsent-service', service.name);
		}
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

	function readableBlockedItem(node) {
		var fallback = node.tagName === 'IFRAME' ? 'Blocked embed' : 'Configured script';
		return node.getAttribute('data-openconsent-service') ||
			node.getAttribute('data-openconsent-src') ||
			node.getAttribute('src') ||
			(node.textContent ? 'Inline script' : fallback);
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
			summary.textContent = 'What this controls';
			var detailList = document.createElement('ul');
			var serviceItem = document.createElement('li');
			var runtimeItem = document.createElement('li');
			var signalItem = document.createElement('li');

			function updateCategoryDetails() {
				var categoryServices = servicesForCategory(category);
				var blockedItems = blockedItemsForCategory(category);
				var granted = input.checked || category === 'necessary';
				serviceItem.textContent = categoryServices.length
					? 'URL rules: ' + categoryServices.map(function (service) { return service.name || service.pattern; }).join(', ') + '.'
					: 'URL rules: no services configured in this category.';
				runtimeItem.textContent = blockedItems.length
					? 'Blocked on this page: ' + blockedItems.map(readableBlockedItem).slice(0, 4).join(', ') + (blockedItems.length > 4 ? ', +' + (blockedItems.length - 4) + ' more.' : '.')
					: 'Blocked on this page: no matching scripts or embeds right now.';
				signalItem.textContent = signalsForCategory(category).length
					? 'Google signals controlled here: ' + signalsForCategory(category).join(', ') + '.'
					: 'Google signals controlled here: none.';
				statusItem.textContent = granted ? 'Current state: allowed after saving.' : 'Current state: blocked or denied until allowed.';
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
				? 'Selected optional categories: ' + selected.join(', ') + '.'
				: 'Only necessary cookies are selected.';
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
			customize.textContent = (ui.customize || 'Customize') + ': ' + selectedTheme.label;
		});
		customize.textContent = (ui.customize || 'Customize') + ': ' + selectedTheme.label;
		customize.setAttribute('aria-label', 'Change consent dialog theme color');
		customize.title = 'Change theme color';
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

		var links = document.createElement('p');
		links.className = 'openconsent__links';
		links.innerHTML = '<a href="https://developers.google.com/tag-platform/security/guides/consent" target="_blank" rel="noopener noreferrer">' + (ui.googleGuide || 'Google Consent Mode guide') + '</a> <span>/</span> <a href="https://www.google.com/about/company/user-consent-policy/" target="_blank" rel="noopener noreferrer">' + (ui.consentPolicy || 'Google user consent policy') + '</a>';
		panel.appendChild(links);

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
		showPreferences: function () {
			renderBanner({ force: true, showCategories: true });
		},
		revoke: function () {
			document.cookie = cookieName + '=; Max-Age=0; path=/; SameSite=Lax';
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
