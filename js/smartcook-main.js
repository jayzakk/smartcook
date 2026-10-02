"use strict";
/*
 * SmartCook compatibility frontend.
 * Dependency-free TypeScript bundle included in release archives so the app can
 * be installed without Node.js. The Vue/Vite source in src/ remains available
 * for teams that prefer the standard Nextcloud frontend stack.
 */
const smartWindow = window;
const appId = 'smartcook';
const root = document.getElementById('smartcook');
const publicRoot = document.getElementById('smartcook-public');
[root, publicRoot].forEach(node => node?.removeAttribute('data-theme'));
let busyCount = 0;
let messageTimer = 0;
const fallbackTranslations = {
    it: {
        'Ingredients': 'Ingredienti',
        'Category': 'Categoria',
        'Category...': 'Categoria...',
        'Edit': 'Modifica',
        'Edit ingredient': 'Modifica ingrediente',
        'Ingredient updated': 'Ingrediente aggiornato',
        'Save': 'Salva',
        'Substitutes (comma separated)': 'Sostituti (separati da virgola)',
        'e.g. Milk, Water': 'es. Latte, Acqua',
        // Keep bundle-only UI text translated when the Nextcloud catalog has not been regenerated yet.
        minutes: 'minuti',
        hours: 'ore',
        'Remove this ingredient?': 'Rimuovere questo ingrediente?',
        'Remove this step?': 'Rimuovere questo passaggio?',
        'Delete this share?': 'Eliminare questa condivisione?',
        'Delete this meal?': 'Eliminare questo pasto?',
        'Empty week': 'Svuota settimana',
        Actions: 'Azioni',
        Share: 'Condividi',
        'Could not copy the link': 'Impossibile copiare il link',
        'Add to today\'s plan': 'Aggiungi al piano di oggi',
        'Add to shopping list': 'Aggiungi alla lista della spesa',
        'Choose where to add this recipe or create a new list.': 'Scegli dove aggiungere questa ricetta oppure crea una nuova lista.',
        'Create a new list': 'Crea una nuova lista',
        'Add to selected list': 'Aggiungi alla lista selezionata',
        'Recipe added to shopping list': 'Ricetta aggiunta alla lista della spesa',
        'Generate one from your recipes': 'Generane una dalle tue ricette',
        'No items yet': 'Nessun articolo',
        Items: 'Elementi',
        of: 'di',
        completed: 'completati',
        Completed: 'Completata',
        'New list': 'Nuova lista',
        Decrease: 'Diminuisci',
        Increase: 'Aumenta',
        Appearance: 'Aspetto',
        'Interface theme': 'Tema dell\'interfaccia',
        Theme: 'Tema',
        Default: 'Predefinito',
        'Editorial — warm, magazine style': 'Editoriale — caldo, stile rivista',
        'Minimal — dense, flat lists': 'Minimale — liste dense e piatte',
        'Bold — dark sidebar, colorful cards': 'Vivace — barra laterale scura, card colorate',
        'Changes the look of this browser only; it is not shared with other devices or users.': 'Cambia l\'aspetto solo su questo browser; non è condiviso con altri dispositivi o utenti.',
		Alternatives: 'Alternative',
		'Add alternative': 'Aggiungi alternativa',
		'Alternative ingredient': 'Ingrediente alternativo',
		Previous: 'Indietro',
		Next: 'Avanti',
		'Move step up': 'Sposta il passaggio in alto',
		'Move step down': 'Sposta il passaggio in basso',
		Advanced: 'Avanzate',
		'Attachments, sharing and version history become available after the first save.': 'Allegati, condivisioni e cronologia versioni saranno disponibili dopo il primo salvataggio.',
        'Clear all meals from this week?': 'Rimuovere tutti i pasti assegnati a questa settimana?',
        'Search recipes...': 'Cerca ricette...',
        'Search recipes or cuisine...': 'Cerca ricette o cucina...',
        'Search categories...': 'Cerca categorie...',
        'Search tags...': 'Cerca tag...',
        'Top categories': 'Categorie più usate',
        'Top ingredients': 'Ingredienti più usati',
        'No categories yet': 'Nessuna categoria',
        'Selected items': 'Elementi selezionati',
        'Additional attachment': 'Allegato aggiuntivo',
        'Generate with AI': 'Genera con AI',
        'AI meal planner': 'Pianificatore pasti AI',
        'Cookbook assistant': 'Assistente del ricettario',
        'Ask your cookbook': 'Chiedi al tuo ricettario',
        'Ask about recipes, available ingredients or meal ideas.': 'Chiedi informazioni su ricette, ingredienti disponibili o idee per i pasti.',
        'What can I cook with eggs, zucchini and Parmesan?': 'Cosa posso cucinare con uova, zucchine e parmigiano?',
        'Ask': 'Chiedi',
        'Ask a question about your cookbook': 'Fai una domanda sul tuo ricettario',
        'Answer': 'Risposta',
        'Recommended recipes': 'Ricette consigliate',
        'No recipe was recommended. Try asking with ingredients, a dietary preference or a time limit.': 'Non è stata consigliata nessuna ricetta. Prova a indicare ingredienti, preferenze alimentari o un limite di tempo.',
        'The assistant uses the AI provider configured in Settings and only the recipes you can access.': 'L’assistente usa il provider AI configurato nelle Impostazioni e solo le ricette a cui puoi accedere.',
        'Weekly instruction (optional)': 'Istruzione per questa settimana (opzionale)',
        'Meal plan generated': 'Piano pasti generato',
        'Dietary preferences and constraints': 'Preferenze alimentari e vincoli',
        'Maximum cooking time per meal': 'Tempo massimo di cucina per pasto',
        'Default servings': 'Porzioni predefinite',
        'Planner prompt': 'Prompt del pianificatore',
        'Example: use more legumes and prepare leftovers for lunch': 'Esempio: usa più legumi e prepara gli avanzi per il pranzo',
        'Example: vegetarian, no peanuts, low salt': 'Esempio: vegetariana, senza arachidi, poco sale',
        'Import previews': 'Anteprime di importazione',
        'Received imports': 'Importazioni ricevute',
        'Imports sent from SmartCook Connector appear here.': 'Qui compaiono le importazioni inviate da SmartCook Connector.',
        'Refresh': 'Aggiorna',
        'Open preview': 'Apri anteprima',
        'Waiting for processing': 'In attesa di elaborazione',
        'Processing import': 'Importazione in elaborazione',
        'Import failed': 'Importazione non riuscita',
        'No received imports yet': 'Nessuna importazione ricevuta',
        Administration: 'Amministrazione',
        'Manage recipe lists': 'Gestisci gli elenchi delle ricette',
        'Manage list': 'Gestisci elenco',
        'Add values to your lists, then assign or remove each value from all your recipes. Deleting a value also removes it from every recipe.': 'Aggiungi valori agli elenchi, quindi assegnali o rimuovili da tutte le ricette. Eliminando un valore viene rimosso da ogni ricetta.',
        'New value...': 'Nuovo valore...',
        recipes: 'ricette',
        'Assign to all': 'Assegna a tutte',
        'Remove from all': 'Rimuovi da tutte',
        'No values yet': 'Nessun valore',
        'Bulk editing': 'Modifica massiva',
        'Select recipes': 'Seleziona ricette',
        'recipes selected': 'ricette selezionate',
        'Clear selected recipes': 'Deseleziona ricette',
        'Filter recipes...': 'Filtra ricette...',
        'Select filtered recipes': 'Seleziona ricette filtrate',
        'No recipes found': 'Nessuna ricetta trovata',
        'Select recipes, then assign or remove values from the lists below. Deleting a value removes it from every recipe.': 'Seleziona le ricette, quindi assegna o rimuovi i valori dagli elenchi sottostanti. Eliminando un valore viene rimosso da ogni ricetta.',
        'Assign to selected': 'Assegna alle selezionate',
        'Remove from selected': 'Rimuovi dalle selezionate',
        'Delete selected recipes': 'Elimina ricette selezionate',
        'Delete the selected recipes? This cannot be undone.': 'Eliminare le ricette selezionate? L’operazione non può essere annullata.',
        'recipes deleted': 'ricette eliminate',
        'Filter recipes with this value': 'Filtra ricette con questo valore',
        'Filtered by': 'Filtrate per',
        'Clear filter': 'Rimuovi filtro',
        'Clear filters': 'Rimuovi filtri',
        'Analyzing recipes': 'Analisi delle ricette',
        'Applying approved changes': 'Applicazione delle modifiche approvate',
        'Please wait while approved changes are saved.': 'Attendi mentre vengono salvate le modifiche approvate.',
        'Recipe %s of %s': 'Ricetta %s di %s',
        'Cancel analysis': 'Interrompi analisi',
        'Analysis interrupted': 'Analisi interrotta',
        Current: 'Attuale',
        Proposed: 'Proposto',
        'AI refinement': 'Raffinamento AI',
        'Analyzing recipe with AI': 'Analisi della ricetta con AI',
        'Preparing your proposal...': 'Preparazione della proposta in corso...',
        '1 - Very easy': '1 - Facilissima',
        '2 - Easy': '2 - Facile',
        '3 - Medium': '3 - Media',
        '4 - Difficult': '4 - Difficile',
        '5 - Very difficult': '5 - Molto difficile',
        'Difficulty:': 'Difficoltà:',
        'Optimize organization with AI': 'Ottimizza l’organizzazione con AI',
        'Review AI proposal': 'Controlla proposta AI',
        'Improve recipe organization': 'Migliora l’organizzazione delle ricette',
        'AI reviews identity and organization. It can propose a procedure only when one is missing. Ingredients and existing procedure are never changed. Review every proposal before applying it.': 'L’AI esamina identità e organizzazione. Può proporre un procedimento solo quando manca. Ingredienti e procedimento esistente non vengono mai modificati. Controlla ogni proposta prima di applicarla.',
        'Select all recipes': 'Seleziona tutte le ricette',
        'Analyze selected recipes': 'Analizza le ricette selezionate',
        'Preview': 'Anteprima',
        'Suggested changes': 'Modifiche proposte',
        'Apply approved changes': 'Applica le modifiche approvate',
        'Apply this proposal': 'Applica questa proposta',
        'No changes proposed': 'Nessuna modifica proposta',
        'Find a cover image': 'Trova un’immagine di copertina',
        'Apply the approved changes to the selected recipes?': 'Applicare le modifiche approvate alle ricette selezionate?',
        'recipes updated': 'ricette aggiornate',
        'Assign this value to the selected recipes?': 'Assegnare questo valore alle ricette selezionate?',
        'Remove this value from the selected recipes?': 'Rimuovere questo valore dalle ricette selezionate?',
        'Value added': 'Valore aggiunto',
        'Value assigned to all recipes': 'Valore assegnato a tutte le ricette',
        'Value removed from all recipes': 'Valore rimosso da tutte le ricette',
        'Value deleted': 'Valore eliminato',
        'Assign this value to all your recipes?': 'Assegnare questo valore a tutte le tue ricette?',
        'Remove this value from all your recipes?': 'Rimuovere questo valore da tutte le tue ricette?',
        'Delete this value and remove it from all your recipes?': 'Eliminare questo valore e rimuoverlo da tutte le tue ricette?',
        'Loading...': 'Caricamento...',
        'Select import': 'Seleziona importazione',
        'Delete import': 'Elimina',
        'Delete this received import?': 'Eliminare questa importazione ricevuta?',
        'Imported recipes saved': 'Ricette importate salvate',
        'Importing file': 'Importazione file',
        'Importing recipe': 'Importazione ricetta',
        of: 'di',
        'Please wait while the source is analyzed.': 'Attendi mentre la fonte viene analizzata.',
        'recipes extracted. Review them before saving.': 'ricette estratte. Controllale prima di salvarle.',
        'Save all recipes': 'Salva tutte le ricette',
        'Saving recipe': 'Salvataggio ricetta',
		'Choose a recipe to review': 'Scegli una ricetta da controllare',
		'Apply this tag to every imported recipe': 'Applica questo tag a tutte le ricette importate',
		'Ingredients (one per line)': 'Ingredienti (uno per riga)',
		'Quantity | unit | ingredient | notes': 'Quantità | unità | ingrediente | note',
		'Steps (one per line)': 'Passaggi (uno per riga)',
		Steps: 'Passaggi',
		'Imported classification': 'Classificazione importata',
		'Suggested cover photo': 'Foto di copertina suggerita',
		'AI suggestion': 'Suggerimento AI',
		'Servings per recipe': 'Porzioni per ricetta',
		'Add': 'Aggiungi',
		'Cost': 'Costo',
		'Cost and currency': 'Costo e valuta',
		'kcal x 1 serving': 'kcal x 1 porzione',
		'Planning details help': 'Indica porzioni e resa per ricalcolare la ricetta; i tempi sono espressi in minuti e vengono sommati automaticamente.',
		'Exclude from meal planner': 'Escludi dal pianificatore pasti',
		'Choose existing values or type a new one. Press Enter or comma to add it.': 'Scegli un valore esistente oppure scrivine uno nuovo. Premi Invio o virgola per aggiungerlo.',
		'Select an option': 'Seleziona un’opzione',
		'Spring': 'Primavera',
		'Summer': 'Estate',
		'Autumn': 'Autunno',
		'Winter': 'Inverno',
        'Select one or more files. Images and PDFs require a configured OCR/document extractor.': 'Seleziona uno o più file. Immagini e PDF richiedono un estrattore OCR/documenti configurato.',
        'Some files could not be imported': 'Non è stato possibile importare alcuni file',
        'Some recipes could not be saved': 'Non è stato possibile salvare alcune ricette',
        'Timer quantity': 'Quantità timer',
        'Timer unit': 'Unità timer',
        'View recipes': 'Visualizza ricette',
        'Select one or more categories': 'Seleziona una o più categorie',
        'Select one or more tags': 'Seleziona uno o più tag',
        'Find cover image': 'Trova immagine di copertina',
        'Open source': 'Apri fonte',
        'Cover image found': 'Immagine di copertina trovata',
        'Cover saved locally': 'Copertina salvata localmente',
        'Cover removed': 'Copertina rimossa',
        'Save cover locally': 'Salva copertina localmente',
        'Remove cover': 'Rimuovi copertina',
        'Remove cover?': 'Rimuovere la copertina?',
        'The current cover is hosted externally. Save it locally to make it reliable and available in attachments.': 'La copertina attuale è ospitata esternamente. Salvala in locale per renderla affidabile e disponibile negli allegati.',
        'Some covers could not be saved': 'Non è stato possibile salvare alcune copertine',
        'Remove attachment?': 'Rimuovere l’allegato?',
        'Attachment removed': 'Allegato rimosso',
        'Google image search': 'Ricerca immagini Google',
        'Image search provider': 'Provider ricerca immagini',
        'Pexels API key': 'Chiave API Pexels',
        'Unsplash access key': 'Chiave di accesso Unsplash',
        'Remove stored Pexels key': 'Rimuovi chiave Pexels salvata',
        'Remove stored Unsplash key': 'Rimuovi chiave Unsplash salvata',
        'Find covers for all missing recipes': 'Trova copertine per tutte le ricette senza immagine',
        'This will search for and download covers for all your recipes without an image. Continue?': 'Saranno cercate e scaricate le copertine per tutte le tue ricette senza immagine. Continuare?',
        'Cover search completed': 'Ricerca copertine completata',
        failed: 'non riuscite',
        'Choose a cover image': 'Scegli un’immagine di copertina',
        'Use this image': 'Usa questa immagine',
		'Search terms': 'Termini di ricerca',
		Search: 'Cerca',
		'Show 10 more images': 'Mostra altre 10 immagini',
        'Programmable Search engine ID': 'ID motore Programmable Search',
        'Google API key': 'Chiave API Google',
        'Key already stored; leave blank to keep it': 'Chiave già salvata; lascia vuoto per conservarla',
        'Remove stored Google key': 'Rimuovi chiave Google salvata',
        'Searches Google Images using the recipe title and saves the first suitable result as the cover.': 'Cerca in Google Immagini usando il titolo della ricetta e salva il primo risultato idoneo come copertina.',
        'Uses the selected provider with the recipe title and saves the first suitable result as the cover.': 'Usa il provider selezionato con il titolo della ricetta e salva il primo risultato idoneo come copertina.',
        'OCR setup': 'Configurazione OCR',
        'Set up local OCR': 'Configura OCR locale',
        'SmartCook uses Tesseract for images and pdftotext for PDFs. Install these tools in the operating system or container that runs Nextcloud; they are not included in the SmartCook app.': 'SmartCook usa Tesseract per le immagini e pdftotext per i PDF. Installa questi strumenti nel sistema operativo o nel container che esegue Nextcloud: non sono inclusi nell’app SmartCook.',
        'Run this from the Unraid terminal when the Nextcloud container is named Nextcloud and is based on Debian.': 'Esegui questo dal terminale Unraid quando il container Nextcloud si chiama Nextcloud ed è basato su Debian.',
        'Verify the commands and language packs as the Nextcloud web user.': 'Verifica i comandi e i pacchetti lingua come utente web di Nextcloud.',
        'Other Linux systems': 'Altri sistemi Linux',
        'Install the matching packages in the operating system or container that runs Nextcloud.': 'Installa i pacchetti corrispondenti nel sistema operativo o nel container che esegue Nextcloud.',
        'After installing': 'Dopo l’installazione',
        'Set the extractor to Local Tesseract / pdftotext, use ita+eng when both languages are installed, then save the settings. Packages installed directly in a container can be lost when it is recreated; use a custom image for a permanent setup.': 'Imposta l’estrattore su Tesseract / pdftotext locale, usa ita+eng quando sono installate entrambe le lingue, quindi salva le impostazioni. I pacchetti installati direttamente nel container possono essere persi quando viene ricreato: usa un’immagine personalizzata per una configurazione permanente.',
    },
};
const tr = (text) => {
    const language = String(document.documentElement.lang || '').toLowerCase().split('-')[0];
    return fallbackTranslations[language]?.[text] || (smartWindow.t ? smartWindow.t(appId, text) : text);
};
const displayUnit = (unit) => {
    const language = String(document.documentElement.lang || '').toLowerCase().split('-')[0];
    if (language !== 'it')
        return String(unit ?? '');
    return ({
        tsp: 'cucchiaino', tbsp: 'cucchiaio', cup: 'tazza', pc: 'pz', clove: 'spicchio',
        pinch: 'pizzico', to_taste: 'q.b.',
    })[String(unit ?? '').toLowerCase()] || String(unit ?? '');
};
const mealLabel = (slot) => tr({ breakfast: 'Breakfast', lunch: 'Lunch', dinner: 'Dinner', snack: 'Snack' }[slot] || 'Meal');
const esc = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
const attr = esc;
const safeExternalUrl = (value) => {
    const text = String(value ?? '');
    return /^https?:\/\//i.test(text) ? text : '';
};
const sourceServiceName = (url) => {
    try {
        const host = new URL(url).hostname.replace(/^www\./i, '').toLowerCase();
        if (/(^|\.)youtube\.com$|(^|\.)youtu\.be$/.test(host))
            return 'YouTube';
        if (/(^|\.)facebook\.com$|(^|\.)fb\.watch$/.test(host))
            return 'Facebook';
        if (/(^|\.)instagram\.com$/.test(host))
            return 'Instagram';
        if (/(^|\.)tiktok\.com$/.test(host))
            return 'TikTok';
        return host;
    }
    catch {
        return '';
    }
};
const asNumber = (value, fallback = 0) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
};
const asText = (value) => String(value ?? '').trim();
const dateIso = (value) => {
    const local = new Date(value.getTime() - value.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 10);
};
const appUrl = (path) => smartWindow.OC?.generateUrl
    ? smartWindow.OC.generateUrl(`/apps/${appId}${path}`)
    : `/index.php/apps/${appId}${path}`;
const appVersion = '1.0.40';
const appIconUrl = `/custom_apps/smartcook/img/app-${appVersion}.svg?v=${appVersion}`;
const faviconUrl = `/custom_apps/smartcook/img/favicon-${appVersion}.ico?v=${appVersion}`;

function installFavicon() {
	let icon = document.head.querySelector('link[data-smartcook-favicon]');
	if (!icon) {
		icon = document.createElement('link');
		icon.rel = 'icon';
		icon.dataset.smartcookFavicon = 'true';
		document.head.append(icon);
	}
	icon.type = 'image/x-icon';
	icon.href = faviconUrl;
}

installFavicon();
const mediaUrl = (id) => appUrl(`/media/${id}`);
const formatBytes = (value) => {
    if (!value || value < 0)
        return '—';
    if (value < 1024)
        return `${value} B`;
    if (value < 1024 * 1024)
        return `${(value / 1024).toFixed(1)} KB`;
    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
};
const formatMediaDate = (value) => value ? new Date(value * 1000).toLocaleString() : '—';
const recipeImageUrl = (value) => {
    const text = String(value ?? '');
    const stored = /^media:(\d+)$/.exec(text);
    return stored ? mediaUrl(Number(stored[1])) : safeExternalUrl(text);
};
const exportUrl = (id, format) => appUrl(`/recipes/${id}/export/${format}`);
class ApiError extends Error {
    constructor(message, status, fields = {}) {
        super(message);
        this.status = status;
        this.fields = fields;
    }
}
async function request(path, options = {}) {
    const headers = new Headers(options.headers || {});
    headers.set('Accept', 'application/json');
    if (options.json !== undefined) {
        headers.set('Content-Type', 'application/json');
        options.body = JSON.stringify(options.json);
    }
    if (smartWindow.OC?.requestToken) {
        headers.set('requesttoken', smartWindow.OC.requestToken);
    }
    const response = await fetch(appUrl(path), { ...options, headers, credentials: 'same-origin' });
    const contentType = response.headers.get('content-type') || '';
    const data = contentType.includes('json') ? await response.json() : await response.text();
    if (!response.ok) {
        const body = typeof data === 'object' && data !== null ? data : {};
        throw new ApiError(body.error || `${tr('Request failed')} (${response.status})`, response.status, body.errors || {});
    }
    return data;
}
function setBusy(active) {
    busyCount = Math.max(0, busyCount + (active ? 1 : -1));
    const node = document.querySelector('[data-smartcook-busy]');
    if (node) {
        node.hidden = busyCount === 0;
        node.textContent = tr('Working...');
    }
}
async function working(operation) {
    setBusy(true);
    clearNotice('error');
    try {
        return await operation();
    }
    catch (error) {
        showNotice(error instanceof Error ? error.message : tr('Unexpected error'), 'error');
        throw error;
    }
    finally {
        setBusy(false);
    }
}
function showNotice(message, type = 'success') {
    const holder = document.querySelector('[data-smartcook-notices]');
    if (!holder)
        return;
    holder.innerHTML = `<div class="notice ${type}" role="${type === 'error' ? 'alert' : 'status'}"><span>${esc(message)}</span><button type="button" aria-label="${attr(tr('Close'))}">${fa('xmark')}</button></div>`;
    holder.querySelector('button')?.addEventListener('click', () => { holder.innerHTML = ''; });
    window.clearTimeout(messageTimer);
    if (type === 'success')
        messageTimer = window.setTimeout(() => { holder.innerHTML = ''; }, 4000);
}
function clearNotice(type) {
    const notice = document.querySelector('[data-smartcook-notices] .notice');
    if (!notice || (type && !notice.classList.contains(type)))
        return;
    notice.remove();
}
function emptyRecipe() {
    return {
        title: '', subtitle: null, description: null, language: document.documentElement.lang || 'en', author: null,
        sourceName: null, sourceUrl: null, license: null, status: 'draft', visibility: 'private', favorite: false, excludeFromPlanner: false,
        servings: 4, yieldText: null, prepTime: 0, restTime: 0, cookTime: 0, totalTime: 0, difficulty: null,
        costCents: null, currency: 'EUR', cuisine: null, mealType: null, cookingMethod: null,
        season: null, calories: null, nutrition: {}, notes: null, imagePath: null,
        ingredients: [{ name: '', quantity: null, unit: null }], steps: [{ text: '' }], tools: [], tags: [], categories: [], media: [],
    };
}
function splitNames(value) {
    return [...new Set(value.split(/[,;]+/).map(item => item.trim()).filter(Boolean))].map(name => ({ name }));
}
function shellTitle(section, id) {
    const titles = {
        dashboard: tr('Dashboard'), recipes: tr('Recipes'), recipe: tr('Recipe overview'), editor: id ? tr('Edit recipe') : tr('New recipe'),
        import: tr('Import'), assistant: tr('Cookbook assistant'), planner: tr('Meal planner'), shopping: tr('Shopping lists'), administration: tr('Administration'), settings: tr('Settings'),
    };
    return titles[section] || 'SmartCook';
}
function refinementFieldLabel(field) {
    return tr({ title: 'Title', subtitle: 'Subtitle', description: 'Description', author: 'Author', sourceName: 'Source name', sourceUrl: 'Source URL', cuisine: 'Cuisine', mealType: 'Meal type', cookingMethod: 'Cooking method', season: 'Season', calories: 'kcal x 1 serving', nutrition: 'Nutrition', steps: 'Procedure', tools: 'Tools', tags: 'Tags', categories: 'Categories' }[field] || field);
}
function refinementValue(value) {
    if (Array.isArray(value)) return value.map(item => typeof item === 'object' ? (item.text || item.name) : item).filter(Boolean).join(', ');
    if (value && typeof value === 'object') return Object.entries(value).map(([key, item]) => `${key}: ${item}`).join(', ');
    return String(value);
}
function refinementComparison(current, proposed) {
    const present = value => value === null || value === undefined || value === '' || (Array.isArray(value) && !value.length) ? '—' : refinementValue(value);
    return `<span class="refinement-comparison"><small><b>${esc(tr('Current'))}</b>${esc(present(current))}</small><small><b>${esc(tr('Proposed'))}</b>${esc(present(proposed))}</small></span>`;
}
function shellIntro(section) {
    if (section === 'administration')
        return `<div class="page-intro"><p>${esc(tr('Select recipes, then assign or remove values from the lists below. Deleting a value removes it from every recipe.'))}</p></div>`;
    return '';
}
function parseRoute() {
    const [path, query = ''] = (location.hash.replace(/^#\/?/, '') || 'dashboard').split('?');
    const parts = path.split('/');
    const params = new URLSearchParams(query);
    if (parts[0] === 'recipes' && parts[1]) {
        const id = asNumber(parts[1]) || undefined;
        return { section: parts[2] === 'edit' ? 'editor' : 'recipe', id, params };
    }
    if (parts[0] === 'new')
        return { section: 'editor', params };
    return { section: parts[0] || 'dashboard', params };
}
const FA_NAV_ICONS = {
    dashboard: 'gauge', recipes: 'book-open', import: 'file-import', assistant: 'wand-magic-sparkles',
    planner: 'calendar-days', shopping: 'bookmark', administration: 'shield-halved', settings: 'gear',
};
function fa(name, className = '') {
    return `<i class="fa-solid fa-${name}${className ? ` ${className}` : ''}" aria-hidden="true"></i>`;
}
function navIcon(route) {
    return fa(FA_NAV_ICONS[route] || 'circle', 'nav-icon');
}
function renderShell(section, id) {
    if (!root)
        throw new Error('SmartCook root was not found');
    const nav = [
        ['dashboard', tr('Dashboard')], ['recipes', tr('Recipes')], ['import', tr('Import')], ['assistant', tr('Cookbook assistant')],
        ['planner', tr('Meal planner')], ['shopping', tr('Shopping lists')], ['administration', tr('Administration')], ['settings', tr('Settings')],
    ];
    root.innerHTML = `<div class="smartcook-shell">
		<aside class="smartcook-sidebar" aria-label="SmartCook">
			<div class="brand"><img src="${attr(appIconUrl)}" alt=""><div><strong>SmartCook</strong><span>${esc(tr('Recipe intelligence'))}</span></div></div>
			<nav>${nav.map(([route, label]) => `<a class="${section === route || (route === 'recipes' && ['recipe', 'editor'].includes(section)) ? 'active' : ''}" href="#/${route}">${navIcon(route)}<span>${esc(label)}</span></a>`).join('')}</nav>
			${section === 'dashboard' ? `<a class="primary mobile-create" href="#/new" aria-label="${attr(tr('New recipe'))}"><span class="mobile-create-icon">${fa('plus')}</span><span class="mobile-create-label">${esc(tr('New recipe'))}</span></a>` : ''}
		</aside>
		<main class="smartcook-content">
			<header class="page-header"><div><h1>${esc(shellTitle(section, id))}</h1>${shellIntro(section)}</div><div class="busy" data-smartcook-busy hidden>${esc(tr('Working...'))}</div></header>
			<div data-smartcook-notices></div>
			<div data-smartcook-view class="view-stack"></div>
		</main>
	</div>`;
    return root.querySelector('[data-smartcook-view]');
}
function recipeThumb(recipe) {
    const image = recipeImageUrl(recipe.imagePath);
    return image
        ? `<img src="${attr(image)}" alt="">`
        : `<span>${esc((recipe.title || '?').slice(0, 1).toUpperCase())}</span>`;
}
async function renderDashboard(view) {
    view.innerHTML = `<div class="skeleton-grid"><div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div></div>`;
    const stats = await working(() => request('/stats'));
    const metricIcon = (kind) => fa({ recipes: 'book-open', favorites: 'heart' }[kind] || 'circle');
    const tagWeightClass = (index) => index === 0 ? 'tag-lg' : index <= 2 ? 'tag-md' : '';
    view.innerHTML = `<section class="metric-grid">
		<article><div class="metric-icon metric-icon-recipes">${metricIcon('recipes')}</div><div><span>${esc(tr('Recipes'))}</span><strong>${stats.recipeCount}</strong></div></article>
		<article><div class="metric-icon metric-icon-favorites">${metricIcon('favorites')}</div><div><span>${esc(tr('Favorites'))}</span><strong>${stats.favoriteCount}</strong></div></article>
	</section>
	<section class="two-column">
		<article class="panel"><div class="panel-heading"><div><p class="eyebrow">${esc(tr('Library'))}</p><h2>${esc(tr('Recently updated'))}</h2></div><a class="dashboard-view-all" href="#/recipes"><span>${esc(tr('View all'))}</span><i aria-hidden="true"></i></a></div>
			${stats.recentRecipes.length ? `<div class="compact-list">${stats.recentRecipes.map(recipe => `<a href="#/recipes/${recipe.id}"><div class="recipe-thumb">${recipeThumb(recipe)}</div><div><strong>${esc(recipe.title)}</strong><small>${esc(recipe.cuisine || tr('Uncategorized'))} - ${asNumber(recipe.totalTime)} min</small></div><i class="compact-list-arrow" aria-hidden="true"></i></a>`).join('')}</div>` : `<div class="empty-state"><h3>${esc(tr('Your cookbook is ready'))}</h3><p>${esc(tr('Create a recipe or import one from a webpage or text.'))}</p><a class="primary" href="#/import">${esc(tr('Import a recipe'))}</a></div>`}
		</article>
		<div class="view-stack">
			<article class="panel dashboard-cloud"><h2>${esc(tr('Top categories'))}</h2><div class="tag-cloud">${(stats.topCategories || []).map((item, index) => `<a class="${tagWeightClass(index)}" href="#/recipes?categories=${encodeURIComponent(item.name)}">${esc(item.name)} <b>${item.count}</b></a>`).join('') || `<small>${esc(tr('No categories yet'))}</small>`}</div></article>
			<article class="panel dashboard-cloud"><h2>${esc(tr('Top ingredients'))}</h2>${stats.topIngredients.length ? `<div class="meter-list">${(() => { const max = Math.max(...stats.topIngredients.map(item => item.count)); return stats.topIngredients.map(item => `<a href="#/recipes?ingredients=${encodeURIComponent(item.name)}"><span class="meter-label">${esc(item.name)}</span><span class="meter-track"><span class="meter-fill" style="width:${Math.max(8, Math.round(item.count / max * 100))}%"></span></span><span class="meter-value">${item.count}</span></a>`).join(''); })()}</div>` : `<small>${esc(tr('No data yet'))}</small>`}</article>
			<article class="panel dashboard-cloud"><h2>${esc(tr('Top tags'))}</h2><div class="tag-cloud">${stats.topTags.map(item => `<a href="#/recipes?tags=${encodeURIComponent(item.name)}">#${esc(item.name)} <b>${item.count}</b></a>`).join('') || `<small>${esc(tr('No tags yet'))}</small>`}</div></article>
		</div>
	</section>`;
}
function taxonomyPickerLabel(kind) {
	return kind === 'categories' ? tr('Categories') : tr('Tags');
}
function taxonomyTriggerText(kind, count) {
	const label = taxonomyPickerLabel(kind);
	return count ? `${label} (${count})` : label;
}
function taxonomyPicker(kind, items, selected) {
	const searchLabel = kind === 'categories' ? tr('Search categories...') : tr('Search tags...');
	return `<div class="taxonomy-picker" data-taxonomy-picker="${attr(kind)}"><button class="taxonomy-picker-trigger" data-taxonomy-trigger type="button" aria-expanded="false"><span data-taxonomy-count>${esc(taxonomyTriggerText(kind, selected.length))}</span><span class="taxonomy-picker-chevron" aria-hidden="true"></span></button><div class="taxonomy-picker-menu" data-taxonomy-menu hidden><input data-taxonomy-search type="search" autocomplete="off" placeholder="${attr(searchLabel)}"><div class="taxonomy-picker-options">${items.map(item => { const name = String(item.name || ''); return `<label data-taxonomy-option><input type="checkbox" value="${attr(name)}"${selected.includes(name) ? ' checked' : ''}><span>${esc(name)}</span></label>`; }).join('')}<p data-taxonomy-empty hidden>${esc(tr('No data yet'))}</p></div></div></div>`;
}
function bindTaxonomyPicker(view, kind, selected, onChange) {
	const picker = view.querySelector(`[data-taxonomy-picker="${kind}"]`);
	if (!picker)
		return;
	const trigger = picker.querySelector('[data-taxonomy-trigger]');
	const menu = picker.querySelector('[data-taxonomy-menu]');
	const search = picker.querySelector('[data-taxonomy-search]');
	const count = picker.querySelector('[data-taxonomy-count]');
	const options = [...picker.querySelectorAll('[data-taxonomy-option]')];
	const empty = picker.querySelector('[data-taxonomy-empty]');
	const update = () => {
		selected.splice(0, selected.length, ...options.filter(option => option.querySelector('input')?.checked).map(option => option.querySelector('input')?.value || ''));
		if (count)
			count.textContent = taxonomyTriggerText(kind, selected.length);
		onChange();
	};
	const filter = () => {
		const query = search?.value.trim().toLocaleLowerCase() || '';
		let visible = 0;
		options.forEach(option => {
			const matches = !query || option.textContent?.toLocaleLowerCase().includes(query);
			option.hidden = !matches;
			visible += matches ? 1 : 0;
		});
		if (empty)
			empty.hidden = visible > 0;
	};
	const close = () => { menu.hidden = true; trigger?.setAttribute('aria-expanded', 'false'); };
	trigger?.addEventListener('click', () => {
		menu.hidden = !menu.hidden;
		trigger.setAttribute('aria-expanded', menu.hidden ? 'false' : 'true');
		if (!menu.hidden)
			search?.focus();
	});
	search?.addEventListener('input', filter);
	search?.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
	options.forEach(option => option.querySelector('input')?.addEventListener('change', update));
	view.addEventListener('click', event => { if (!picker.contains(event.target)) close(); });
}
async function renderRecipes(view, routeParams = new URLSearchParams()) {
    const taxonomy = await working(() => request('/taxonomy'));
    const selectedTags = [...new Set(routeParams.getAll('tags'))];
    const selectedCategories = [...new Set(routeParams.getAll('categories'))];
    view.innerHTML = `<section class="toolbar toolbar-pills panel">
		<label class="search-field"><span>${fa('magnifying-glass')}</span><input data-search placeholder="${attr(tr('Search recipes or cuisine...'))}"></label>
		<div class="taxonomy-filter">${taxonomyPicker('categories', taxonomy.categories || [], selectedCategories)}</div>
		<div class="taxonomy-filter">${taxonomyPicker('tags', taxonomy.tags || [], selectedTags)}</div>
		<label class="check-inline"><input data-favorites type="checkbox"> ${esc(tr('Favorites only'))}</label>
		<label>${esc(tr('Sort'))}<select data-sort><option value="updated_at">${esc(tr('Recently updated'))}</option><option value="title:ASC">${esc(tr('Title'))} A-Z</option><option value="title:DESC">${esc(tr('Title'))} Z-A</option><option value="total_time">${esc(tr('Total time'))}</option><option value="cook_count">${esc(tr('Most cooked'))}</option></select></label>
		<a class="primary" href="#/new">+ ${esc(tr('New recipe'))}</a>
	</section><section data-recipe-results></section>`;
    const results = view.querySelector('[data-recipe-results]');
    const search = view.querySelector('[data-search]');
    const favorites = view.querySelector('[data-favorites]');
    const sort = view.querySelector('[data-sort]');
    const ingredients = routeParams.getAll('ingredients');
    const filterLabel = [...selectedCategories, ...selectedTags.map(name => `#${name}`), ...ingredients].join(', ');
    if (filterLabel) {
        search.setAttribute('aria-label', `${tr('Search recipes or cuisine...')}: ${filterLabel}`);
        search.title = filterLabel;
    }
    const recipeSortKey = 'smartcook.recipe-sort';
    try {
        const savedSort = window.localStorage.getItem(recipeSortKey);
        if (savedSort && [...sort.options].some(option => option.value === savedSort))
            sort.value = savedSort;
    }
    catch (_) {
        // Continue with the default ordering when browser storage is unavailable.
    }
    let timer = 0;
    const load = async () => {
        const [sortField, direction] = sort.value.split(':');
        const params = new URLSearchParams({ search: search.value, favorite: favorites.checked ? '1' : '', sort: sortField, ...(direction ? { direction } : {}) });
        selectedTags.forEach(tag => params.append('tags[]', tag));
        selectedCategories.forEach(category => params.append('categories[]', category));
        ingredients.forEach(ingredient => params.append('ingredients', ingredient));
        const payload = await working(() => request(`/recipes?${params.toString()}`));
        const recipes = payload.recipes;
        results.innerHTML = recipes.length ? `<div class="recipe-grid">${recipes.map(recipe => {
            const image = recipeImageUrl(recipe.imagePath);
            const totalTime = asNumber(recipe.totalTime) || asNumber(recipe.prepTime) + asNumber(recipe.restTime) + asNumber(recipe.cookTime);
            const categories = (recipe.categories || []).map(category => String(category?.name || category || '').trim()).filter(Boolean);
            return `<article class="recipe-card"><a class="recipe-image" href="#/recipes/${recipe.id}">${image ? `<img src="${attr(image)}" alt="">` : `<div class="image-placeholder"><span>${esc(recipe.title.slice(0, 1).toUpperCase())}</span></div>`}<span class="recipe-image-overlay" aria-hidden="true"></span>${categories.length ? `<span class="recipe-badge">${esc(categories[0])}</span>` : ''}${totalTime > 0 ? `<span class="time-pill">${fa('clock')} ${totalTime} min</span>` : ''}</a><button class="recipe-fav" data-favorite-id="${recipe.id}" data-favorite="${recipe.favorite ? '1' : '0'}" aria-label="${attr(tr('Toggle favorite'))}">${fa(recipe.favorite ? 'star' : 'star', recipe.favorite ? '' : 'fa-regular')}</button>
			<div class="recipe-card-body"><a href="#/recipes/${recipe.id}"><h2>${esc(recipe.title)}</h2></a><p>${esc(recipe.description || tr('No description'))}</p><div class="card-meta">${asNumber(recipe.servings) > 0 ? `<span class="card-meta-item">${fa('utensils')} ${asNumber(recipe.servings)} ${esc(tr('servings'))}</span>` : ''}${difficultyIndicator(recipe.difficulty)}</div></div></article>`;
        }).join('')}</div>` : `<section class="panel empty-state"><h2>${esc(tr('No recipes found'))}</h2><p>${esc(tr('Change the filters, create a recipe, or import one from a URL.'))}</p><div><a class="primary" href="#/import">${esc(tr('Import recipe'))}</a> <a class="secondary" href="#/new">${esc(tr('Create manually'))}</a></div></section>`;
        results.querySelectorAll('[data-favorite-id]').forEach(button => button.addEventListener('click', async () => {
            const id = asNumber(button.dataset.favoriteId);
            const newValue = button.dataset.favorite !== '1';
            await working(() => request(`/recipes/${id}/favorite`, { method: 'POST', json: { favorite: newValue } }));
            button.dataset.favorite = newValue ? '1' : '0';
            button.innerHTML = fa('star', newValue ? '' : 'fa-regular');
            showNotice(newValue ? tr('Added to favorites') : tr('Removed from favorites'));
        }));
    };
    const delayed = () => { window.clearTimeout(timer); timer = window.setTimeout(() => { void load(); }, 250); };
    search.addEventListener('input', delayed);
	bindTaxonomyPicker(view, 'categories', selectedCategories, () => { void load(); });
	bindTaxonomyPicker(view, 'tags', selectedTags, () => { void load(); });
    favorites.addEventListener('change', () => { void load(); });
    sort.addEventListener('change', () => {
        try {
            window.localStorage.setItem(recipeSortKey, sort.value);
        }
        catch (_) {
            // The selected order still applies for the current page.
        }
        void load();
    });
    await load();
}
function textInput(label, field, value, options = {}) {
    return `<label class="${attr(options.className || '')}">${esc(label)}<input data-field="${attr(field)}" type="${attr(options.type || 'text')}" value="${attr(value)}"${options.placeholder ? ` placeholder="${attr(options.placeholder)}"` : ''}${options.min !== undefined ? ` min="${options.min}"` : ''}${options.step !== undefined ? ` step="${options.step}"` : ''}></label>`;
}
function labelInput(label, control, className = '') {
    return `<label class="${attr(className)}">${esc(label)}${control}</label>`;
}
function textareaInput(label, field, value, rows = 4, className = '') {
    return `<label class="${attr(className)}">${esc(label)}<textarea data-field="${attr(field)}" rows="${rows}">${esc(value)}</textarea></label>`;
}
function selectInput(label, field, value, options, className = '') {
    return `<label class="${attr(className)}">${esc(label)}<select data-field="${attr(field)}">${options.map(([id, name]) => `<option value="${attr(id)}"${String(value ?? '') === id ? ' selected' : ''}>${esc(name)}</option>`).join('')}</select></label>`;
}
function predefinedOptions(currentValue, options) {
    const current = String(currentValue || '').trim();
    return current && !options.some(([value]) => value === current) ? [[current, current], ...options] : options;
}
function alternativeRow(item = { name: '' }) {
	return `<div class="ingredient-alternative-row" data-alternative-row><input data-alternative-quantity value="${attr(item.quantity)}" placeholder="${attr(tr('Qty'))}" aria-label="${attr(tr('Quantity'))}"><input data-alternative-unit value="${attr(displayUnit(item.unit))}" placeholder="${attr(tr('Unit'))}" aria-label="${attr(tr('Unit'))}"><input data-alternative-name value="${attr(item.name)}" placeholder="${attr(tr('Alternative ingredient'))}" aria-label="${attr(tr('Alternative ingredient'))}"><input data-alternative-notes value="${attr(item.notes)}" placeholder="${attr(tr('Notes'))}" aria-label="${attr(tr('Notes'))}"><button class="icon-button danger" data-remove-alternative type="button" aria-label="${attr(tr('Remove'))}">${fa('xmark')}</button></div>`;
}
function ingredientRow(item = { name: '' }) {
    return `<div class="ingredient-row" data-ingredient-row>
		<input data-ing-quantity value="${attr(item.quantity)}" placeholder="${attr(tr('Qty'))}" aria-label="${attr(tr('Quantity'))}">
		<input data-ing-unit value="${attr(displayUnit(item.unit))}" placeholder="${attr(tr('Unit'))}" aria-label="${attr(tr('Unit'))}">
		<input data-ing-name value="${attr(item.name)}" placeholder="${attr(tr('Ingredient'))}" aria-label="${attr(tr('Ingredient'))}">
		<input data-ing-notes value="${attr(item.notes)}" placeholder="${attr(tr('Notes'))}" aria-label="${attr(tr('Notes'))}">
        <label class="tiny-check"><input data-ing-optional type="checkbox"${item.optional ? ' checked' : ''}> ${esc(tr('Optional'))}</label>
        <button class="icon-button danger" data-remove-row type="button" aria-label="${attr(tr('Remove'))}">${fa('xmark')}</button>
		<div class="ingredient-alternatives"><div><strong>${esc(tr('Alternatives'))}</strong><button class="ghost" data-add-alternative type="button">${fa('plus')} ${esc(tr('Add alternative'))}</button></div><div data-alternative-rows>${(item.alternatives || []).map(alternative => alternativeRow(alternative)).join('')}</div></div>
	</div>`;
}
function timerParts(seconds) {
    const value = Math.max(0, Number(seconds || 0));
    if (value >= 3600 && value % 3600 === 0)
        return { value: value / 3600, unit: 'hours' };
    return { value: value ? value / 60 : null, unit: 'minutes' };
}
function timerSeconds(value, unit) {
    if (value === null || value === undefined || value === 0)
        return null;
    const amount = Math.max(0, Number(value || 0));
    return unit === 'hours' ? Math.round(amount * 3600) : Math.round(amount * 60);
}
function stepRow(item = { text: '' }, index = 0, total = 1) {
    const timer = timerParts(item.timerSeconds);
    return `<div class="step-row" data-step-row><span class="step-number">${index + 1}</span><textarea data-step-text rows="3" placeholder="${attr(tr('Describe this step...'))}">${esc(item.text)}</textarea><div class="step-extras"><input data-step-timer type="number" min="0" step="any" value="${attr(timer.value)}" placeholder="${attr(tr('Timer quantity'))}"><select data-step-timer-unit aria-label="${attr(tr('Timer unit'))}"><option value="minutes"${timer.unit === 'minutes' ? ' selected' : ''}>${esc(tr('minutes'))}</option><option value="hours"${timer.unit === 'hours' ? ' selected' : ''}>${esc(tr('hours'))}</option></select><input data-step-temp type="number" value="${attr(item.temperature)}" placeholder="${attr(tr('Temperature'))}"><select data-step-temp-unit><option value="C"${item.temperatureUnit === 'C' ? ' selected' : ''}>C</option><option value="F"${item.temperatureUnit === 'F' ? ' selected' : ''}>F</option></select></div><div class="step-move-actions"><button class="icon-button" data-move-step="up" type="button" aria-label="${attr(tr('Move step up'))}" title="${attr(tr('Move step up'))}"${index === 0 ? ' disabled' : ''}>${fa('arrow-up')}</button><button class="icon-button" data-move-step="down" type="button" aria-label="${attr(tr('Move step down'))}" title="${attr(tr('Move step down'))}"${index === total - 1 ? ' disabled' : ''}>${fa('arrow-down')}</button></div><button class="icon-button danger" data-remove-row type="button" aria-label="${attr(tr('Remove'))}">${fa('xmark')}</button></div>`;
}
function recipeViewer(recipe) {
    const image = recipeImageUrl(recipe.imagePath);
	const sourceUrl = safeExternalUrl(recipe.sourceUrl);
	const sourceLabel = String(sourceServiceName(sourceUrl) || recipe.sourceName || '').trim();
	const categories = (recipe.categories || []).map(category => String(category?.name || category || '').trim()).filter(Boolean);
	const categoryLabel = categories.join(' · ') || tr('Uncategorized');
	const calories = asNumber(recipe.calories);
    const ingredientItems = recipe.ingredients.map(item => {
        const measure = [item.quantity, displayUnit(item.unit)].filter(Boolean).join(' ');
		const alternatives = (item.alternatives || []).map(alternative => [alternative.quantity, displayUnit(alternative.unit), alternative.name].filter(Boolean).join(' ')).filter(Boolean);
        return `<li><span>${esc(measure)}</span><strong>${esc(item.name)}</strong>${item.optional ? `<em>${esc(tr('Optional'))}</em>` : ''}${item.notes ? `<small>${esc(item.notes)}</small>` : ''}${alternatives.length ? `<small class="recipe-ingredient-alternatives">${esc(tr('Alternatives'))}: ${esc(alternatives.join(' · '))}</small>` : ''}</li>`;
    }).join('');
    const steps = recipe.steps.map((step, index) => {
        const details = [];
        if (step.timerSeconds) {
            const timer = timerParts(step.timerSeconds);
            details.push(`${timer.value} ${tr(timer.unit)}`);
        }
        if (step.temperature)
            details.push(`${step.temperature}°${step.temperatureUnit || 'C'}`);
        return `<li><span class="recipe-step-number">${index + 1}</span><div><p>${esc(step.text)}</p>${details.length ? `<small>${esc(details.join(' · '))}</small>` : ''}</div></li>`;
    }).join('');
    return `<div class="recipe-view-shell"><article class="recipe-view panel">
        <header class="recipe-view-header"><div class="recipe-view-image-wrap">${image ? `<img class="recipe-view-image" src="${attr(image)}" alt="">` : `<div class="recipe-view-image image-placeholder" aria-hidden="true"><span>${esc(recipe.title.slice(0, 1).toUpperCase())}</span></div><button class="cover-search-button" data-find-cover type="button" title="${attr(tr('Find cover image'))}" aria-label="${attr(tr('Find cover image'))}">${fa('wand-magic-sparkles')}</button>`}</div><div class="recipe-view-heading"><p class="eyebrow">${esc(categoryLabel)}</p><h2>${esc(recipe.title)}</h2>${recipe.subtitle ? `<p class="recipe-view-subtitle">${esc(recipe.subtitle)}</p>` : ''}${recipe.description ? `<p class="recipe-view-description">${esc(recipe.description)}</p>` : ''}${sourceUrl ? `<div class="recipe-view-actions"><a class="secondary recipe-source-link" href="${attr(sourceUrl)}" target="_blank" rel="noopener noreferrer" title="${attr(tr('Open source'))}">${fa('arrow-up-right-from-square')}${esc(tr('Open source'))}${sourceLabel ? `: ${esc(sourceLabel)}` : ''}</a></div>` : ''}</div></header>
        <div class="recipe-view-meta"><span><strong>${asNumber(recipe.servings)}</strong> ${esc(tr('servings'))}</span>${calories > 0 ? `<span><strong>${calories}</strong> ${esc(tr('kcal x 1 serving'))}</span>` : ''}<span class="recipe-view-time-marker" aria-hidden="true"></span><span><strong>${asNumber(recipe.prepTime)}</strong> ${esc(tr('prep'))}</span><span><strong>${asNumber(recipe.cookTime)}</strong> ${esc(tr('cook'))}</span><span><strong>${asNumber(recipe.totalTime)}</strong> ${esc(tr('total'))}</span></div>
        <div class="recipe-view-content"><section><h3>${esc(tr('Ingredients'))}</h3><ul class="recipe-view-ingredients">${ingredientItems || `<li>${esc(tr('No data yet'))}</li>`}</ul></section><section><h3>${esc(tr('Procedure'))}</h3><ol class="recipe-view-steps">${steps || `<li>${esc(tr('No data yet'))}</li>`}</ol></section></div>
    </article>${recipe.id ? `<div class="recipe-view-footer-actions"><details class="recipe-view-download"><summary>${esc(tr('Download'))}${fa('chevron-down', 'recipe-dropdown-icon')}</summary><div><a href="${attr(exportUrl(recipe.id, 'json'))}">JSON-LD</a><a href="${attr(exportUrl(recipe.id, 'markdown'))}">Markdown</a><a href="${attr(exportUrl(recipe.id, 'html'))}">HTML</a></div></details><details class="recipe-view-actions-menu"><summary>${esc(tr('Actions'))}${fa('chevron-down', 'recipe-dropdown-icon')}</summary><div><button data-share-recipe type="button">${fa('share-nodes')}<span>${esc(tr('Share'))}</span></button>${recipe.excludeFromPlanner ? '' : `<button data-add-to-today type="button">${fa('calendar-plus')}<span>${esc(tr('Add to today\'s plan'))}</span></button>`}<button data-add-to-shopping type="button">${fa('cart-plus')}<span>${esc(tr('Add to shopping list'))}</span></button><button data-refine-recipe type="button">${fa('wand-magic-sparkles')}<span>${esc(tr('Optimize organization with AI'))}</span></button><a href="#/recipes/${recipe.id}/edit">${fa('pen-to-square')}<span>${esc(tr('Edit recipe'))}</span></a></div></details></div>` : ''}</div>`;
}
function difficultyScore(value) {
    const text = String(value ?? '').trim().toLocaleLowerCase();
    if (/^[1-5]$/.test(text)) return Number(text);
    if (/\b(?:molto|very)\s+(?:difficil|hard)|difficilissim|expert|avanzatissim|altissim/.test(text)) return 5;
    if (/facilissim|molto\s+facil|very\s+easy|beginner|bassissim|\bbassa\b/.test(text)) return 1;
    if (/\b(?:difficil|hard|avanzat|\balta\b)/.test(text)) return 4;
    if (/\b(?:facil|easy|semplic|low)/.test(text)) return 2;
    if (/\b(?:medi|moder|normal|intermedi)/.test(text)) return 3;
    return 0;
}
function difficultyIndicator(value) {
    const score = difficultyScore(value);
    if (!score) return '';
    return `<span class="card-meta-item card-meta-difficulty" aria-label="${attr(`${tr('Difficulty:')} ${score}/5`)}"><b>${esc(tr('Difficulty:'))}</b><span aria-hidden="true">${fa('bolt').repeat(score)}</span></span>`;
}
function showRefinementLoading() {
    const modal = document.createElement('div');
    modal.className = 'blocking-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'smartcook-refinement-loading-title');
    modal.innerHTML = `<div class="blocking-modal-card"><div class="loading-spinner" aria-hidden="true"></div><h2 id="smartcook-refinement-loading-title">${esc(tr('Analyzing recipe with AI'))}</h2><p>${esc(tr('Preparing your proposal...'))}</p></div>`;
    document.body.append(modal);
    return () => modal.remove();
}
function reviewRecipeRefinement(recipe, proposal) {
    return new Promise(resolve => {
        const fields = Object.entries(proposal.proposal || {});
        const selected = new Set(fields.map(([field]) => field));
        let includeCover = !!proposal.addCover;
        const modal = document.createElement('div');
        modal.className = 'cover-picker-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-label', tr('Review AI proposal'));
        const close = approved => { modal.remove(); resolve(approved); };
        const render = () => {
            const rows = fields.map(([field, value]) => `<label class="check-inline"><input data-refinement-field="${attr(field)}" type="checkbox"${selected.has(field) ? ' checked' : ''}><span><strong>${esc(refinementFieldLabel(field))}</strong>${refinementComparison(proposal.current?.[field], value)}</span></label>`).join('');
            modal.innerHTML = `<div class="cover-picker-card refinement-proposal"><div class="section-heading"><div><p class="eyebrow">${esc(tr('AI refinement'))}</p><h2>${esc(proposal.title || recipe.title)}</h2><p class="section-help">${esc(tr('AI reviews identity and organization. It can propose a procedure only when one is missing. Ingredients and existing procedure are never changed. Review every proposal before applying it.'))}</p></div><button class="icon-button" data-close-refinement type="button" aria-label="${attr(tr('Close'))}">${fa('xmark')}</button></div>${rows ? `<div class="refinement-fields">${rows}</div>` : `<p class="section-help">${esc(tr('No changes proposed'))}</p>`}${proposal.addCover ? `<label class="check-inline"><input data-refinement-cover type="checkbox"${includeCover ? ' checked' : ''}><span><strong>${esc(tr('Find a cover image'))}</strong><small>${esc(proposal.coverSuggestion || tr('AI suggestion'))}</small></span></label>` : ''}<div class="button-row"><button class="primary" data-apply-refinement type="button"${selected.size || includeCover ? '' : ' disabled'}>${esc(tr('Apply approved changes'))}</button><button class="ghost" data-close-refinement type="button">${esc(tr('Cancel'))}</button></div></div>`;
            modal.querySelectorAll('[data-close-refinement]').forEach(button => button.addEventListener('click', () => close(null)));
            modal.querySelectorAll('[data-refinement-field]').forEach(input => input.addEventListener('change', () => { input.checked ? selected.add(input.dataset.refinementField) : selected.delete(input.dataset.refinementField); render(); }));
            modal.querySelector('[data-refinement-cover]')?.addEventListener('change', input => { includeCover = input.target.checked; render(); });
            modal.querySelector('[data-apply-refinement]')?.addEventListener('click', () => close({ recipeId: proposal.recipeId, fields: Object.fromEntries(fields.filter(([field]) => selected.has(field))), addCover: includeCover }));
        };
        modal.addEventListener('click', event => { if (event.target === modal) close(null); });
        document.body.append(modal);
        render();
    });
}
async function shareRecipe(recipe) {
    const url = window.location.href;
    if (typeof navigator.share === 'function') {
        try {
            await navigator.share({ title: recipe.title, text: recipe.title, url });
            return;
        }
        catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError')
                return;
        }
    }
    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(url);
        }
        else {
            const input = document.createElement('textarea');
            input.value = url;
            input.setAttribute('readonly', '');
            input.style.position = 'fixed';
            input.style.opacity = '0';
            document.body.append(input);
            input.select();
            const copied = document.execCommand('copy');
            input.remove();
            if (!copied)
                throw new Error('Copy failed');
        }
        showNotice(tr('Link copied'));
    }
    catch {
        showNotice(tr('Could not copy the link'), 'error');
    }
}
function coverPreviewUrl(recipeId, thumbnailUrl) {
    return appUrl(`/recipes/${recipeId}/cover/preview?url=${encodeURIComponent(thumbnailUrl)}`);
}
function chooseCoverCandidate(recipeId, initialQuery, candidates) {
    return new Promise(resolve => {
        let query = String(initialQuery || '');
        let page = 1;
        let safeCandidates = (candidates || []).filter(candidate => safeExternalUrl(candidate.url) && safeExternalUrl(candidate.thumbnailUrl));
        const modal = document.createElement('div');
        modal.className = 'cover-picker-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-label', tr('Choose a cover image'));
        const close = (candidate = null) => { modal.remove(); resolve(candidate); };
		const render = () => {
			modal.innerHTML = `<div class="cover-picker-card"><div class="section-heading"><h2>${esc(tr('Choose a cover image'))}</h2><button class="icon-button" data-close-cover-picker type="button" aria-label="${attr(tr('Close'))}">${fa('xmark')}</button></div><form class="cover-picker-search" data-cover-search-form><label>${esc(tr('Search terms'))}<input data-cover-search-query name="smartcook-cover-search" value="${attr(query)}" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" data-bwignore="true"></label><button class="secondary" type="submit">${esc(tr('Search'))}</button></form><div class="cover-picker-grid">${safeCandidates.map((candidate, index) => `<button data-cover-candidate="${index}" type="button"><img src="${attr(coverPreviewUrl(recipeId, candidate.thumbnailUrl))}" alt=""><span>${esc(candidate.label || tr('Use this image'))}</span></button>`).join('')}</div><div class="button-row"><button class="secondary" data-more-cover-candidates type="button">${esc(tr('Show 10 more images'))}</button><button class="ghost" data-close-cover-picker type="button">${esc(tr('Cancel'))}</button></div></div>`;
			modal.querySelectorAll('[data-close-cover-picker]').forEach(button => button.addEventListener('click', () => close()));
			modal.querySelectorAll('[data-cover-candidate]').forEach(button => button.addEventListener('click', () => close(safeCandidates[asNumber(button.dataset.coverCandidate)] || null)));
			modal.querySelector('[data-cover-search-form]')?.addEventListener('submit', async event => {
				event.preventDefault();
				query = modal.querySelector('[data-cover-search-query]')?.value.trim() || initialQuery;
				page = 1;
				await loadCandidates();
			});
			modal.querySelector('[data-more-cover-candidates]')?.addEventListener('click', async () => {
				query = modal.querySelector('[data-cover-search-query]')?.value.trim() || initialQuery;
				page++;
				await loadCandidates();
			});
		};
		const loadCandidates = async () => {
			try {
				const response = await request(`/recipes/${recipeId}/cover/search`, { method: 'POST', json: { query, page } });
				safeCandidates = (response.candidates || []).filter(candidate => safeExternalUrl(candidate.url) && safeExternalUrl(candidate.thumbnailUrl));
				render();
			}
			catch (error) {
				showNotice(error instanceof Error ? error.message : tr('Unexpected error'), 'error');
			}
		};
        modal.addEventListener('click', event => { if (event.target === modal) close(); });
        document.body.append(modal);
		render();
        modal.querySelector('[data-cover-search-query]')?.focus();
    });
}
function chooseShoppingList(recipe, lists) {
    return new Promise(resolve => {
        const modal = document.createElement('div');
        modal.className = 'cover-picker-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'smartcook-shopping-list-title');
        const close = (selection = null) => {
            document.removeEventListener('keydown', onKeyDown);
            modal.remove();
            resolve(selection);
        };
        const onKeyDown = event => {
            if (event.key === 'Escape')
                close();
        };
        const listChoices = (lists || []).map(list => `<label class="check-inline shopping-list-choice"><input data-shopping-list-choice type="radio" name="smartcook-shopping-list" value="${asNumber(list.id)}"><span><strong>${esc(list.name)}</strong><small>${asNumber(list.itemCount)} ${esc(tr('Items'))}</small></span></label>`).join('');
        modal.innerHTML = `<div class="cover-picker-card shopping-list-picker"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Shopping list'))}</p><h2 id="smartcook-shopping-list-title">${esc(tr('Select or create a list'))}</h2><p class="section-help">${esc(tr('Choose where to add this recipe or create a new list.'))}</p></div><button class="icon-button" data-close-shopping-list-picker type="button" aria-label="${attr(tr('Close'))}">${fa('xmark')}</button></div><div class="shopping-list-choices">${listChoices}<label class="check-inline shopping-list-choice"><input data-shopping-list-choice type="radio" name="smartcook-shopping-list" value="new" checked><span><strong>${esc(tr('Create a new list'))}</strong><small>${esc(recipe.title)}</small></span></label></div><div class="form-grid"><label class="shopping-list-name">${esc(tr('List name'))}<input data-new-shopping-list-name value="${attr(`${tr('Shopping list')}: ${recipe.title}`)}"></label><label>${esc(tr('Servings'))}<input data-shopping-servings type="number" min="1" step="1" value="${asNumber(recipe.servings, 1)}"></label></div><div class="button-row"><button class="primary" data-confirm-shopping-list type="button">${esc(tr('Add to selected list'))}</button><button class="ghost" data-close-shopping-list-picker type="button">${esc(tr('Cancel'))}</button></div></div>`;
        modal.querySelectorAll('[data-close-shopping-list-picker]').forEach(button => button.addEventListener('click', () => close()));
        const updateNameField = () => {
            const input = modal.querySelector('[data-new-shopping-list-name]');
            if (input)
                input.disabled = modal.querySelector('[data-shopping-list-choice]:checked')?.value !== 'new';
        };
        modal.querySelectorAll('[data-shopping-list-choice]').forEach(input => input.addEventListener('change', updateNameField));
        updateNameField();
        modal.querySelector('[data-confirm-shopping-list]')?.addEventListener('click', () => {
            const selected = modal.querySelector('[data-shopping-list-choice]:checked')?.value;
            const servings = Math.max(1, asNumber(modal.querySelector('[data-shopping-servings]')?.value, asNumber(recipe.servings, 1)));
            if (selected === 'new') {
                close({ name: modal.querySelector('[data-new-shopping-list-name]')?.value.trim() || `${tr('Shopping list')}: ${recipe.title}`, servings });
                return;
            }
            const listId = asNumber(selected);
            if (listId > 0)
                close({ listId, servings });
        });
        modal.addEventListener('click', event => { if (event.target === modal) close(); });
        document.body.append(modal);
        document.addEventListener('keydown', onKeyDown);
        modal.querySelector('[data-new-shopping-list-name]')?.focus();
    });
}
async function renderRecipe(view, id) {
    const recipe = (await working(() => request(`/recipes/${id}`))).recipe;
    view.innerHTML = recipeViewer(recipe);
    view.querySelector('[data-find-cover]')?.addEventListener('click', async () => {
        const response = await working(() => request(`/recipes/${recipe.id}/cover/search`, { method: 'POST', json: { query: recipe.title, page: 1 } }));
        const candidate = await chooseCoverCandidate(recipe.id, recipe.title, response.candidates);
        if (!candidate)
            return;
        await working(() => request(`/recipes/${recipe.id}/cover`, { method: 'POST', json: { url: candidate.url, downloadUrl: candidate.downloadUrl || '' } }));
        showNotice(tr('Cover image found'));
        await renderRecipe(view, id);
    });
    view.querySelector('[data-add-to-today]')?.addEventListener('click', async () => {
        await working(() => request('/planner', { method: 'POST', json: { meal: { date: dateIso(new Date()), recipeId: recipe.id, slot: 'dinner', servings: Math.max(1, asNumber(recipe.servings, 1)) } } }));
        showNotice(tr('Meal added'));
    });
    view.querySelector('[data-add-to-shopping]')?.addEventListener('click', async () => {
        const lists = (await working(() => request('/shopping'))).lists;
        const selection = await chooseShoppingList(recipe, lists);
        if (!selection)
            return;
        const recipes = [{ recipeId: recipe.id, servings: selection.servings }];
        if (selection.listId) {
            await working(() => request(`/shopping/${selection.listId}/recipes`, { method: 'POST', json: { recipes } }));
            showNotice(tr('Recipe added to shopping list'));
            return;
        }
        await working(() => request('/shopping', { method: 'POST', json: { name: selection.name, recipes } }));
        showNotice(tr('Shopping list created'));
    });
    view.querySelector('[data-refine-recipe]')?.addEventListener('click', async () => {
        const closeLoading = showRefinementLoading();
        let response;
        try {
            response = await working(() => request('/recipes/refinement/analyze', { method: 'POST', json: { recipeIds: [recipe.id] } }));
        } finally {
            closeLoading();
        }
        const approved = await reviewRecipeRefinement(recipe, response.proposals?.[0] || {});
        if (!approved || (!Object.keys(approved.fields).length && !approved.addCover))
            return;
        const result = await working(() => request('/recipes/refinement/apply', { method: 'POST', json: { proposals: [approved] } }));
        showNotice(`${asNumber(result.changed)} ${tr('recipes updated')}`);
        await renderRecipe(view, id);
    });
    view.querySelector('[data-share-recipe]')?.addEventListener('click', () => { void shareRecipe(recipe); });
}
function bindRowRemoval(container) {
    container.querySelectorAll('[data-remove-row]').forEach(button => button.addEventListener('click', () => {
        const row = button.closest('[data-ingredient-row], [data-step-row]');
        if (!row)
            return;
        const message = row.matches('[data-step-row]') ? 'Remove this step?' : 'Remove this ingredient?';
        if (!window.confirm(tr(message)))
            return;
		const holder = row.parentElement;
        row.remove();
		if (holder?.matches('[data-steps]'))
			updateStepRows(holder);
    }));
}
function updateStepRows(container) {
    const rows = [...container.querySelectorAll('[data-step-row]')];
    rows.forEach((row, index) => {
        const number = row.querySelector('.step-number');
        if (number)
            number.textContent = String(index + 1);
        const up = row.querySelector('[data-move-step="up"]');
        const down = row.querySelector('[data-move-step="down"]');
        if (up)
            up.disabled = index === 0;
        if (down)
            down.disabled = index === rows.length - 1;
    });
}
function bindStepReordering(container) {
    container.querySelectorAll('[data-move-step]').forEach(button => button.addEventListener('click', () => {
        const row = button.closest('[data-step-row]');
        const holder = row?.parentElement;
        if (!row || !holder)
            return;
        if (button.dataset.moveStep === 'up') {
            const previous = row.previousElementSibling;
            if (previous)
                holder.insertBefore(row, previous);
        }
        else {
            const next = row.nextElementSibling;
            if (next)
                holder.insertBefore(next, row);
        }
        updateStepRows(holder);
    }));
}
function bindIngredientAlternatives(container) {
	container.querySelectorAll('[data-add-alternative]').forEach(button => button.addEventListener('click', () => {
		const holder = button.closest('[data-ingredient-row]')?.querySelector('[data-alternative-rows]');
		if (!holder)
			return;
		holder.insertAdjacentHTML('beforeend', alternativeRow());
		bindIngredientAlternatives(holder);
	}));
	container.querySelectorAll('[data-remove-alternative]').forEach(button => button.addEventListener('click', () => button.closest('[data-alternative-row]')?.remove()));
}
function collectRecipe(view, existing) {
    const fallbackValue = (name) => {
        if (name === 'costAmount')
            return existing.costCents === null || existing.costCents === undefined ? '' : String(asNumber(existing.costCents) / 100);
        if (['tags', 'categories', 'tools'].includes(name))
            return (existing[name] || []).map(item => String(item?.name || item || '')).filter(Boolean).join(', ');
        return existing[name] ?? '';
    };
    const value = (name) => view.querySelector(`[data-field="${name}"]`)?.value ?? fallbackValue(name);
    const numeric = (name) => asNumber(value(name));
    const ingredientRows = [...view.querySelectorAll('[data-ingredient-row]')];
    const ingredients = ingredientRows.length ? ingredientRows.map(row => ({
        name: row.querySelector('[data-ing-name]')?.value.trim() || '',
        quantity: row.querySelector('[data-ing-quantity]')?.value.trim() || null,
        unit: row.querySelector('[data-ing-unit]')?.value.trim() || null,
        notes: row.querySelector('[data-ing-notes]')?.value.trim() || null,
        optional: row.querySelector('[data-ing-optional]')?.checked || false,
		alternatives: [...row.querySelectorAll('[data-alternative-row]')].map(alternative => ({
			name: alternative.querySelector('[data-alternative-name]')?.value.trim() || '',
			quantity: alternative.querySelector('[data-alternative-quantity]')?.value.trim() || null,
			unit: alternative.querySelector('[data-alternative-unit]')?.value.trim() || null,
			notes: alternative.querySelector('[data-alternative-notes]')?.value.trim() || null,
		})).filter(alternative => alternative.name),
    })).filter(item => item.name) : (existing.ingredients || []);
    const stepRows = [...view.querySelectorAll('[data-step-row]')];
    const steps = stepRows.length ? stepRows.map(row => ({
        text: row.querySelector('[data-step-text]')?.value.trim() || '',
        timerSeconds: timerSeconds(asNumber(row.querySelector('[data-step-timer]')?.value) || null, row.querySelector('[data-step-timer-unit]')?.value),
        temperature: asNumber(row.querySelector('[data-step-temp]')?.value) || null,
        temperatureUnit: row.querySelector('[data-step-temp-unit]')?.value || null,
    })).filter(item => item.text) : (existing.steps || []);
    const prepTime = numeric('prepTime');
    const restTime = numeric('restTime');
    const cookTime = numeric('cookTime');
    return {
        ...existing,
        title: value('title').trim(), subtitle: value('subtitle').trim() || null, description: value('description').trim() || null,
        language: value('language').trim() || 'en', author: value('author').trim() || null, sourceName: value('sourceName').trim() || null,
        sourceUrl: value('sourceUrl').trim() || null, license: value('license').trim() || null,
        status: value('status'), visibility: value('visibility'),
        excludeFromPlanner: view.querySelector('[data-field="excludeFromPlanner"]')?.checked ?? Boolean(existing.excludeFromPlanner),
        servings: Math.max(1, numeric('servings')), yieldText: value('yieldText').trim() || null,
        prepTime, restTime, cookTime, totalTime: prepTime + restTime + cookTime,
        difficulty: value('difficulty').trim() || null, costCents: Math.round(Math.max(0, numeric('costAmount')) * 100) || null, currency: value('currency').trim().toUpperCase() || null,
        cuisine: value('cuisine').trim() || null, mealType: value('mealType').trim() || null,
        cookingMethod: value('cookingMethod').trim() || null, season: value('season').trim() || null,
        calories: numeric('calories') || null, notes: value('notes').trim() || null,
        ingredients, steps, tags: splitNames(value('tags')), categories: splitNames(value('categories')), tools: splitNames(value('tools')),
    };
}
function chipPicker(name, selected, suggestions, single = false) {
	const values = [...new Map(selected.map(item => String(item?.name || item).trim()).filter(Boolean).map(item => [item.toLocaleLowerCase(), item])).values()].slice(0, single ? 1 : undefined);
	const options = [...new Map(suggestions.map(item => String(item?.name || item).trim()).filter(Boolean).map(item => [item.toLocaleLowerCase(), item])).values()];
	return `<div class="chip-picker" data-chip-picker="${attr(name)}"${single ? ' data-chip-single="true"' : ''}><input data-field="${attr(name)}" type="hidden" value="${attr(values.join(', '))}"><div class="chip-picker-input" data-chip-input-wrap><span data-chip-values>${values.map(value => `<button class="chip-picker-value" data-chip-remove="${attr(value)}" type="button">${esc(value)} <span aria-hidden="true">×</span></button>`).join('')}</span><input data-chip-input name="smartcook-taxonomy-${attr(name)}" type="text" autocomplete="off" readonly data-lpignore="true" data-1p-ignore="true" data-bwignore="true" role="combobox" aria-autocomplete="list" aria-expanded="false"></div><div class="chip-picker-options" data-chip-options role="listbox" hidden>${options.map(value => `<button data-chip-option="${attr(value)}" type="button" role="option">${esc(value)}</button>`).join('')}<p data-chip-empty hidden>${esc(tr('No data yet'))}</p></div></div>`;
}
function bindChipPicker(view, name) {
	const picker = view.querySelector(`[data-chip-picker="${name}"]`);
	const hidden = picker?.querySelector(`[data-field="${name}"]`);
	const input = picker?.querySelector('[data-chip-input]');
	const options = picker?.querySelector('[data-chip-options]');
	const valuesHolder = picker?.querySelector('[data-chip-values]');
	if (!picker || !hidden || !input || !options || !valuesHolder)
		return;
	const single = picker.dataset.chipSingle === 'true';
	const available = [...options.querySelectorAll('[data-chip-option]')].map(option => option.dataset.chipOption || '');
	let values = [...new Map(String(hidden.value || '').split(/[,;]+/).map(value => value.trim()).filter(Boolean).map(value => [value.toLocaleLowerCase(), value])).values()].slice(0, single ? 1 : undefined);
	const sync = () => { hidden.value = values.join(', '); valuesHolder.innerHTML = values.map(value => `<button class="chip-picker-value" data-chip-remove="${attr(value)}" type="button">${esc(value)} <span aria-hidden="true">×</span></button>`).join(''); };
	const close = () => { options.hidden = true; input.setAttribute('aria-expanded', 'false'); };
	const renderOptions = () => {
		const query = input.value.trim();
		const normalizedQuery = query.toLocaleLowerCase();
		let count = 0;
		[...options.querySelectorAll('[data-chip-option]')].forEach(option => {
			const value = option.dataset.chipOption || '';
			const visible = !values.some(selected => selected.toLocaleLowerCase() === value.toLocaleLowerCase()) && (!normalizedQuery || value.toLocaleLowerCase().includes(normalizedQuery));
			option.hidden = !visible;
			count += visible ? 1 : 0;
		});
		options.querySelector('[data-chip-create]')?.remove();
		if (query && !values.some(value => value.toLocaleLowerCase() === normalizedQuery) && !available.some(value => value.toLocaleLowerCase() === normalizedQuery))
			options.insertAdjacentHTML('beforeend', `<button data-chip-create="${attr(query)}" type="button" role="option">${esc(tr('Add'))}: ${esc(query)}</button>`);
		const empty = options.querySelector('[data-chip-empty]');
		if (empty)
			empty.hidden = count > 0 || Boolean(query);
	};
	const add = (raw) => {
		String(raw || '').split(/[,;]+/).map(value => value.trim()).filter(Boolean).forEach(value => {
			if (single)
				values = [value];
			else if (!values.some(selected => selected.toLocaleLowerCase() === value.toLocaleLowerCase()))
				values.push(value);
		});
		input.value = '';
		sync();
		renderOptions();
	};
	sync();
	input.addEventListener('pointerdown', () => input.removeAttribute('readonly'), { once: true });
	input.addEventListener('focus', () => { input.removeAttribute('readonly'); options.hidden = false; input.setAttribute('aria-expanded', 'true'); renderOptions(); });
	input.addEventListener('input', () => { options.hidden = false; input.setAttribute('aria-expanded', 'true'); renderOptions(); });
	input.addEventListener('keydown', event => {
		if (event.key === 'Enter' || event.key === ',') { event.preventDefault(); add(input.value); }
		else if (event.key === 'Backspace' && !input.value && values.length) { values.pop(); sync(); renderOptions(); }
		else if (event.key === 'Escape') close();
	});
	options.addEventListener('click', event => {
		const option = event.target.closest('[data-chip-option], [data-chip-create]');
		if (option)
			add(option.dataset.chipOption || option.dataset.chipCreate || '');
	});
	valuesHolder.addEventListener('click', event => {
		const button = event.target.closest('[data-chip-remove]');
		if (!button)
			return;
		values = values.filter(value => value.toLocaleLowerCase() !== String(button.dataset.chipRemove || '').toLocaleLowerCase());
		sync();
		renderOptions();
	});
	view.addEventListener('click', event => { if (!picker.contains(event.target)) close(); });
}
function editorForm(recipe, taxonomy = {}, step = 1) {
	const costAmount = recipe.costCents === null || recipe.costCents === undefined ? '' : (asNumber(recipe.costCents) / 100).toFixed(2);
    const steps = [[tr('Identity'), tr('Recipe')], [tr('Ingredients'), tr('Structured list')], [tr('Procedure'), tr('Method')], [tr('Organization'), tr('Classification')], [tr('Advanced'), tr('Attachments')]];
    const navigation = `<nav class="recipe-wizard" aria-label="${attr(tr('Recipe details'))}">${steps.map(([label, detail], index) => `<button class="${step === index + 1 ? 'active' : step > index + 1 ? 'complete' : ''}" data-wizard-step="${index + 1}" type="button"><span>${index + 1}</span><b>${esc(label)}</b><small>${esc(detail)}</small></button>`).join('')}</nav>`;
    const identity = `<section class="form-section wizard-panel"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Identity'))}</p><h2>${esc(tr('Recipe'))}</h2></div></div><div class="form-grid">${textInput(tr('Title'), 'title', recipe.title, { className: 'span-2' })}${textInput(tr('Subtitle'), 'subtitle', recipe.subtitle, { className: 'span-2' })}${textareaInput(tr('Description'), 'description', recipe.description, 4, 'span-2')}${textInput(tr('Author'), 'author', recipe.author)}${textInput(tr('Language'), 'language', recipe.language)}${textInput(tr('Source name'), 'sourceName', recipe.sourceName)}${textInput(tr('Source URL'), 'sourceUrl', recipe.sourceUrl, { type: 'url' })}${textInput(tr('License'), 'license', recipe.license)}${selectInput(tr('Status'), 'status', recipe.status, [['draft', tr('Draft')], ['published', tr('Published')]])}${selectInput(tr('Visibility'), 'visibility', recipe.visibility, [['private', tr('Private')], ['shared', tr('Shared')], ['public', tr('Public')]])}</div><div class="section-heading compact-heading"><div><p class="eyebrow">${esc(tr('Yield and timing'))}</p><h2>${esc(tr('Planning data'))}</h2></div></div><div class="planning-grid">${textInput(tr('Servings'), 'servings', recipe.servings, { type: 'number', min: 1 })}${textInput(tr('Yield'), 'yieldText', recipe.yieldText)}${textInput(tr('Preparation (min)'), 'prepTime', recipe.prepTime, { type: 'number', min: 0 })}${textInput(tr('Rest (min)'), 'restTime', recipe.restTime, { type: 'number', min: 0 })}${textInput(tr('Cooking (min)'), 'cookTime', recipe.cookTime, { type: 'number', min: 0 })}${selectInput(tr('Difficulty'), 'difficulty', String(difficultyScore(recipe.difficulty) || ''), [['', tr('Select an option')], ['1', tr('1 - Very easy')], ['2', tr('2 - Easy')], ['3', tr('3 - Medium')], ['4', tr('4 - Difficult')], ['5', tr('5 - Very difficult')]])}${textInput(tr('Calories'), 'calories', recipe.calories, { type: 'number', min: 0 })}<label class="planning-cost">${esc(tr('Cost and currency'))}<span><input data-field="costAmount" type="number" min="0" step="0.01" inputmode="decimal" value="${attr(costAmount)}" placeholder="0.00"><input data-field="currency" value="${attr(recipe.currency || 'EUR')}" maxlength="3" aria-label="${attr(tr('Currency'))}"></span></label><label class="check-inline"><input data-field="excludeFromPlanner" type="checkbox"${recipe.excludeFromPlanner ? ' checked' : ''}> ${esc(tr('Exclude from meal planner'))}</label></div></section>`;
    const ingredients = `<section class="form-section wizard-panel"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Structured list'))}</p><h2>${esc(tr('Ingredients'))}</h2></div></div><div class="ingredient-table"><div class="ingredient-head"><span>${esc(tr('Quantity'))}</span><span>${esc(tr('Unit'))}</span><span>${esc(tr('Ingredient'))}</span><span>${esc(tr('Notes'))}</span><span>${esc(tr('Optional'))}</span></div><div data-ingredients>${(recipe.ingredients.length ? recipe.ingredients : [{ name: '' }]).map(item => ingredientRow(item)).join('')}</div></div></section>`;
    const procedureSteps = recipe.steps.length ? recipe.steps : [{ text: '' }];
    const procedure = `<section class="form-section wizard-panel"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Method'))}</p><h2>${esc(tr('Procedure'))}</h2></div></div><div class="step-list" data-steps>${procedureSteps.map((item, index) => stepRow(item, index, procedureSteps.length)).join('')}</div></section>`;
    const organization = `<section class="form-section wizard-panel"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Classification'))}</p><h2>${esc(tr('Organization'))}</h2><p class="section-help">${esc(tr('Choose existing values or type a new one. Press Enter or comma to add it.'))}</p></div></div><div class="form-grid">${labelInput(tr('Tags'), chipPicker('tags', recipe.tags || [], taxonomy.tags || []), 'span-2')}${labelInput(tr('Categories'), chipPicker('categories', recipe.categories || [], taxonomy.categories || []), 'span-2')}${labelInput(tr('Tools'), chipPicker('tools', recipe.tools || [], taxonomy.tools || []), 'span-2')}${labelInput(tr('Cuisine'), chipPicker('cuisine', [recipe.cuisine].filter(Boolean), taxonomy.cuisine || [], true))}${labelInput(tr('Meal type'), chipPicker('mealType', [recipe.mealType].filter(Boolean), taxonomy.mealType || [], true))}${labelInput(tr('Cooking method'), chipPicker('cookingMethod', [recipe.cookingMethod].filter(Boolean), taxonomy.cookingMethod || [], true))}${selectInput(tr('Season'), 'season', recipe.season, predefinedOptions(recipe.season, [['', tr('Select an option')], ['Primavera', tr('Spring')], ['Estate', tr('Summer')], ['Autunno', tr('Autumn')], ['Inverno', tr('Winter')]]))}${textareaInput(tr('Personal notes'), 'notes', recipe.notes, 5, 'span-2')}</div></section>`;
    const externalCover = safeExternalUrl(recipe.imagePath);
    const advanced = `<section class="form-section"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Advanced'))}</p><h2>${esc(tr('Advanced'))}</h2></div></div></section>`;
    const stepContent = [identity, ingredients, procedure, organization, advanced][step - 1];
	const stepAddition = step === 2 ? `<div class="wizard-add-action"><button class="secondary" data-add-ingredient type="button">+ ${esc(tr('Ingredient'))}</button></div>` : step === 3 ? `<div class="wizard-add-action"><button class="secondary" data-add-step type="button">+ ${esc(tr('Step'))}</button></div>` : '';
	return `<section class="editor-top wizard-header"><div><p class="eyebrow">${esc(recipe.id ? tr('Recipe details') : tr('Create manually'))}</p><h2>${esc(recipe.title || tr('Untitled recipe'))}</h2></div></section>${navigation}<div class="editor-layout recipe-wizard-layout"><main class="view-stack"><div class="panel">${stepContent}${step === 5 ? `<div class="advanced-panels">
			${recipe.id ? `
			<section class="panel form-section" data-media-section><p class="eyebrow">${esc(tr('Files'))}</p><h2>${esc(tr('Attachments'))}</h2>${externalCover ? `<div class="media-upload-group cover-upload"><strong>${esc(tr('Cover image'))}</strong><small>${esc(tr('The current cover is hosted externally. Save it locally to make it reliable and available in attachments.'))}</small><div class="button-row"><button class="secondary" data-store-current-cover type="button">${esc(tr('Save cover locally'))}</button><button class="danger secondary" data-remove-cover type="button">${esc(tr('Remove cover'))}</button></div></div>` : ''}<div class="media-upload-group cover-upload"><label><strong>${esc(tr('Cover image'))}</strong><input data-cover-file type="file" accept="image/*"></label><small>${esc(tr('The uploaded image becomes the recipe cover after saving.'))}</small></div><div class="media-upload-group"><label><strong>${esc(tr('Additional attachment'))}</strong><input data-media-file type="file"></label><button class="secondary" data-upload-media type="button">${esc(tr('Upload attachment'))}</button></div><ul class="media-list">${recipe.media.map(item => item.id ? `<li><a href="${attr(mediaUrl(item.id))}" target="_blank" rel="noopener"><strong>${esc(item.altText || item.path.split('/').pop() || item.kind)}</strong><small>${esc(item.mime || item.kind)} · ${formatBytes(item.fileSize)} · ${formatMediaDate(item.createdAt)}</small></a><button class="icon-button danger" data-delete-media="${attr(item.id)}" type="button" aria-label="${attr(tr('Delete'))}">${fa('xmark')}</button></li>` : '').join('')}</ul></section>
			<section class="panel form-section" data-sharing-section><p class="eyebrow">${esc(tr('Access'))}</p><h2>${esc(tr('Sharing'))}</h2><div data-share-list></div><div class="share-form"><select data-share-type><option value="link">${esc(tr('Public link'))}</option><option value="user">${esc(tr('User'))}</option><option value="group">${esc(tr('Group'))}</option></select><input data-share-with name="smartcook-share-with" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" data-bwignore="true" aria-label="${attr(tr('User or group ID'))}" placeholder="${attr(tr('User or group ID'))}"><input data-share-password name="smartcook-link-password" type="password" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" data-bwignore="true" aria-label="${attr(tr('Optional link password'))}" placeholder="${attr(tr('Optional link password'))}"><label class="check-inline"><input data-share-edit type="checkbox"> ${esc(tr('Allow editing'))}</label><button class="secondary" data-create-share type="button">${esc(tr('Create share'))}</button></div></section>
			<section class="panel form-section" data-history-section><p class="eyebrow">${esc(tr('Audit trail'))}</p><h2>${esc(tr('Version history'))}</h2><div class="version-list" data-version-list></div></section>
			<section class="panel form-section danger-zone"><h2>${esc(tr('Danger zone'))}</h2><button class="danger secondary" data-delete-recipe type="button">${esc(tr('Delete recipe'))}</button></section>` : `<section class="panel empty-state"><h2>${esc(tr('Save first'))}</h2><p>${esc(tr('Attachments, sharing and version history become available after the first save.'))}</p></section>`}
		</div>` : ''}${stepAddition}<footer class="wizard-actions"><div class="wizard-navigation-actions"><button class="secondary wizard-navigation-button" data-wizard-back type="button"${step === 1 ? ' disabled' : ''}>${fa('arrow-left')}${esc(tr('Previous'))}</button>${step < 5 ? `<button class="secondary wizard-navigation-button" data-wizard-next type="button">${esc(tr('Next'))}${fa('arrow-right')}</button>` : ''}</div><button class="primary" data-save-recipe type="button">${esc(tr('Save recipe'))}</button></footer></div></main></div>`;
}
async function renderEditor(view, id) {
    let recipe = id ? (await working(() => request(`/recipes/${id}`))).recipe : emptyRecipe();
	let wizardStep = 1;
	const [taxonomy, recipeResponse] = await working(() => Promise.all([request('/taxonomy'), request('/recipes?sort=title&direction=ASC')]));
	['cuisine', 'mealType', 'cookingMethod'].forEach(field => {
		taxonomy[field] = [...new Map((recipeResponse.recipes || []).map(item => String(item[field] || '').trim()).filter(Boolean).map(value => [value.toLocaleLowerCase(), value])).values()];
	});
    const paint = async () => {
        view.innerHTML = editorForm(recipe, taxonomy, wizardStep);
        bindRowRemoval(view);
		bindStepReordering(view);
		bindIngredientAlternatives(view);
		['tags', 'categories', 'tools', 'cuisine', 'mealType', 'cookingMethod'].forEach(name => bindChipPicker(view, name));
		const changeStep = (nextStep) => {
			recipe = collectRecipe(view, recipe);
			wizardStep = nextStep;
			void paint();
		};
		view.querySelectorAll('[data-wizard-step]').forEach(button => button.addEventListener('click', () => changeStep(asNumber(button.dataset.wizardStep))));
		view.querySelector('[data-wizard-back]')?.addEventListener('click', () => changeStep(Math.max(1, wizardStep - 1)));
		view.querySelector('[data-wizard-next]')?.addEventListener('click', () => changeStep(Math.min(5, wizardStep + 1)));
        view.querySelector('[data-add-ingredient]')?.addEventListener('click', () => {
            const holder = view.querySelector('[data-ingredients]');
            holder.insertAdjacentHTML('beforeend', ingredientRow());
            bindRowRemoval(holder);
			bindIngredientAlternatives(holder);
        });
        view.querySelector('[data-add-step]')?.addEventListener('click', () => {
            const holder = view.querySelector('[data-steps]');
            const stepCount = holder.querySelectorAll('[data-step-row]').length;
            holder.insertAdjacentHTML('beforeend', stepRow({ text: '' }, stepCount, stepCount + 1));
            bindRowRemoval(holder);
			bindStepReordering(holder);
			updateStepRows(holder);
        });
        view.querySelector('[data-save-recipe]')?.addEventListener('click', async () => {
            const payload = collectRecipe(view, recipe);
            const cover = view.querySelector('[data-cover-file]')?.files?.[0];
            const response = recipe.id
                ? await working(() => request(`/recipes/${recipe.id}`, { method: 'PUT', json: { recipe: payload } }))
                : await working(() => request('/recipes', { method: 'POST', json: { recipe: payload } }));
            recipe = response.recipe;
            if (cover && recipe.id) {
                const form = new FormData();
                form.append('file', cover);
                form.append('kind', 'image');
                form.append('altText', tr('Cover image'));
                await working(() => request(`/recipes/${recipe.id}/media`, { method: 'POST', body: form }));
                recipe = (await request(`/recipes/${recipe.id}`)).recipe;
            }
            showNotice(tr('Recipe saved'));
            if (!id && recipe.id)
                location.hash = `#/recipes/${recipe.id}`;
            else
                await paint();
        });
        view.querySelector('[data-delete-recipe]')?.addEventListener('click', async () => {
            if (!recipe.id || !window.confirm(tr('Delete this recipe permanently?')))
                return;
            await working(() => request(`/recipes/${recipe.id}`, { method: 'DELETE' }));
            showNotice(tr('Recipe deleted'));
            location.hash = '#/recipes';
        });
        view.querySelector('[data-upload-media]')?.addEventListener('click', async () => {
            if (!recipe.id)
                return;
            const file = view.querySelector('[data-media-file]')?.files?.[0];
            if (!file) {
                showNotice(tr('Choose a file first'), 'error');
                return;
            }
            const form = new FormData();
            form.append('file', file);
            await working(() => request(`/recipes/${recipe.id}/media`, { method: 'POST', body: form }));
            recipe = (await request(`/recipes/${recipe.id}`)).recipe;
            showNotice(tr('Attachment uploaded'));
            await paint();
        });
        view.querySelector('[data-store-current-cover]')?.addEventListener('click', async () => {
            if (!recipe.id || !safeExternalUrl(recipe.imagePath))
                return;
            await working(() => request(`/recipes/${recipe.id}/cover`, { method: 'POST', json: { url: recipe.imagePath } }));
            recipe = (await request(`/recipes/${recipe.id}`)).recipe;
            showNotice(tr('Cover saved locally'));
            await paint();
        });
        view.querySelector('[data-remove-cover]')?.addEventListener('click', async () => {
            if (!recipe.id || !window.confirm(tr('Remove cover?')))
                return;
            await working(() => request(`/recipes/${recipe.id}`, { method: 'PUT', json: { recipe: { ...recipe, imagePath: null } } }));
            recipe.imagePath = null;
            showNotice(tr('Cover removed'));
            await paint();
        });
        view.querySelectorAll('[data-delete-media]').forEach(button => button.addEventListener('click', async () => {
            if (!recipe.id || !window.confirm(tr('Remove attachment?')))
                return;
            await working(() => request(`/recipes/${recipe.id}/media/${asNumber(button.dataset.deleteMedia)}`, { method: 'DELETE' }));
            recipe = (await request(`/recipes/${recipe.id}`)).recipe;
            showNotice(tr('Attachment removed'));
            await paint();
        }));
        if (recipe.id) {
            await Promise.all([loadShares(view, recipe.id), loadVersions(view, recipe.id)]);
            const shareType = view.querySelector('[data-share-type]');
            const shareWith = view.querySelector('[data-share-with]');
            const sharePassword = view.querySelector('[data-share-password]');
            const shareEdit = view.querySelector('[data-share-edit]');
            const updateShareFields = () => {
                if (shareWith)
                    shareWith.disabled = shareType?.value === 'link';
                if (sharePassword)
                    sharePassword.disabled = shareType?.value !== 'link';
            };
            shareType?.addEventListener('change', updateShareFields);
            updateShareFields();
            view.querySelector('[data-create-share]')?.addEventListener('click', async () => {
                const type = shareType?.value || 'link';
                await working(() => request(`/recipes/${recipe.id}/shares`, { method: 'POST', json: { share: { type, shareWith: shareWith?.value || '', password: sharePassword?.value || '', permission: shareEdit?.checked ? 3 : 1 } } }));
                if (shareWith)
                    shareWith.value = '';
                if (sharePassword)
                    sharePassword.value = '';
                showNotice(tr('Share created'));
                await loadShares(view, recipe.id);
            });
        }
    };
    await paint();
}
async function loadShares(view, recipeId) {
    const holder = view.querySelector('[data-share-list]');
    if (!holder)
        return;
    const shares = (await request(`/recipes/${recipeId}/shares`)).shares;
    holder.innerHTML = shares.length ? shares.map(share => `<div class="share-item"><div><strong>${esc(share.type === 'link' ? tr('Public link') : share.shareWith)}</strong><small>${share.url ? `<a href="${attr(share.url)}" target="_blank" rel="noopener">${esc(tr('Open link'))}</a>` : esc(String(share.type || ''))}${share.passwordProtected ? ` - ${esc(tr('password protected'))}` : ''}</small></div><button class="icon-button danger" data-delete-share="${attr(share.id)}" type="button">${fa('xmark')}</button></div>`).join('') : `<p>${esc(tr('Not shared yet'))}</p>`;
    holder.querySelectorAll('[data-delete-share]').forEach(button => button.addEventListener('click', async () => {
        if (!window.confirm(tr('Delete this share?')))
            return;
        await working(() => request(`/recipes/${recipeId}/shares/${asNumber(button.dataset.deleteShare)}`, { method: 'DELETE' }));
        await loadShares(view, recipeId);
    }));
}
async function loadVersions(view, recipeId) {
    const holder = view.querySelector('[data-version-list]');
    if (!holder)
        return;
    const versions = (await request(`/recipes/${recipeId}/versions`)).versions;
    holder.innerHTML = versions.length ? versions.slice(0, 20).map(version => `<div class="version-item"><div><strong>v${version.revision}</strong><small>${esc(new Date(version.createdAt * 1000).toLocaleString())} - ${esc(version.userId)}</small></div><button class="ghost" data-restore-version="${version.revision}" type="button">${esc(tr('Restore'))}</button></div>`).join('') : `<p>${esc(tr('No versions yet'))}</p>`;
    holder.querySelectorAll('[data-restore-version]').forEach(button => button.addEventListener('click', async () => {
        if (!window.confirm(tr('Restore this version?')))
            return;
        await working(() => request(`/recipes/${recipeId}/restore/${asNumber(button.dataset.restoreVersion)}`, { method: 'POST', json: {} }));
        showNotice(tr('Version restored'));
        location.hash = '#/recipes';
        window.setTimeout(() => { location.hash = `#/recipes/${recipeId}`; }, 0);
    }));
}
function assistantIcon() {
    return fa('wand-magic-sparkles');
}
async function renderAssistant(view) {
    let lastQuestion = null;
    let response = null;
    const paint = () => {
        const recommendations = response?.recommendations || [];
        view.innerHTML = `<div class="assistant-chat">
			<p class="section-help assistant-intro">${esc(tr('Ask about recipes, available ingredients or meal ideas.'))}</p>
			${lastQuestion ? `<div class="assistant-bubble assistant-bubble-user"><p>${esc(lastQuestion)}</p></div>` : ''}
			${response ? `<div class="assistant-turn">
				<div class="assistant-avatar">${assistantIcon()}</div>
				<div class="assistant-bubble assistant-bubble-answer"><p>${esc(response.answer || tr('Unexpected error'))}</p></div>
			</div>
			${recommendations.length ? `<div class="assistant-recommendations-heading">${esc(tr('Recommended recipes'))}</div>
			<div class="assistant-recommendations">${recommendations.map(item => `<a class="assistant-recommendation" href="#/recipes/${asNumber(item.recipe?.id)}">${recipeThumb(item.recipe || {})}<div><strong>${esc(item.recipe?.title || tr('Untitled recipe'))}</strong><small>${esc(item.reason || '')}</small></div></a>`).join('')}</div>` : `<p class="section-help assistant-indent">${esc(tr('No recipe was recommended. Try asking with ingredients, a dietary preference or a time limit.'))}</p>`}` : ''}
			<label class="assistant-input"><span class="sr-only">${esc(tr('Ask your cookbook'))}</span><textarea data-assistant-question rows="1" placeholder="${attr(tr('What can I cook with eggs, zucchini and Parmesan?'))}"></textarea><button class="assistant-send" data-assistant-ask type="button" aria-label="${attr(tr('Ask'))}">${fa('paper-plane')}</button></label>
			<p class="section-help assistant-footnote">${esc(tr('The assistant uses the AI provider configured in Settings and only the recipes you can access.'))}</p>
		</div>`;
        view.querySelector('[data-assistant-ask]')?.addEventListener('click', async () => {
            const question = String(view.querySelector('[data-assistant-question]')?.value || '').trim();
            if (!question) {
                showNotice(tr('Ask a question about your cookbook'), 'error');
                return;
            }
            lastQuestion = question;
            const result = await working(() => request('/assistant/chat', { method: 'POST', json: { question, language: document.documentElement.lang || 'it' } }));
            response = result.response;
            paint();
        });
    };
    paint();
}

async function renderImport(view) {
    let kind = 'url';
    let previews = [];
    let savedPreviews = new Set();
	let commonImportTag = '';
    let activeExternalJobId = null;
	let taxonomy = {};
	try {
		taxonomy = await working(() => request('/taxonomy'));
	}
	catch (_) {
		// The preview remains usable if taxonomy suggestions cannot be loaded.
	}
    const setImportBusy = (active, title = tr('Importing recipe'), detail = tr('Please wait while the source is analyzed.')) => {
        const modal = view.querySelector('[data-import-loading]');
        if (modal) {
            const titleNode = modal.querySelector('[data-import-loading-title]');
            const detailNode = modal.querySelector('[data-import-loading-detail]');
            if (titleNode)
                titleNode.textContent = title;
            if (detailNode)
                detailNode.textContent = detail;
            modal.hidden = !active;
        }
    };
	const refinePreviews = async (holder, language, provider) => {
		const pending = previews.map((preview, index) => ({ preview, index })).filter(({ preview }) => !String(preview.strategy || '').includes('+ai'));
		if (!pending.length)
			return;
		try {
			for (const [position, { preview, index }] of pending.entries()) {
				setImportBusy(true, tr('AI refinement'), `${tr('Recipe')} ${position + 1} ${tr('of')} ${pending.length}: ${preview.recipe.title}`);
				try {
					const response = await request('/import/refine', { method: 'POST', json: { recipe: preview.recipe, language, provider: provider || null } });
					previews[index].recipe = response.recipe;
					previews[index].strategy = `${preview.strategy}+ai`;
				}
				catch (error) {
					previews[index].warnings = [...(preview.warnings || []), `${tr('AI refinement')}: ${error instanceof Error ? error.message : tr('Unexpected error')}`];
				}
				holder.innerHTML = importPreviewsHtml(previews, savedPreviews, commonImportTag, taxonomy);
				bindImportSave(holder);
			}
		}
		finally {
			setImportBusy(false);
		}
	};
    const paint = () => {
        view.innerHTML = `<section class="panel" data-external-import-inbox><div class="section-heading"><div><p class="eyebrow">${esc(tr('Received imports'))}</p></div><button class="ghost" data-refresh-external-imports type="button">${esc(tr('Refresh'))}</button></div><p>${esc(tr('Loading...'))}</p></section>
			<article class="panel form-section">
				<div class="source-tabs">${[['url', 'URL'], ['text', tr('Text')], ['markdown', 'Markdown'], ['json', 'JSON'], ['file', tr('File / OCR')]].map(([id, label]) => `<button type="button" data-import-kind="${id}" class="${kind === id ? 'active' : ''}">${esc(label)}</button>`).join('')}</div>
	            ${kind === 'url' ? `<label>${esc(tr('Recipe URL'))}<input data-import-url type="url" placeholder="https://example.com/recipe"></label>` : kind === 'file' ? `<label>${esc(tr('PDF, image, Markdown, HTML or JSON'))}<input data-import-file type="file" multiple accept="image/*,.pdf,.txt,.md,.markdown,.html,.htm,.json"><small>${esc(tr('Select one or more files. Images and PDFs require a configured OCR/document extractor.'))}</small></label>` : `<label>${esc(tr('Source content'))}<textarea data-import-text rows="18" placeholder="${attr(tr('Paste the recipe, including ingredients and procedure...'))}"></textarea></label>`}
				<div class="form-grid"><label>${esc(tr('Output language'))}<input data-import-language value="${attr(document.documentElement.lang || 'it')}"></label><label>${esc(tr('AI provider override'))}<select data-import-provider><option value="">${esc(tr('Use settings'))}</option><option value="nextcloud">Nextcloud Assistant</option><option value="openai">OpenAI</option><option value="openrouter">OpenRouter</option><option value="ollama">Ollama</option><option value="localai">LocalAI</option><option value="mistral">Mistral</option><option value="anthropic">Anthropic</option><option value="gemini">Gemini</option></select></label></div>
				<label class="check-inline ai-toggle"><input data-import-ai type="checkbox"><span><b>${esc(tr('Use AI refinement'))}</b><small>${esc(tr('Optional fallback for incomplete or unstructured sources'))}</small></span></label>
				<button class="primary" data-extract type="button">${esc(tr('Extract recipe data'))}</button>
			</article>
			<article class="panel preview-panel" data-import-preview>${importPreviewsHtml(previews, savedPreviews, commonImportTag, taxonomy)}</article>
		<div class="blocking-modal" data-import-loading hidden role="dialog" aria-modal="true" aria-labelledby="smartcook-import-loading-title"><div class="blocking-modal-card"><div class="loading-spinner" aria-hidden="true"></div><h2 id="smartcook-import-loading-title" data-import-loading-title>${esc(tr('Importing recipe'))}</h2><p data-import-loading-detail>${esc(tr('Please wait while the source is analyzed.'))}</p></div></div>`;
        view.querySelectorAll('[data-import-kind]').forEach(button => button.addEventListener('click', () => { kind = button.dataset.importKind; previews = []; savedPreviews = new Set(); activeExternalJobId = null; paint(); }));
        const loadInbox = async () => {
            const inbox = view.querySelector('[data-external-import-inbox]');
            if (!inbox)
                return;
            try {
                const jobs = (await request('/import/jobs?limit=50')).jobs || [];
                inbox.innerHTML = externalImportInboxHtml(jobs);
                inbox.querySelector('[data-refresh-external-imports]')?.addEventListener('click', loadInbox);
                inbox.querySelectorAll('[data-delete-external-import]').forEach(button => button.addEventListener('click', async () => {
                    if (!window.confirm(tr('Delete this received import?')))
                        return;
                    await working(() => request(`/import/jobs/${button.dataset.deleteExternalImport}`, { method: 'DELETE' }));
                    await loadInbox();
                }));
                inbox.querySelectorAll('[data-select-external-import]').forEach(button => button.addEventListener('click', () => {
                    const job = jobs.find(item => String(item.id) === String(button.dataset.selectExternalImport));
                    if (!job)
                        return;
                    kind = job.kind === 'text' ? 'text' : 'url';
                    paint();
                    const source = job.payload?.[kind] || job.sourceRef || '';
                    const sourceInput = kind === 'url' ? view.querySelector('[data-import-url]') : view.querySelector('[data-import-text]');
                    if (sourceInput)
                        sourceInput.value = source;
                    activeExternalJobId = job.id;
                    sourceInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }));
                inbox.querySelectorAll('[data-open-external-import]').forEach(button => button.addEventListener('click', () => {
                    const job = jobs.find(item => String(item.id) === String(button.dataset.openExternalImport));
                    if (!job?.result?.recipe) {
                        showNotice(tr('Waiting for processing'), 'error');
                        return;
                    }
                    previews = job.result.previews || [job.result];
                    savedPreviews = new Set();
                    activeExternalJobId = job.id;
                    const previewHolder = view.querySelector('[data-import-preview]');
                    previewHolder.innerHTML = importPreviewsHtml(previews, savedPreviews, commonImportTag, taxonomy);
                    bindImportSave(previewHolder);
                    previewHolder.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }));
            }
            catch (error) {
                inbox.innerHTML = `<div class="section-heading"><div><p class="eyebrow">${esc(tr('Received imports'))}</p></div><button class="ghost" data-refresh-external-imports type="button">${esc(tr('Refresh'))}</button></div><p>${esc(error instanceof Error ? error.message : tr('Unexpected error'))}</p>`;
                inbox.querySelector('[data-refresh-external-imports]')?.addEventListener('click', loadInbox);
            }
        };
        view.querySelector('[data-refresh-external-imports]')?.addEventListener('click', loadInbox);
        view.querySelector('[data-extract]')?.addEventListener('click', async () => {
            const language = view.querySelector('[data-import-language]')?.value || 'en';
            const useAi = view.querySelector('[data-import-ai]')?.checked || false;
            const provider = view.querySelector('[data-import-provider]')?.value || '';
            if (kind === 'file') {
                const files = Array.from(view.querySelector('[data-import-file]')?.files || []);
                if (!files.length) {
                    showNotice(tr('Choose a file first'), 'error');
                    return;
                }
                previews = [];
                savedPreviews = new Set();
                const failedFiles = [];
                try {
                    for (const [index, file] of files.entries()) {
                        setImportBusy(true, `${tr('Importing file')} ${index + 1} ${tr('of')} ${files.length}`, file.name);
                        const form = new FormData();
                        form.append('file', file);
                        form.append('language', language);
                        form.append('useAi', String(useAi));
                        if (provider)
                            form.append('provider', provider);
                        try {
                            const response = await request('/import/file', { method: 'POST', body: form });
                            previews.push(...(response.previews || [response]));
                        }
                        catch (error) {
                            failedFiles.push(`${file.name}: ${error instanceof Error ? error.message : tr('Unexpected error')}`);
                        }
                    }
                }
                finally {
                    setImportBusy(false);
                }
                if (failedFiles.length)
                    showNotice(`${tr('Some files could not be imported')}: ${failedFiles.join('; ')}`, 'error');
                if (!previews.length)
                    return;
            }
            else {
                const text = view.querySelector('[data-import-text]')?.value || '';
                const url = view.querySelector('[data-import-url]')?.value || '';
                if ((kind === 'url' && !url.trim()) || (kind !== 'url' && !text.trim())) {
                    showNotice(tr('Enter a source first'), 'error');
                    return;
                }
                setImportBusy(true);
                try {
                    const response = await working(() => request('/import/preview', { method: 'POST', json: { kind, payload: kind === 'url' ? { url, language } : { text, language }, useAi, provider: provider || null } }));
                    previews = response.previews || [response];
                    savedPreviews = new Set();
                }
                finally {
                    setImportBusy(false);
                }
            }
            showNotice(previews.length > 1 ? `${previews.length} ${tr('recipes extracted. Review them before saving.')}` : tr('Recipe data extracted. Review it before saving.'));
            const holder = view.querySelector('[data-import-preview]');
            holder.innerHTML = importPreviewsHtml(previews, savedPreviews, commonImportTag, taxonomy);
            bindImportSave(holder);
			if (useAi)
				await refinePreviews(holder, language, provider);
        });
        if (previews.length)
            bindImportSave(view.querySelector('[data-import-preview]'));
        loadInbox();
    };
    const bindImportSave = (holder) => {
		holder.querySelector('[data-import-common-tag]')?.addEventListener('input', event => {
			commonImportTag = event.currentTarget.value;
		});
		holder.querySelectorAll('[data-open-import-preview]').forEach(button => button.addEventListener('click', () => {
			const index = Number(button.dataset.openImportPreview);
			const card = holder.querySelectorAll('[data-import-preview-card]')[index];
			if (!card)
				return;
			card.open = true;
			card.scrollIntoView({ behavior: 'smooth', block: 'start' });
			card.querySelector('[data-preview-title]')?.focus({ preventScroll: true });
		}));
        holder.querySelector('[data-save-all-import]')?.addEventListener('click', async (event) => {
            const button = event.currentTarget;
            const pendingIndexes = previews.map((_, index) => index).filter(index => !savedPreviews.has(index));
            if (!pendingIndexes.length) {
                location.hash = '#/recipes';
                return;
            }
            const sharedTags = commonImportTag.split(',').map(tag => tag.trim()).filter(Boolean);
            holder.querySelectorAll('[data-import-preview-card]').forEach((card, index) => {
                const preview = previews[index];
                preview.recipe.title = card.querySelector('[data-preview-title]')?.value.trim() || preview.recipe.title;
                preview.recipe.description = card.querySelector('[data-preview-description]')?.value.trim() || preview.recipe.description;
				preview.recipe.cuisine = card.querySelector('[data-preview-cuisine]')?.value.trim() || null;
				preview.recipe.mealType = card.querySelector('[data-preview-meal-type]')?.value.trim() || null;
				preview.recipe.cookingMethod = card.querySelector('[data-preview-cooking-method]')?.value.trim() || null;
				preview.recipe.tags = [...new Set([...previewNames(card.querySelector('[data-preview-tags]')?.value), ...sharedTags])];
				preview.recipe.categories = previewNames(card.querySelector('[data-preview-categories]')?.value);
				preview.recipe.ingredients = previewIngredients(card.querySelector('[data-preview-ingredients]')?.value);
				preview.recipe.steps = previewSteps(card.querySelector('[data-preview-steps]')?.value);
            });
            button.disabled = true;
            const failedRecipes = [];
            const failedCovers = [];
            try {
                for (const [position, index] of pendingIndexes.entries()) {
                    const preview = previews[index];
                    setImportBusy(true, `${tr('Saving recipe')} ${position + 1} ${tr('of')} ${pendingIndexes.length}`, preview.recipe.title);
                    try {
                        const remoteCover = safeExternalUrl(preview.recipe.imagePath);
                        const savedRecipe = await request('/recipes', { method: 'POST', json: { recipe: { ...preview.recipe, imagePath: remoteCover ? null : preview.recipe.imagePath } } });
                        savedPreviews.add(index);
                        if (remoteCover) {
                            try {
                                await request(`/recipes/${savedRecipe.recipe.id}/cover`, { method: 'POST', json: { url: remoteCover } });
                            }
                            catch (error) {
                                failedCovers.push(preview.recipe.title);
                            }
                        }
                    }
                    catch (error) {
                        failedRecipes.push(`${preview.recipe.title}: ${error instanceof Error ? error.message : tr('Unexpected error')}`);
                    }
                }
            }
            finally {
                setImportBusy(false);
                button.disabled = false;
            }
            if (failedRecipes.length) {
                showNotice(`${tr('Some recipes could not be saved')}: ${failedRecipes.join('; ')}`, 'error');
                holder.innerHTML = importPreviewsHtml(previews, savedPreviews, commonImportTag, taxonomy);
                bindImportSave(holder);
                return;
            }
            if (failedCovers.length)
                showNotice(`${tr('Some covers could not be saved')}: ${failedCovers.join('; ')}`, 'error');
            else
                showNotice(tr('Imported recipes saved'));
            if (activeExternalJobId !== null) {
                await request(`/import/jobs/${activeExternalJobId}`, { method: 'DELETE' });
                activeExternalJobId = null;
            }
            location.hash = '#/recipes';
        });
    };
    paint();
}
function externalImportInboxHtml(jobs) {
    // Keep pre-upgrade remote jobs visible too, so failed entries can be removed.
    const entries = jobs.filter(job => ['url', 'text'].includes(job.kind));
    return `<div class="section-heading"><div><p class="eyebrow">${esc(tr('Received imports'))}</p></div><button class="ghost" data-refresh-external-imports type="button">${esc(tr('Refresh'))}</button></div>${entries.length ? `<div class="version-list">${entries.map(job => {
        const status = job.status === 'complete' ? tr('Open preview') : job.status === 'failed' ? tr('Import failed') : job.status === 'running' ? tr('Processing import') : tr('Waiting for processing');
        const source = String(job.sourceRef || job.payload?.text || job.payload?.url || job.kind).slice(0, 180);
        const error = job.status === 'failed' && job.error ? `<small>${esc(job.error)}</small>` : '';
        const mainAction = job.status === 'complete' && job.result?.recipe
            ? `<button class="primary" data-open-external-import="${attr(job.id)}" type="button">${esc(tr('Open preview'))}</button>`
            : job.status !== 'running' ? `<button class="primary" data-select-external-import="${attr(job.id)}" type="button">${esc(tr('Select import'))}</button>` : '';
        return `<div class="version-item"><div><strong>${esc(source)}</strong><small>${esc(new Date(job.createdAt * 1000).toLocaleString())} · ${esc(status)}</small>${error}</div><div class="button-row">${mainAction}<button class="ghost danger" data-delete-external-import="${attr(job.id)}" type="button">${esc(tr('Delete import'))}</button></div></div>`;
    }).join('')}</div>` : `<p>${esc(tr('No received imports yet'))}</p>`}`;
}
function previewNames(value) {
	return String(value || '').split(',').map(item => item.trim()).filter(Boolean);
}
function previewIngredients(value) {
	return String(value || '').split(/\r?\n/).map(line => line.trim()).filter(Boolean).map(line => {
		const parts = line.split('|').map(part => part.trim());
		if (parts.length === 1)
			return { name: parts[0], quantity: null, unit: null, notes: null };
		const quantity = Number(parts[0].replace(',', '.'));
		return { name: parts[2] || parts[0], quantity: Number.isFinite(quantity) ? quantity : null, unit: parts[1] || null, notes: parts.slice(3).join(' | ') || null };
	}).filter(item => item.name);
}
function previewSteps(value) {
	return String(value || '').split(/\r?\n/).map(text => text.trim()).filter(Boolean).map(text => ({ text }));
}
function importTaxonomyOptions(items = []) {
	return (items || []).map(item => String(item?.name || item || '').trim()).filter(Boolean).map(name => `<option value="${attr(name)}"></option>`).join('');
}
function importPreviewsHtml(previews, savedPreviews, commonImportTag = '', taxonomy = {}) {
    if (!previews.length)
        return `<div class="empty-preview"><div>${fa('file-lines')}</div><h2>${esc(tr('Preview appears here'))}</h2><p>${esc(tr('The source is never saved as a recipe until you review and confirm the extracted fields.'))}</p></div>`;
    const pending = previews.length - savedPreviews.size;
    const lists = `<datalist id="smartcook-import-tags">${importTaxonomyOptions(taxonomy.tags)}</datalist><datalist id="smartcook-import-categories">${importTaxonomyOptions(taxonomy.categories)}</datalist><datalist id="smartcook-import-cuisine">${importTaxonomyOptions(taxonomy.cuisine)}</datalist><datalist id="smartcook-import-meal-type">${importTaxonomyOptions(taxonomy.mealType)}</datalist><datalist id="smartcook-import-cooking-method">${importTaxonomyOptions(taxonomy.cookingMethod)}</datalist>`;
    return `${lists}<div class="section-heading"><div><p class="eyebrow">${previews.length} ${esc(tr('recipes extracted. Review them before saving.'))}</p><h2>${esc(tr('Import previews'))}</h2></div><button class="primary" data-save-all-import type="button">${esc(pending ? tr('Save all recipes') : tr('View recipes'))}</button></div>${previews.length > 1 ? `<nav class="import-preview-index" aria-label="${attr(tr('Choose a recipe to review'))}"><strong>${esc(tr('Choose a recipe to review'))}</strong><div>${previews.map((item, index) => `<button class="secondary" data-open-import-preview="${index}" type="button">${index + 1}. ${esc(item.recipe.title || tr('Import preview'))}</button>`).join('')}</div></nav><label class="import-common-tag">${esc(tr('Apply this tag to every imported recipe'))}<input data-import-common-tag value="${attr(commonImportTag)}" list="smartcook-import-tags" autocomplete="new-password"></label>` : ''}${previews.map((item, index) => importPreviewHtml(item, savedPreviews.has(index), index)).join('')}`;
}
function importPreviewHtml(preview, saved = false, index = 0) {
    const recipe = preview.recipe;
	const names = (values) => (values || []).map(value => String(value?.name || value || '').trim()).filter(Boolean);
	const categories = names(recipe.categories);
	const tags = names(recipe.tags);
	const coverImage = safeExternalUrl(recipe.imagePath);
	const classification = [
		[tr('Categories'), categories.join(' · ')],
		[tr('Cuisine'), recipe.cuisine],
		[tr('Cooking method'), recipe.cookingMethod],
		[tr('Meal type'), recipe.mealType],
		[tr('Calories'), asNumber(recipe.calories) > 0 ? `${asNumber(recipe.calories)} kcal` : ''],
		[tr('Tags'), tags.map(tag => `#${tag}`).join(' · ')],
	].filter(([, value]) => String(value || '').trim() !== '');
    const ingredientText = (recipe.ingredients || []).map(item => [item.quantity ?? '', item.unit || '', item.name || '', item.notes || ''].join(' | ')).join('\n');
	const stepText = (recipe.steps || []).map(step => step.text || '').join('\n');
    return `<details class="import-preview-card" data-import-preview-card ${index === 0 ? 'open' : ''}><summary><div><p class="eyebrow">${index + 1}. ${esc(preview.strategy)}</p><h3>${esc(recipe.title || tr('Import preview'))}</h3></div>${saved ? `<span class="status-pill enabled">${esc(tr('Saved'))}</span>` : `<span class="import-preview-summary">${recipe.ingredients.length} ${esc(tr('Ingredients'))} · ${recipe.steps.length} ${esc(tr('Steps'))}</span>`}</summary><div class="import-preview-body">
		${preview.warnings.length ? `<div class="warning-list">${preview.warnings.map(warning => `<p>${esc(warning)}</p>`).join('')}</div>` : ''}
		${coverImage ? `<img class="import-cover-preview" src="${attr(coverImage)}" alt="">` : ''}
		<label>${esc(tr('Title'))}<input data-preview-title value="${attr(recipe.title)}"></label><label>${esc(tr('Description'))}<textarea data-preview-description rows="3">${esc(recipe.description)}</textarea></label>
		<div class="form-grid import-preview-fields"><label>${esc(tr('Tags'))}<input data-preview-tags value="${attr(tags.join(', '))}" list="smartcook-import-tags" autocomplete="new-password"></label><label>${esc(tr('Categories'))}<input data-preview-categories value="${attr(categories.join(', '))}" list="smartcook-import-categories" autocomplete="new-password"></label><label>${esc(tr('Cuisine'))}<input data-preview-cuisine value="${attr(recipe.cuisine)}" list="smartcook-import-cuisine"></label><label>${esc(tr('Meal type'))}<input data-preview-meal-type value="${attr(recipe.mealType)}" list="smartcook-import-meal-type"></label><label class="span-2">${esc(tr('Cooking method'))}<input data-preview-cooking-method value="${attr(recipe.cookingMethod)}" list="smartcook-import-cooking-method"></label></div>
		<div class="preview-metrics"><span>${recipe.servings} ${esc(tr('servings'))}</span><span>${recipe.prepTime} min ${esc(tr('prep'))}</span><span>${recipe.cookTime} min ${esc(tr('cook'))}</span><span>${recipe.totalTime} min ${esc(tr('total'))}</span></div>
		${classification.length ? `<section class="import-classification"><h3>${esc(tr('Imported classification'))}</h3><dl>${classification.map(([label, value]) => `<div><dt>${esc(label)}</dt><dd>${esc(value)}</dd></div>`).join('')}</dl></section>` : ''}
		${recipe.coverSuggestion ? `<section class="import-cover-suggestion"><span aria-hidden="true">${fa('wand-magic-sparkles')}</span><div><strong>${esc(tr('Suggested cover photo'))}</strong><p>${esc(recipe.coverSuggestion)}</p><small>${esc(tr('AI suggestion'))}</small></div></section>` : ''}
		<div class="preview-columns"><label><h3>${esc(tr('Ingredients'))} <small>${recipe.ingredients.length}</small></h3><textarea data-preview-ingredients rows="12" aria-label="${attr(tr('Ingredients (one per line)'))}">${esc(ingredientText)}</textarea><small>${esc(tr('Quantity | unit | ingredient | notes'))}</small></label><label><h3>${esc(tr('Procedure'))} <small>${recipe.steps.length}</small></h3><textarea data-preview-steps rows="12" aria-label="${attr(tr('Steps (one per line)'))}">${esc(stepText)}</textarea><small>${esc(tr('Steps (one per line)'))}</small></label></div>
		${preview.duplicates.length ? `<div class="duplicate-box"><h3>${esc(tr('Possible duplicates'))}</h3>${preview.duplicates.map(match => `<a href="#/recipes/${match.recipe.id}">${esc(match.recipe.title)} <span>${Math.round(match.score * 100)}%</span></a>`).join('')}</div>` : ''}</div></details>`;
}
function startOfWeek(date) {
    const copy = new Date(date);
    const day = (copy.getDay() + 6) % 7;
    copy.setDate(copy.getDate() - day);
    copy.setHours(0, 0, 0, 0);
    return copy;
}
function recipeSearchPicker(recipes) {
	return `<div class="recipe-search-picker" data-recipe-search-picker><input data-meal-recipe-search type="search" autocomplete="off" placeholder="${attr(tr('Search recipes...'))}" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="smartcook-recipe-options"><input data-meal-recipe type="hidden"><div id="smartcook-recipe-options" class="recipe-search-options" data-meal-recipe-options role="listbox" hidden>${recipes.map(recipe => `<button data-recipe-id="${attr(recipe.id)}" data-recipe-title="${attr(recipe.title)}" type="button" role="option">${esc(recipe.title)}</button>`).join('')}<p data-no-recipe-results hidden>${esc(tr('No recipes found'))}</p></div></div>`;
}
function bindRecipeSearchPicker(view) {
	const picker = view.querySelector('[data-recipe-search-picker]');
	const search = view.querySelector('[data-meal-recipe-search]');
	const selected = view.querySelector('[data-meal-recipe]');
	const options = view.querySelector('[data-meal-recipe-options]');
	if (!picker || !search || !selected || !options)
		return;
	const buttons = [...options.querySelectorAll('[data-recipe-id]')];
	const noResults = options.querySelector('[data-no-recipe-results]');
	const filter = (term = search.value) => {
		const query = term.trim().toLocaleLowerCase();
		let count = 0;
		buttons.forEach(button => {
			const visible = !query || (button.dataset.recipeTitle || '').toLocaleLowerCase().includes(query);
			button.hidden = !visible;
			count += visible ? 1 : 0;
		});
		if (noResults)
			noResults.hidden = count > 0;
	};
	const close = () => {
		options.hidden = true;
		search.setAttribute('aria-expanded', 'false');
	};
	const open = () => {
		options.hidden = false;
		search.setAttribute('aria-expanded', 'true');
	};
	const choose = (button) => {
		selected.value = button.dataset.recipeId || '';
		search.value = button.dataset.recipeTitle || '';
		close();
	};
	search.addEventListener('focus', () => { open(); filter(''); search.select(); });
	search.addEventListener('input', () => { selected.value = ''; open(); filter(); });
	search.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') {
			close();
			return;
		}
		if (event.key === 'ArrowDown') {
			const first = buttons.find(button => !button.hidden);
			if (first) {
				event.preventDefault();
				first.focus();
			}
		}
	});
	buttons.forEach(button => button.addEventListener('click', () => choose(button)));
	view.addEventListener('click', (event) => { if (!picker.contains(event.target)) close(); });
}
async function renderPlanner(view) {
    let weekStart = startOfWeek(new Date());
    let recipes = [];
    let meals = [];
    const load = async () => {
        const days = Array.from({ length: 7 }, (_, index) => { const day = new Date(weekStart); day.setDate(day.getDate() + index); return day; });
        const [recipeResponse, mealResponse] = await working(() => Promise.all([
            request('/recipes?sort=title&direction=ASC'),
            request(`/planner?from=${dateIso(days[0])}&to=${dateIso(days[6])}`),
        ]));
        recipes = recipeResponse.recipes.filter(recipe => !recipe.excludeFromPlanner);
        meals = mealResponse.meals;
        paint(days);
    };
    const paint = (days) => {
		view.innerHTML = `<section class="toolbar panel planner-toolbar"><button class="secondary planner-week-button" data-week-back type="button" aria-label="${attr(tr('Previous'))}" title="${attr(tr('Previous'))}">${fa('chevron-left')}</button><div><p class="eyebrow">${esc(tr('Week'))}</p><h2>${esc(days[0].toLocaleDateString())} - ${esc(days[6].toLocaleDateString())}</h2></div><button class="secondary planner-week-button" data-week-forward type="button" aria-label="${attr(tr('Next'))}" title="${attr(tr('Next'))}">${fa('chevron-right')}</button><div class="planner-toolbar-actions"><button class="ghost" data-week-today type="button">${esc(tr('Today'))}</button><button class="secondary" data-clear-week type="button"${meals.length ? '' : ' disabled'}>${esc(tr('Empty week'))}</button></div></section>
		<section class="panel form-section planner-ai"><div class="section-heading"><div><p class="eyebrow">${esc(tr('AI meal planner'))}</p></div></div><div class="form-grid"><label class="span-2">${esc(tr('Weekly instruction (optional)'))}<textarea data-planner-instruction rows="2" placeholder="${attr(tr('Example: use more legumes and prepare leftovers for lunch'))}"></textarea></label></div><button class="primary" data-generate-plan type="button">${esc(tr('Generate with AI'))}</button></section>
		<section class="planner-grid">${days.map(day => `<article class="day-column panel ${dateIso(day) === dateIso(new Date()) ? 'today' : ''}"><header><span>${esc(day.toLocaleDateString(undefined, { weekday: 'short' }))}</span><strong>${day.getDate()}</strong></header>${meals.filter(meal => meal.date === dateIso(day)).map(meal => { const recipe = recipes.find(item => item.id === meal.recipeId); return `<div class="meal-card"><small>${esc(mealLabel(meal.slot))}</small><a href="#/recipes/${meal.recipeId}"><span class="meal-card-thumb">${recipeThumb(recipe || { title: meal.recipeTitle })}</span><span class="meal-card-title">${esc(meal.recipeTitle)}</span></a><button data-delete-meal="${meal.id}" type="button" aria-label="${attr(tr('Remove'))}">${fa('xmark')}</button></div>`; }).join('')}<button class="add-meal" data-select-date="${dateIso(day)}" type="button">${fa('plus')}</button></article>`).join('')}</section>
		<section class="panel form-section"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Plan'))}</p><h2>${esc(tr('Add a meal'))}</h2></div></div><div class="form-grid four"><label>${esc(tr('Date'))}<input data-meal-date type="date" value="${dateIso(new Date())}"></label><label>${esc(tr('Recipe'))}${recipeSearchPicker(recipes)}</label><label>${esc(tr('Meal'))}<select data-meal-slot><option value="breakfast">${esc(tr('Breakfast'))}</option><option value="lunch">${esc(tr('Lunch'))}</option><option value="dinner" selected>${esc(tr('Dinner'))}</option><option value="snack">${esc(tr('Snack'))}</option></select></label><label>${esc(tr('Servings'))}<input data-meal-servings type="number" min="1" value="2"></label></div><button class="primary" data-add-meal type="button">${esc(tr('Add to plan'))}</button></section>`;
        view.querySelector('[data-week-back]')?.addEventListener('click', () => { weekStart.setDate(weekStart.getDate() - 7); void load(); });
        view.querySelector('[data-week-forward]')?.addEventListener('click', () => { weekStart.setDate(weekStart.getDate() + 7); void load(); });
        view.querySelector('[data-week-today]')?.addEventListener('click', () => { weekStart = startOfWeek(new Date()); void load(); });
		bindRecipeSearchPicker(view);
		view.querySelector('[data-clear-week]')?.addEventListener('click', async () => {
			if (!window.confirm(tr('Clear all meals from this week?')))
				return;
			await working(() => Promise.all(meals.map(meal => request(`/planner/${meal.id}`, { method: 'DELETE' }))));
			await load();
		});
		view.querySelector('[data-generate-plan]')?.addEventListener('click', async () => {
			const instruction = view.querySelector('[data-planner-instruction]')?.value || '';
			await working(() => request('/planner', { method: 'POST', json: { plan: { from: dateIso(days[0]), to: dateIso(days[6]), instruction } } }));
			showNotice(tr('Meal plan generated'));
			await load();
		});
        view.querySelectorAll('[data-select-date]').forEach(button => button.addEventListener('click', () => { const input = view.querySelector('[data-meal-date]'); if (input)
            input.value = button.dataset.selectDate || ''; }));
        view.querySelectorAll('[data-delete-meal]').forEach(button => button.addEventListener('click', async () => {
            if (!window.confirm(tr('Delete this meal?')))
                return;
            await working(() => request(`/planner/${asNumber(button.dataset.deleteMeal)}`, { method: 'DELETE' }));
            await load();
        }));
        view.querySelector('[data-add-meal]')?.addEventListener('click', async () => {
            const date = view.querySelector('[data-meal-date]')?.value || '';
            const recipeId = asNumber(view.querySelector('[data-meal-recipe]')?.value);
            const slot = view.querySelector('[data-meal-slot]')?.value || 'dinner';
            const servings = asNumber(view.querySelector('[data-meal-servings]')?.value, 1);
            if (!date || !recipeId) {
                showNotice(tr('Choose a date and recipe'), 'error');
                return;
            }
            await working(() => request('/planner', { method: 'POST', json: { meal: { date, recipeId, slot, servings } } }));
            showNotice(tr('Meal added'));
            await load();
        });
    };
    await load();
}
async function renderShopping(view) {
    let lists = [];
    let recipes = [];
    let selected = null;
    const load = async () => {
        const [listResponse, recipeResponse] = await working(() => Promise.all([
            request('/shopping'), request('/recipes?sort=title&direction=ASC'),
        ]));
        lists = listResponse.lists;
        recipes = recipeResponse.recipes;
        if (selected)
            selected = (await request(`/shopping/${selected.id}`)).list;
        else if (lists[0])
            selected = (await request(`/shopping/${lists[0].id}`)).list;
        paint();
    };
    const open = async (id) => { selected = (await working(() => request(`/shopping/${id}`))).list; paint(); };
    const listStatus = (list) => {
        if (!list.itemCount)
            return tr('No items yet');
        if (list.checkedCount >= list.itemCount)
            return tr('Completed');
        return `${list.checkedCount} ${tr('of')} ${list.itemCount} ${tr('completed')}`;
    };
    const groupItems = (items) => {
        const groups = new Map();
        items.forEach(item => {
            const key = item.category || tr('Uncategorized');
            if (!groups.has(key))
                groups.set(key, []);
            groups.get(key).push(item);
        });
        return [...groups.entries()];
    };
    const paint = () => {
        const checkedCount = selected ? (selected.items || []).filter(item => item.checked).length : 0;
        const totalCount = selected ? (selected.items || []).length : 0;
        const progress = totalCount ? Math.round(checkedCount / totalCount * 100) : 0;
        view.innerHTML = `<div class="shopping-layout"><aside class="panel list-sidebar"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Saved'))}</p></div></div><div class="list-sidebar-items">${lists.map(list => `<a class="${selected?.id === list.id ? 'active' : ''}" data-open-list="${list.id}" href="#"><span class="list-sidebar-icon">${fa('bookmark')}</span><span class="list-sidebar-text"><strong>${esc(list.name)}</strong><small>${esc(listStatus(list))}</small></span></a>`).join('') || `<div class="empty-state list-sidebar-empty"><div class="list-sidebar-empty-icon">${fa('bookmark')}</div><p><strong>${esc(tr('No lists yet'))}</strong><br>${esc(tr('Generate one from your recipes'))}</p></div>`}</div>${lists.length ? `<button class="list-sidebar-new" data-new-list type="button">${fa('plus')}${esc(tr('New list'))}</button>` : ''}</aside>
		<main class="view-stack">${selected ? `<section class="panel shopping-sheet"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Active list'))}</p><h2>${esc(selected.name)}</h2></div><button class="ghost danger" data-delete-list type="button">${fa('trash-can')}${esc(tr('Delete'))}</button></div>${totalCount ? `<div class="shopping-progress"><span class="shopping-progress-track"><span class="shopping-progress-fill" style="width:${progress}%"></span></span><span class="shopping-progress-label">${checkedCount} ${esc(tr('of'))} ${totalCount}</span></div>` : ''}<div class="add-item"><input data-new-item placeholder="${attr(tr('Add an item...'))}"><button class="secondary" data-add-item type="button" aria-label="${attr(tr('Add'))}">${fa('plus')}</button></div><div class="shopping-items">${groupItems(selected.items || []).map(([category, items]) => `<div class="shopping-group"><p class="shopping-group-heading">${esc(category)}</p>${items.map(item => `<label class="${item.checked ? 'checked' : ''}"><input data-toggle-item="${item.id}" type="checkbox"${item.checked ? ' checked' : ''}><span><strong>${esc(item.quantity)} ${esc(displayUnit(item.unit))}</strong> ${esc(item.name)}${item.notes ? `<small>${esc(item.notes)}</small>` : ''}</span></label>`).join('')}</div>`).join('')}</div></section>` : ''}
		<section class="panel form-section"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Generate'))}</p><h2>${esc(tr('From recipes'))}</h2></div></div><label>${esc(tr('List name'))}<input data-list-name value="${attr(tr('Weekly shopping'))}"></label><p class="recipe-selector-heading">${esc(tr('Servings per recipe'))}</p><div class="recipe-selector">${recipes.map(recipe => `<label><input data-list-recipe="${recipe.id}" type="checkbox"><div class="recipe-thumb">${recipeThumb(recipe)}</div><span>${esc(recipe.title)}</span><span class="servings-stepper"><button type="button" class="stepper-btn" data-step="-1" data-step-target="${recipe.id}" aria-label="${attr(tr('Decrease'))}">${fa('minus')}</button><input class="recipe-selector-servings" data-list-servings="${recipe.id}" type="number" min="1" value="${recipe.servings || 1}" aria-label="${attr(tr('Servings'))}"><button type="button" class="stepper-btn" data-step="1" data-step-target="${recipe.id}" aria-label="${attr(tr('Increase'))}">${fa('plus')}</button></span></label>`).join('')}</div><button class="primary" data-create-list type="button">${esc(tr('Generate shopping list'))}</button><small class="section-help">${esc(tr('Quantities with compatible units are summed automatically.'))}</small></section>
		</main></div>`;
        view.querySelectorAll('[data-open-list]').forEach(button => button.addEventListener('click', (event) => { event.preventDefault(); void open(asNumber(button.dataset.openList)); }));
        view.querySelector('[data-new-list]')?.addEventListener('click', () => { selected = null; paint(); });
        view.querySelectorAll('[data-step]').forEach(button => button.addEventListener('click', () => {
            const input = view.querySelector(`[data-list-servings="${button.dataset.stepTarget}"]`);
            if (!input)
                return;
            input.value = String(Math.max(1, asNumber(input.value, 1) + asNumber(button.dataset.step, 0)));
        }));
        view.querySelector('[data-create-list]')?.addEventListener('click', async () => {
            const name = view.querySelector('[data-list-name]')?.value.trim() || tr('Shopping list');
            const selections = [...view.querySelectorAll('[data-list-recipe]:checked')].map(box => ({ recipeId: asNumber(box.dataset.listRecipe), servings: asNumber(view.querySelector(`[data-list-servings="${box.dataset.listRecipe}"]`)?.value, 1) }));
            selected = (await working(() => request('/shopping', { method: 'POST', json: { name, recipes: selections } }))).list;
            showNotice(tr('Shopping list created'));
            await load();
        });
        view.querySelectorAll('[data-toggle-item]').forEach(box => box.addEventListener('change', async () => {
            if (!selected)
                return;
            await working(() => request(`/shopping/${selected.id}/items/${asNumber(box.dataset.toggleItem)}`, { method: 'PUT', json: { item: { checked: box.checked } } }));
            await open(selected.id);
        }));
        const addItem = async () => {
            if (!selected)
                return;
            const input = view.querySelector('[data-new-item]');
            if (!input?.value.trim())
                return;
            await working(() => request(`/shopping/${selected.id}/items`, { method: 'POST', json: { item: { name: input.value.trim() } } }));
            await open(selected.id);
        };
        view.querySelector('[data-add-item]')?.addEventListener('click', () => { void addItem(); });
        view.querySelector('[data-new-item]')?.addEventListener('keydown', event => { if (event.key === 'Enter') {
            event.preventDefault();
            void addItem();
        } });
        view.querySelector('[data-delete-list]')?.addEventListener('click', async () => {
            if (!selected || !window.confirm(tr('Delete this shopping list?')))
                return;
            await working(() => request(`/shopping/${selected.id}`, { method: 'DELETE' }));
            selected = null;
            await load();
        });
    };
    await load();
}
async function renderAdministration(view) {
    const labels = {
        tags: tr('Tags'), categories: tr('Categories'), tools: tr('Tools'), cuisine: tr('Cuisine'),
        mealType: tr('Meal type'), cookingMethod: tr('Cooking method'), season: tr('Season'),
        ingredients: tr('Ingredients')
    };
    let taxonomy = {};
    let recipes = [];
    let recipeFilter = '';
    let activeKind = 'tags';
    const selectedCategories = [];
    const selectedTags = [];
    const selectedRecipeIds = new Set();
    let selectedTaxonomyValue = null;
    let administrationTab = 'bulk';
    let refinementProposals = [];
    const refinementSelection = new Map();
    let analysisRunning = false;
    let analysisCancelled = false;
    let analysisProgress = { completed: 0, total: 0, title: '' };
    let analysisAbortController = null;
    const administrationTabs = () => `<nav class="tabs taxonomy-admin-tabs" aria-label="${attr(tr('Administration'))}" role="tablist"><button class="${administrationTab === 'bulk' ? 'active' : ''}" data-administration-tab="bulk" type="button" role="tab" aria-selected="${administrationTab === 'bulk'}">${esc(tr('Bulk editing'))}</button><button class="${administrationTab === 'refinement' ? 'active' : ''}" data-administration-tab="refinement" type="button" role="tab" aria-selected="${administrationTab === 'refinement'}">${esc(tr('AI refinement'))}</button></nav>`;
    const bindAdministrationTabs = () => view.querySelectorAll('[data-administration-tab]').forEach(button => button.addEventListener('click', () => {
        if (analysisRunning) return;
        administrationTab = button.dataset.administrationTab || 'bulk';
        render();
    }));
    const filteredRecipes = () => {
        const normalizedFilter = recipeFilter.trim().toLocaleLowerCase();
        const hasSelectedTaxonomy = (values, selected) => !selected.length || selected.some(selectedName => (values || []).some(value => String(value?.name || value || '') === selectedName));
        const hasSelectedValue = recipe => {
            if (!selectedTaxonomyValue) return true;
            const { kind, name } = selectedTaxonomyValue;
            return ['tags', 'categories', 'tools'].includes(kind) ? (recipe[kind] || []).some(value => String(value?.name || value || '') === name) : String(recipe[kind] || '') === name;
        };
        return recipes.filter(recipe => `${recipe.title} ${recipe.cuisine || ''}`.toLocaleLowerCase().includes(normalizedFilter)
            && hasSelectedTaxonomy(recipe.categories, selectedCategories) && hasSelectedTaxonomy(recipe.tags, selectedTags) && hasSelectedValue(recipe));
    };
    const recipeSelector = () => {
        const visibleRecipes = filteredRecipes();
        const selectedLabel = `${selectedRecipeIds.size} ${tr('recipes selected')}`;
        return `<section class="panel taxonomy-recipe-selector"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Select recipes'))}</p><h2>${esc(tr('Recipe'))}</h2></div><span class="taxonomy-selection-summary"><strong>${esc(selectedLabel)}</strong><button class="ghost icon-button taxonomy-clear-filters" data-clear-selected-recipes type="button" aria-label="${attr(tr('Clear selected recipes'))}" title="${attr(tr('Clear selected recipes'))}"${selectedRecipeIds.size ? '' : ' disabled'}>${fa('xmark')}</button></span></div><div class="toolbar taxonomy-admin-toolbar"><label class="search-field"><span>${fa('magnifying-glass')}</span><input data-admin-recipe-search value="${attr(recipeFilter)}" placeholder="${attr(tr('Filter recipes...'))}"></label><div class="taxonomy-filter">${taxonomyPicker('categories', taxonomy.categories || [], selectedCategories)}</div><div class="taxonomy-filter">${taxonomyPicker('tags', taxonomy.tags || [], selectedTags)}</div><button class="danger ghost" data-delete-selected-recipes type="button"${selectedRecipeIds.size ? '' : ' disabled'}>${esc(tr('Delete selected recipes'))}</button></div>${selectedTaxonomyValue ? `<p class="section-help">${esc(tr('Filtered by'))}: <strong>${esc(labels[selectedTaxonomyValue.kind])}: ${esc(selectedTaxonomyValue.name)}</strong> <button class="ghost" data-clear-taxonomy-filter type="button">${esc(tr('Clear filter'))}</button></p>` : ''}<label class="check-inline taxonomy-select-all"><input data-admin-select-all type="checkbox"${visibleRecipes.length > 0 && visibleRecipes.every(recipe => selectedRecipeIds.has(recipe.id)) ? ' checked' : ''}> ${esc(tr('Select filtered recipes'))}</label><div class="taxonomy-recipe-list">${visibleRecipes.map(recipe => `<label><input data-admin-recipe-id="${recipe.id}" type="checkbox"${selectedRecipeIds.has(recipe.id) ? ' checked' : ''}><span><strong>${esc(recipe.title)}</strong>${recipe.cuisine ? `<small>${esc(recipe.cuisine)}</small>` : ''}</span></label>`).join('') || `<p class="section-help">${esc(tr('No recipes found'))}</p>`}</div></section>`;
    };
    const bindRecipeSelector = () => {
        view.querySelector('[data-admin-recipe-search]')?.addEventListener('input', event => { recipeFilter = event.target.value; render(); });
        bindTaxonomyPicker(view, 'categories', selectedCategories, render);
        bindTaxonomyPicker(view, 'tags', selectedTags, render);
        view.querySelector('[data-clear-recipe-filters]')?.addEventListener('click', () => {
            recipeFilter = '';
            selectedCategories.splice(0);
            selectedTags.splice(0);
            selectedTaxonomyValue = null;
            render();
        });
        view.querySelector('[data-clear-taxonomy-filter]')?.addEventListener('click', () => { selectedTaxonomyValue = null; render(); });
        view.querySelectorAll('[data-admin-recipe-id]').forEach(input => input.addEventListener('change', () => { const id = asNumber(input.dataset.adminRecipeId); input.checked ? selectedRecipeIds.add(id) : selectedRecipeIds.delete(id); render(); }));
        view.querySelector('[data-admin-select-all]')?.addEventListener('change', event => { filteredRecipes().forEach(recipe => event.target.checked ? selectedRecipeIds.add(recipe.id) : selectedRecipeIds.delete(recipe.id)); render(); });
    };
    const renderRefinement = () => {
        const selectedIds = [...selectedRecipeIds];
        const proposalRows = refinementProposals.map(item => {
            const selection = refinementSelection.get(asNumber(item.recipeId)) || { fields: new Set(Object.keys(item.proposal || {})), addCover: !!item.addCover };
            const fields = Object.entries(item.proposal || {});
            const fieldRows = fields.map(([field, value]) => `<label class="check-inline"><input data-refinement-field="${asNumber(item.recipeId)}:${attr(field)}" type="checkbox"${selection.fields.has(field) ? ' checked' : ''}><span><strong>${esc(tr(refinementFieldLabel(field)))}</strong>${refinementComparison(item.current?.[field], value)}</span></label>`).join('');
            return `<article class="panel refinement-proposal"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Recipe'))}</p><h2>${esc(item.title)}</h2></div><label class="check-inline"><input data-refinement-recipe="${asNumber(item.recipeId)}" type="checkbox"${selection.fields.size || selection.addCover ? ' checked' : ''}> ${esc(tr('Apply this proposal'))}</label></div>${fields.length ? `<div class="refinement-fields">${fieldRows}</div>` : `<p class="section-help">${esc(tr('No changes proposed'))}</p>`}${item.addCover ? `<label class="check-inline"><input data-refinement-cover="${asNumber(item.recipeId)}" type="checkbox"${selection.addCover ? ' checked' : ''}><span><strong>${esc(tr('Find a cover image'))}</strong><small>${esc(item.coverSuggestion || tr('AI suggestion'))}</small></span></label>` : ''}</article>`;
        }).join('');
        if (true) {
            const progressText = `${analysisProgress.completed} / ${analysisProgress.total}`;
            view.innerHTML = `${administrationTabs()}${recipeSelector()}<section class="panel refinement-intro"><div class="section-heading"><div><p class="eyebrow">${esc(tr('AI refinement'))}</p><h2>${esc(tr('Improve recipe organization'))}</h2><p class="section-help">${esc(tr('AI reviews identity and organization. It can propose a procedure only when one is missing. Ingredients and existing procedure are never changed. Review every proposal before applying it.'))}</p></div></div><div class="toolbar taxonomy-admin-toolbar"><strong>${selectedIds.length} ${esc(tr('recipes selected'))}</strong><button class="primary" data-refinement-analyze type="button"${selectedIds.length && !analysisRunning ? '' : ' disabled'}>${esc(tr('Analyze selected recipes'))}</button></div></section>${refinementProposals.length ? `<section class="section-heading refinement-results-heading"><div><p class="eyebrow">${esc(tr('Preview'))}</p><h2>${esc(tr('Suggested changes'))}</h2></div><button class="primary" data-refinement-apply type="button"${analysisRunning ? ' disabled' : ''}>${esc(tr('Apply approved changes'))}</button></section>${proposalRows}` : ''}<div class="blocking-modal" data-refinement-loading${analysisRunning ? '' : ' hidden'} role="dialog" aria-modal="true" aria-labelledby="smartcook-refinement-loading-title"><div class="blocking-modal-card"><div class="loading-spinner" aria-hidden="true"></div><h2 id="smartcook-refinement-loading-title">${esc(tr('Analyzing recipes'))}</h2><p>${esc(analysisProgress.title || tr('Loading...'))}</p><strong>${esc(progressText)}</strong><button class="danger secondary" data-refinement-cancel type="button">${esc(tr('Cancel analysis'))}</button></div></div>`;
            view.insertAdjacentHTML('beforeend', `<div class="blocking-modal" data-refinement-applying hidden role="dialog" aria-modal="true" aria-labelledby="smartcook-refinement-applying-title"><div class="blocking-modal-card"><div class="loading-spinner" aria-hidden="true"></div><h2 id="smartcook-refinement-applying-title">${esc(tr('Applying approved changes'))}</h2><p>${esc(tr('Please wait while approved changes are saved.'))}</p></div></div>`);
            bindAdministrationTabs();
            bindRecipeSelector();
            view.querySelector('[data-refinement-cancel]')?.addEventListener('click', () => {
                analysisCancelled = true;
                analysisAbortController?.abort();
            });
            view.querySelector('[data-refinement-analyze]')?.addEventListener('click', async () => {
                const ids = [...selectedRecipeIds];
                refinementProposals = [];
                refinementSelection.clear();
                analysisRunning = true;
                analysisCancelled = false;
                analysisProgress = { completed: 0, total: ids.length, title: '' };
                let analysisFailed = false;
                renderRefinement();
                for (const id of ids) {
                    if (analysisCancelled) break;
                    const recipe = recipes.find(item => asNumber(item.id) === id);
                    analysisProgress.title = recipe?.title || tr('Loading...');
                    renderRefinement();
                    analysisAbortController = new AbortController();
                    try {
                        const response = await request('/recipes/refinement/analyze', { method: 'POST', json: { recipeIds: [id] }, signal: analysisAbortController.signal });
                        if (!analysisCancelled && response.proposals?.[0]) {
                            const item = response.proposals[0];
                            refinementProposals.push(item);
                            refinementSelection.set(asNumber(item.recipeId), { fields: new Set(Object.keys(item.proposal || {})), addCover: !!item.addCover });
                        }
                    } catch (error) {
                        if (!analysisCancelled && error?.name !== 'AbortError') {
                            analysisFailed = true;
                            analysisCancelled = true;
                            showNotice(error instanceof Error ? error.message : tr('Request failed'), 'error');
                        }
                    } finally {
                        analysisAbortController = null;
                    }
                    analysisProgress.completed++;
                    if (analysisCancelled) break;
                }
                const interrupted = analysisCancelled;
                analysisRunning = false;
                analysisCancelled = false;
                analysisProgress.title = '';
                render();
                if (interrupted && !analysisFailed) showNotice(tr('Analysis interrupted'));
            });
            view.querySelectorAll('[data-refinement-recipe]').forEach(input => input.addEventListener('change', () => { const selection = refinementSelection.get(asNumber(input.dataset.refinementRecipe)) || { fields: new Set(), addCover: false }; selection.fields = input.checked ? new Set(Object.keys((refinementProposals.find(item => asNumber(item.recipeId) === asNumber(input.dataset.refinementRecipe)) || {}).proposal || {})) : new Set(); selection.addCover = input.checked && !!(refinementProposals.find(item => asNumber(item.recipeId) === asNumber(input.dataset.refinementRecipe)) || {}).addCover; refinementSelection.set(asNumber(input.dataset.refinementRecipe), selection); renderRefinement(); }));
            view.querySelectorAll('[data-refinement-field]').forEach(input => input.addEventListener('change', () => { const [id, field] = String(input.dataset.refinementField || '').split(':'); const selection = refinementSelection.get(asNumber(id)) || { fields: new Set(), addCover: false }; input.checked ? selection.fields.add(field) : selection.fields.delete(field); refinementSelection.set(asNumber(id), selection); renderRefinement(); }));
            view.querySelectorAll('[data-refinement-cover]').forEach(input => input.addEventListener('change', () => { const id = asNumber(input.dataset.refinementCover); const selection = refinementSelection.get(id) || { fields: new Set(), addCover: false }; selection.addCover = input.checked; refinementSelection.set(id, selection); renderRefinement(); }));
            view.querySelector('[data-refinement-apply]')?.addEventListener('click', async () => {
                const proposals = refinementProposals.map(item => { const selection = refinementSelection.get(asNumber(item.recipeId)); return { recipeId: item.recipeId, fields: Object.fromEntries(Object.entries(item.proposal || {}).filter(([field]) => selection?.fields.has(field))), addCover: !!selection?.addCover }; }).filter(item => Object.keys(item.fields).length || item.addCover);
                if (!proposals.length || !window.confirm(tr('Apply the approved changes to the selected recipes?'))) return;
                const modal = view.querySelector('[data-refinement-applying]');
                if (modal) modal.hidden = false;
                try {
                    const response = await working(() => request('/recipes/refinement/apply', { method: 'POST', json: { proposals } }));
                    showNotice(`${asNumber(response.changed)} ${tr('recipes updated')}`);
                    refinementProposals = [];
                    await load();
                } catch (_) {
                    if (modal) modal.hidden = true;
                }
            });
            return;
        }
        view.innerHTML = `${administrationTabs()}<section class="panel refinement-intro"><div class="section-heading"><div><p class="eyebrow">${esc(tr('AI refinement'))}</p><h2>${esc(tr('Improve recipe organization'))}</h2><p class="section-help">${esc(tr('AI reviews identity and organization. It can propose a procedure only when one is missing. Ingredients and existing procedure are never changed. Review every proposal before applying it.'))}</p></div></div><div class="toolbar taxonomy-admin-toolbar"><label class="search-field"><span>${fa('magnifying-glass')}</span><input data-refinement-search value="${attr(recipeFilter)}" placeholder="${attr(tr('Filter recipes...'))}"></label><strong>${selectedIds.length} ${esc(tr('recipes selected'))}</strong><button class="secondary" data-refinement-select-all type="button">${esc(tr('Select all recipes'))}</button><button class="primary" data-refinement-analyze type="button"${selectedIds.length ? '' : ' disabled'}>${esc(tr('Analyze selected recipes'))}</button></div><div class="taxonomy-recipe-list">${recipes.filter(recipe => `${recipe.title} ${recipe.cuisine || ''}`.toLocaleLowerCase().includes(recipeFilter.trim().toLocaleLowerCase())).map(recipe => `<label><input data-refinement-id="${recipe.id}" type="checkbox"${selectedRecipeIds.has(recipe.id) ? ' checked' : ''}><span><strong>${esc(recipe.title)}</strong>${recipe.cuisine ? `<small>${esc(recipe.cuisine)}</small>` : ''}</span></label>`).join('') || `<p class="section-help">${esc(tr('No recipes found'))}</p>`}</div></section>${refinementProposals.length ? `<section class="section-heading refinement-results-heading"><div><p class="eyebrow">${esc(tr('Preview'))}</p><h2>${esc(tr('Suggested changes'))}</h2></div><button class="primary" data-refinement-apply type="button">${esc(tr('Apply approved changes'))}</button></section>${proposalRows}` : ''}`;
        bindAdministrationTabs();
        view.querySelector('[data-refinement-search]')?.addEventListener('input', event => { recipeFilter = event.target.value; renderRefinement(); });
        view.querySelectorAll('[data-refinement-id]').forEach(input => input.addEventListener('change', () => { const id = asNumber(input.dataset.refinementId); input.checked ? selectedRecipeIds.add(id) : selectedRecipeIds.delete(id); renderRefinement(); }));
        view.querySelector('[data-refinement-select-all]')?.addEventListener('click', () => { recipes.forEach(recipe => selectedRecipeIds.add(recipe.id)); renderRefinement(); });
        view.querySelector('[data-refinement-analyze]')?.addEventListener('click', async () => {
            const response = await working(() => request('/recipes/refinement/analyze', { method: 'POST', json: { recipeIds: [...selectedRecipeIds] } }));
            refinementProposals = response.proposals || [];
            refinementSelection.clear();
            refinementProposals.forEach(item => refinementSelection.set(asNumber(item.recipeId), { fields: new Set(Object.keys(item.proposal || {})), addCover: !!item.addCover }));
            renderRefinement();
        });
        view.querySelectorAll('[data-refinement-recipe]').forEach(input => input.addEventListener('change', () => { const selection = refinementSelection.get(asNumber(input.dataset.refinementRecipe)) || { fields: new Set(), addCover: false }; selection.fields = input.checked ? new Set(Object.keys((refinementProposals.find(item => asNumber(item.recipeId) === asNumber(input.dataset.refinementRecipe)) || {}).proposal || {})) : new Set(); selection.addCover = input.checked && !!(refinementProposals.find(item => asNumber(item.recipeId) === asNumber(input.dataset.refinementRecipe)) || {}).addCover; refinementSelection.set(asNumber(input.dataset.refinementRecipe), selection); renderRefinement(); }));
        view.querySelectorAll('[data-refinement-field]').forEach(input => input.addEventListener('change', () => { const [id, field] = String(input.dataset.refinementField || '').split(':'); const selection = refinementSelection.get(asNumber(id)) || { fields: new Set(), addCover: false }; input.checked ? selection.fields.add(field) : selection.fields.delete(field); refinementSelection.set(asNumber(id), selection); renderRefinement(); }));
        view.querySelectorAll('[data-refinement-cover]').forEach(input => input.addEventListener('change', () => { const id = asNumber(input.dataset.refinementCover); const selection = refinementSelection.get(id) || { fields: new Set(), addCover: false }; selection.addCover = input.checked; refinementSelection.set(id, selection); renderRefinement(); }));
        view.querySelector('[data-refinement-apply]')?.addEventListener('click', async () => {
            const proposals = refinementProposals.map(item => { const selection = refinementSelection.get(asNumber(item.recipeId)); return { recipeId: item.recipeId, fields: Object.fromEntries(Object.entries(item.proposal || {}).filter(([field]) => selection?.fields.has(field))), addCover: !!selection?.addCover }; }).filter(item => Object.keys(item.fields).length || item.addCover);
            if (!proposals.length || !window.confirm(tr('Apply the approved changes to the selected recipes?'))) return;
            const response = await working(() => request('/recipes/refinement/apply', { method: 'POST', json: { proposals } }));
            showNotice(`${asNumber(response.changed)} ${tr('recipes updated')}`);
            refinementProposals = [];
            await load();
        });
    };
    const render = () => {
        if (administrationTab === 'refinement') { renderRefinement(); return; }
        const normalizedFilter = recipeFilter.trim().toLocaleLowerCase();
        const hasSelectedTaxonomy = (values, selected) => !selected.length || selected.some(selectedName => (values || []).some(value => String(value?.name || value || '') === selectedName));
        const hasSelectedValue = recipe => {
            if (!selectedTaxonomyValue) return true;
            const { kind, name } = selectedTaxonomyValue;
            if (['tags', 'categories', 'tools'].includes(kind))
                return (recipe[kind] || []).some(value => String(value?.name || value || '') === name);
            return String(recipe[kind] || '') === name;
        };
        const visibleRecipes = recipes.filter(recipe => `${recipe.title} ${recipe.cuisine || ''}`.toLocaleLowerCase().includes(normalizedFilter)
            && hasSelectedTaxonomy(recipe.categories, selectedCategories)
            && hasSelectedTaxonomy(recipe.tags, selectedTags)
            && hasSelectedValue(recipe));
        const selectedCount = selectedRecipeIds.size;
        const selectedLabel = `${selectedCount} ${tr('recipes selected')}`;
        const activeLabel = labels[activeKind];
        const activeItems = [...(taxonomy[activeKind] || [])].sort((left, right) => String(left.name || '').localeCompare(String(right.name || ''), undefined, { sensitivity: 'base' }));
        const managementPanel = `<section class="panel taxonomy-admin-card"><div class="section-heading"><div><p class="eyebrow">${esc(activeLabel)}</p><h2>${esc(tr('Manage list'))}</h2></div></div><form data-taxonomy-add="${attr(activeKind)}" class="taxonomy-admin-add"><input name="name" required maxlength="255" placeholder="${attr(tr('New value...'))}"><button class="primary" type="submit">${esc(tr('Add'))}</button></form><div class="taxonomy-admin-items">${activeItems.map(item => `<article><div><strong>${esc(item.name)}</strong><small>${activeKind === 'ingredients' ? esc(item.category || tr('Uncategorized')) : `${asNumber(item.usageCount)} ${esc(tr('recipes'))}`}</small></div><div class="taxonomy-admin-actions">${activeKind === 'ingredients' ? `<button class="secondary" data-ingredient-edit="${item.id}" type="button">${esc(tr('Edit'))}</button>` : ''}<button class="secondary" data-taxonomy-select-recipes="${attr(activeKind)}:${item.id}" type="button">${esc(tr('Filter recipes with this value'))}</button><button class="secondary" data-taxonomy-assign="${attr(activeKind)}:${item.id}" type="button"${selectedCount ? '' : ' disabled'}>${esc(tr('Assign to selected'))}</button><button class="ghost" data-taxonomy-remove="${attr(activeKind)}:${item.id}" type="button"${selectedCount ? '' : ' disabled'}>${esc(tr('Remove from selected'))}</button><button class="danger ghost" data-taxonomy-delete="${attr(activeKind)}:${item.id}" type="button" aria-label="${attr(tr('Delete'))}">${fa('xmark')}</button></div></article>`).join('') || `<p class="section-help">${esc(tr('No values yet'))}</p>`}</div></section>`;
        view.innerHTML = `${recipeSelector()}${administrationTabs()}<section class="panel taxonomy-recipe-selector legacy-taxonomy-recipe-selector"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Bulk editing'))}</p><h2>${esc(tr('Select recipes'))}</h2></div><strong data-selected-recipe-count>${esc(selectedLabel)}</strong></div><div class="toolbar taxonomy-admin-toolbar"><label class="search-field"><span>${fa('magnifying-glass')}</span><input data-admin-recipe-search value="${attr(recipeFilter)}" placeholder="${attr(tr('Filter recipes...'))}"></label><div class="taxonomy-filter">${taxonomyPicker('categories', taxonomy.categories || [], selectedCategories)}</div><div class="taxonomy-filter">${taxonomyPicker('tags', taxonomy.tags || [], selectedTags)}</div></div>${selectedTaxonomyValue ? `<p class="section-help">${esc(tr('Filtered by'))}: <strong>${esc(labels[selectedTaxonomyValue.kind])}: ${esc(selectedTaxonomyValue.name)}</strong> <button class="ghost" data-clear-taxonomy-filter type="button">${esc(tr('Clear filter'))}</button></p>` : ''}<label class="check-inline taxonomy-select-all"><input data-admin-select-all type="checkbox"${visibleRecipes.length > 0 && visibleRecipes.every(recipe => selectedRecipeIds.has(recipe.id)) ? ' checked' : ''}> ${esc(tr('Select filtered recipes'))}</label><div class="taxonomy-recipe-list">${visibleRecipes.map(recipe => `<label><input data-admin-recipe-id="${recipe.id}" type="checkbox"${selectedRecipeIds.has(recipe.id) ? ' checked' : ''}><span><strong>${esc(recipe.title)}</strong>${recipe.cuisine ? `<small>${esc(recipe.cuisine)}</small>` : ''}</span></label>`).join('') || `<p class="section-help">${esc(tr('No recipes found'))}</p>`}</div></section><nav class="tabs taxonomy-admin-tabs" aria-label="${attr(tr('Manage list'))}" role="tablist">${Object.entries(labels).map(([kind, label]) => `<button class="${kind === activeKind ? 'active' : ''}" data-admin-tab="${attr(kind)}" type="button" role="tab" aria-selected="${kind === activeKind}">${esc(label)}</button>`).join('')}</nav>${managementPanel}`;
        bindAdministrationTabs();
        view.querySelector('[data-admin-recipe-search]')?.addEventListener('input', event => {
            recipeFilter = event.target.value;
            render();
            const input = view.querySelector('[data-admin-recipe-search]');
            input?.focus();
            input?.setSelectionRange(recipeFilter.length, recipeFilter.length);
        });
		bindTaxonomyPicker(view, 'categories', selectedCategories, render);
		bindTaxonomyPicker(view, 'tags', selectedTags, render);
		view.querySelectorAll('[data-admin-tab]').forEach(button => button.addEventListener('click', () => {
			activeKind = button.dataset.adminTab || activeKind;
			render();
		}));
        view.querySelector('[data-clear-taxonomy-filter]')?.addEventListener('click', () => {
            selectedTaxonomyValue = null;
            render();
        });
        view.querySelectorAll('[data-admin-recipe-id]').forEach(input => input.addEventListener('change', () => {
            const id = asNumber(input.dataset.adminRecipeId);
            input.checked ? selectedRecipeIds.add(id) : selectedRecipeIds.delete(id);
            render();
        }));
        view.querySelector('[data-admin-select-all]')?.addEventListener('change', event => {
            visibleRecipes.forEach(recipe => event.target.checked ? selectedRecipeIds.add(recipe.id) : selectedRecipeIds.delete(recipe.id));
            render();
        });
        view.querySelector('[data-clear-selected-recipes]')?.addEventListener('click', () => {
            selectedRecipeIds.clear();
            render();
        });
        view.querySelector('[data-delete-selected-recipes]')?.addEventListener('click', async () => {
            const recipeIds = [...selectedRecipeIds];
            if (!recipeIds.length || !window.confirm(tr('Delete the selected recipes? This cannot be undone.'))) return;
            const response = await working(() => request('/recipes/delete', { method: 'POST', json: { recipeIds } }));
            showNotice(`${asNumber(response.changed)} ${tr('recipes deleted')}`);
            selectedRecipeIds.clear();
            await load();
        });
        view.querySelectorAll('[data-taxonomy-add]').forEach(form => form.addEventListener('submit', async event => {
            event.preventDefault();
            const input = form.querySelector('input[name="name"]');
            await working(() => request(`/taxonomy/${encodeURIComponent(form.dataset.taxonomyAdd || '')}`, { method: 'POST', json: { name: input?.value || '' } }));
            showNotice(tr('Value added'));
            await load();
        }));
        const selectedIds = () => [...selectedRecipeIds];
        const bindSelectionAction = (selector, action, message) => view.querySelectorAll(selector).forEach(button => button.addEventListener('click', async () => {
            const [kind, id] = String(button.getAttribute(selector.slice(1, -1)) || '').split(':');
            if (!selectedIds().length || !window.confirm(message)) return;
            const response = await working(() => request(`/taxonomy/${encodeURIComponent(kind)}/${encodeURIComponent(id)}/${action}`, { method: 'POST', json: { recipeIds: selectedIds() } }));
            showNotice(`${asNumber(response.changed)} ${tr('recipes updated')}`);
            await load();
        }));
        view.querySelectorAll('[data-ingredient-edit]' ).forEach(button => button.addEventListener('click', () => {
            const id = button.dataset.ingredientEdit;
            const item = (taxonomy.ingredients || []).find(cand => String(cand.id) === id);
            if (!item) return;

            const allCategories = Array.from(new Set((taxonomy.ingredients || []).map(cand => cand.category).filter(Boolean))).sort();
            const datalistId = `ingredient-categories-${item.id}`;

            const modal = document.createElement('div');
            modal.className = 'blocking-modal';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            modal.innerHTML = `<div class="blocking-modal-card" style="min-width: 400px; padding: 24px;">
                <div class="section-heading"><h2>${esc(tr('Edit ingredient'))}: ${esc(item.name)}</h2></div>
                <form data-ingredient-edit-form="${item.id}" style="display: flex; flex-direction: column; gap: 16px;">
                    <label>${esc(tr('Category'))}
                        <input name="category" list="${attr(datalistId)}" value="${attr(item.category || '')}" placeholder="${attr(tr('Category...'))}">
                        <datalist id="${attr(datalistId)}">${allCategories.map(cat => `<option value="${attr(cat)}"></option>`).join('')}</datalist>
                    </label>
                    <label>${esc(tr('Substitutes (comma separated)'))}
                        <input name="substitutes" value="${attr((item.substitutes || []).join(' , '))}" placeholder="${attr(tr('e.g. Milk, Water'))}">
                    </label>
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <button class="ghost" type="button" data-close>${esc(tr('Cancel'))}</button>
                        <button class="primary" type="submit">${esc(tr('Save'))}</button>
                    </div>
                </form>
            </div>`;
            const close = () => modal.remove();
            modal.querySelector('[data-close]' ).addEventListener('click', close);
            modal.querySelector('form' ).addEventListener('submit', async event => {
                event.preventDefault();
                const form = event.target;
                const category = form.category.value.trim();
                const substitutes = form.substitutes.value.split(',').map(s => s.trim()).filter(Boolean);
                await working(() => request(`/taxonomy/ingredients/${id}`, { method: 'PUT', json: { category, substitutes } }));
                showNotice(tr('Ingredient updated'));
                close();
                await load();
            });
            document.body.append(modal);
            modal.querySelector('input' )?.focus();
        }));
        bindSelectionAction('[data-taxonomy-assign]', 'assign', tr('Assign this value to the selected recipes?'));
        bindSelectionAction('[data-taxonomy-remove]', 'remove', tr('Remove this value from the selected recipes?'));
        view.querySelectorAll('[data-taxonomy-select-recipes]').forEach(button => button.addEventListener('click', () => {
            const [kind, id] = String(button.getAttribute('data-taxonomy-select-recipes') || '').split(':');
            const item = (taxonomy[kind] || []).find(candidate => String(candidate.id) === id);
            if (item?.name) {
                selectedTaxonomyValue = { kind, name: item.name };
                render();
            }
        }));
        view.querySelectorAll('[data-taxonomy-delete]').forEach(button => button.addEventListener('click', async () => {
            const [kind, id] = String(button.getAttribute('data-taxonomy-delete') || '').split(':');
            if (!window.confirm(tr('Delete this value and remove it from all your recipes?'))) return;
            await working(() => request(`/taxonomy/${encodeURIComponent(kind)}/${encodeURIComponent(id)}`, { method: 'DELETE' }));
            showNotice(tr('Value deleted'));
            await load();
        }));
    };
    const load = async () => {
        const response = await working(() => request('/taxonomy/manage'));
        taxonomy = response.taxonomy || {};
        recipes = response.recipes || [];
        const validIds = new Set(recipes.map(recipe => asNumber(recipe.id)));
        [...selectedRecipeIds].forEach(id => { if (!validIds.has(id)) selectedRecipeIds.delete(id); });
        render();
    };
    await load();
}
function openOcrHelp() {
    const modal = document.createElement('div');
    modal.className = 'ocr-help-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'smartcook-ocr-help-title');
    modal.innerHTML = `<div class="ocr-help-card"><div class="section-heading"><h2 id="smartcook-ocr-help-title">${esc(tr('Set up local OCR'))}</h2><button class="icon-button" data-close-ocr-help type="button" aria-label="${attr(tr('Close'))}" title="${attr(tr('Close'))}">${fa('xmark')}</button></div><p>${esc(tr('SmartCook uses Tesseract for images and pdftotext for PDFs. Install these tools in the operating system or container that runs Nextcloud; they are not included in the SmartCook app.'))}</p><section><h3>Unraid</h3><p>${esc(tr('Run this from the Unraid terminal when the Nextcloud container is named Nextcloud and is based on Debian.'))}</p><pre><code>docker exec -u root Nextcloud sh -lc '
apt-get update &amp;&amp;
apt-get install -y tesseract-ocr tesseract-ocr-ita tesseract-ocr-eng poppler-utils
'</code></pre><p>${esc(tr('Verify the commands and language packs as the Nextcloud web user.'))}</p><pre><code>docker exec -u www-data Nextcloud sh -lc '
tesseract --list-langs &amp;&amp;
pdftotext -v
'</code></pre></section><section><h3>${esc(tr('Other Linux systems'))}</h3><p>${esc(tr('Install the matching packages in the operating system or container that runs Nextcloud.'))}</p><pre><code>Debian / Ubuntu
apt-get install -y tesseract-ocr tesseract-ocr-ita tesseract-ocr-eng poppler-utils

Alpine Linux
apk add --no-cache tesseract-ocr tesseract-ocr-data-ita tesseract-ocr-data-eng poppler-utils

Fedora / RHEL / Rocky Linux
dnf install -y tesseract tesseract-langpack-ita tesseract-langpack-eng poppler-utils</code></pre></section><section><h3>${esc(tr('After installing'))}</h3><p>${esc(tr('Set the extractor to Local Tesseract / pdftotext, use ita+eng when both languages are installed, then save the settings. Packages installed directly in a container can be lost when it is recreated; use a custom image for a permanent setup.'))}</p></section><button class="secondary" data-close-ocr-help type="button">${esc(tr('Close'))}</button></div>`;
    const close = () => {
        document.removeEventListener('keydown', onKeyDown);
        modal.remove();
    };
    const onKeyDown = event => {
        if (event.key === 'Escape')
            close();
    };
    modal.querySelectorAll('[data-close-ocr-help]').forEach(button => button.addEventListener('click', close));
    modal.addEventListener('click', event => {
        if (event.target === modal)
            close();
    });
    document.addEventListener('keydown', onKeyDown);
    document.body.append(modal);
    modal.querySelector('[data-close-ocr-help]')?.focus();
}
async function renderSettings(view) {
    const settings = (await working(() => request('/settings'))).settings;
	view.innerHTML = `<div class="settings-layout"><main class="view-stack"><div class="editor-actions settings-actions"><div><button class="primary" data-save-settings type="button">${esc(tr('Save settings'))}</button></div></div>
		<section class="panel form-section"><div class="section-heading"><div><p class="eyebrow">${esc(tr('General'))}</p><h2>${esc(tr('Library preferences'))}</h2></div></div><div class="form-grid"><label>${esc(tr('Default language'))}<input data-setting="language" value="${attr(settings.language)}" placeholder="auto / it / en"></label><label>${esc(tr('Measurement system'))}<select data-setting="measurementSystem"><option value="metric"${settings.measurementSystem === 'metric' ? ' selected' : ''}>${esc(tr('Metric'))}</option><option value="imperial"${settings.measurementSystem === 'imperial' ? ' selected' : ''}>${esc(tr('Imperial'))}</option></select></label><label class="span-2">${esc(tr('Attachments folder in Nextcloud Files'))}<input data-setting="attachmentsFolder" value="${attr(settings.attachmentsFolder)}"></label><label>${esc(tr('Maximum URL import size'))}<input data-setting="maxImportBytes" type="number" min="100000" max="20000000" value="${settings.maxImportBytes}"><small>${esc(tr('bytes'))}</small></label></div></section>
		<section class="panel form-section"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Optional intelligence'))}</p><h2>${esc(tr('AI provider'))}</h2></div><span class="status-pill ${settings.aiProvider !== 'disabled' ? 'enabled' : ''}">${esc(settings.aiProvider === 'disabled' ? tr('Disabled') : tr('Enabled'))}</span></div><div class="form-grid">
			<label>${esc(tr('Provider'))}<select data-setting="aiProvider"><option value="disabled">${esc(tr('Disabled'))}</option><option value="nextcloud">Nextcloud Assistant</option><option value="openai">OpenAI</option><option value="openrouter">OpenRouter</option><option value="ollama">Ollama</option><option value="localai">LocalAI</option><option value="mistral">Mistral</option><option value="anthropic">Anthropic</option><option value="gemini">Gemini</option><option value="custom">${esc(tr('Custom OpenAI-compatible'))}</option></select></label>
			<label>${esc(tr('Model'))}<input data-setting="aiModel" value="${attr(settings.aiModel)}"></label><label class="span-2">${esc(tr('Endpoint'))}<input data-setting="aiEndpoint" type="url" value="${attr(settings.aiEndpoint)}" placeholder="https://..."><small>${esc(tr('Leave empty for the provider default. Ollama and LocalAI use local defaults.'))}</small></label>
			<label>${esc(tr('API key'))}<input data-setting="aiApiKey" type="password" autocomplete="new-password" placeholder="${attr(settings.hasAiApiKey ? tr('Key already stored; leave blank to keep it') : tr('API key'))}"></label><label class="check-inline secret-clear"><input data-setting-clear="aiApiKey" type="checkbox"> ${esc(tr('Remove stored key'))}</label><label>${esc(tr('Temperature'))}<input data-setting="aiTemperature" type="number" min="0" max="2" step="0.1" value="${settings.aiTemperature}"></label><label>${esc(tr('Timeout'))}<input data-setting="aiTimeout" type="number" min="10" max="300" value="${settings.aiTimeout}"><small>${esc(tr('seconds'))}</small></label>
		</div><div class="info-box"><b>Nextcloud Assistant</b><p>${esc(tr('Uses the language-model provider already configured by the instance administrator and requires no duplicate API key.'))}</p></div></section>
		<section class="panel form-section"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Cover image'))}</p></div></div><div class="form-grid"><label>${esc(tr('Image search provider'))}<select data-setting="coverImageProvider"><option value="google">Google</option><option value="pexels">Pexels</option><option value="unsplash">Unsplash</option></select></label><label class="span-2">${esc(tr('Programmable Search engine ID'))}<input data-setting="googleImageSearchEngineId" value="${attr(settings.googleImageSearchEngineId)}"></label><label>${esc(tr('Google API key'))}<input data-setting="googleImageSearchApiKey" type="password" autocomplete="new-password" placeholder="${attr(settings.hasGoogleImageSearchApiKey ? tr('Key already stored; leave blank to keep it') : tr('Google API key'))}"></label><label class="check-inline secret-clear"><input data-setting-clear="googleImageSearchApiKey" type="checkbox"> ${esc(tr('Remove stored Google key'))}</label><label>${esc(tr('Pexels API key'))}<input data-setting="pexelsApiKey" type="password" autocomplete="new-password" placeholder="${attr(settings.hasPexelsApiKey ? tr('Key already stored; leave blank to keep it') : tr('Pexels API key'))}"></label><label class="check-inline secret-clear"><input data-setting-clear="pexelsApiKey" type="checkbox"> ${esc(tr('Remove stored Pexels key'))}</label><label>${esc(tr('Unsplash access key'))}<input data-setting="unsplashAccessKey" type="password" autocomplete="new-password" placeholder="${attr(settings.hasUnsplashAccessKey ? tr('Key already stored; leave blank to keep it') : tr('Unsplash access key'))}"></label><label class="check-inline secret-clear"><input data-setting-clear="unsplashAccessKey" type="checkbox"> ${esc(tr('Remove stored Unsplash key'))}</label></div><p class="section-help">${esc(tr('Uses the selected provider with the recipe title and saves the first suitable result as the cover.'))}</p></section>
		<section class="panel form-section"><div class="section-heading"><div><p class="eyebrow">${esc(tr('AI meal planner'))}</p></div></div><div class="form-grid"><label class="span-2">${esc(tr('Planner prompt'))}<textarea data-setting="aiPlannerPrompt" rows="3">${esc(settings.aiPlannerPrompt)}</textarea></label><label class="span-2">${esc(tr('Dietary preferences and constraints'))}<textarea data-setting="plannerPreferences" rows="3" placeholder="${attr(tr('Example: vegetarian, no peanuts, low salt'))}">${esc(settings.plannerPreferences)}</textarea></label><label>${esc(tr('Maximum cooking time per meal'))}<input data-setting="plannerCookingTime" type="number" min="5" max="600" value="${settings.plannerCookingTime}"><small>${esc(tr('minutes'))}</small></label><label>${esc(tr('Default servings'))}<input data-setting="plannerServings" type="number" min="1" max="30" value="${settings.plannerServings}"></label></div></section>
		<section class="panel form-section"><div class="section-heading"><div><p class="eyebrow">${esc(tr('Documents'))}</p><h2>${esc(tr('OCR and PDF extraction'))}</h2></div><div class="ocr-heading-actions"><span class="status-pill ${settings.ocrProvider !== 'disabled' ? 'enabled' : ''}">${esc(settings.ocrProvider === 'disabled' ? tr('Disabled') : tr('Enabled'))}</span><button class="secondary icon-button ocr-help-button" data-ocr-help type="button" aria-label="${attr(tr('OCR setup'))}" title="${attr(tr('OCR setup'))}">?</button></div></div><div class="form-grid"><label>${esc(tr('Extractor'))}<select data-setting="ocrProvider"><option value="disabled">${esc(tr('Disabled'))}</option><option value="local">${esc(tr('Local Tesseract / pdftotext'))}</option><option value="external">${esc(tr('External HTTP service'))}</option></select></label><label>${esc(tr('OCR languages'))}<input data-setting="ocrLanguage" value="${attr(settings.ocrLanguage)}"></label><label>${esc(tr('Tesseract executable'))}<input data-setting="tesseractPath" value="${attr(settings.tesseractPath)}"></label><label>${esc(tr('pdftotext executable'))}<input data-setting="pdfToTextPath" value="${attr(settings.pdfToTextPath)}"></label><label class="span-2">${esc(tr('External endpoint'))}<input data-setting="ocrEndpoint" type="url" value="${attr(settings.ocrEndpoint)}"></label><label>${esc(tr('API key'))}<input data-setting="ocrApiKey" type="password" autocomplete="new-password" placeholder="${attr(settings.hasOcrApiKey ? tr('Key already stored; leave blank to keep it') : tr('API key'))}"></label><label class="check-inline secret-clear"><input data-setting-clear="ocrApiKey" type="checkbox"> ${esc(tr('Remove stored key'))}</label></div></section>
		<section class="panel form-section danger-zone"><p class="eyebrow">${esc(tr('Danger zone'))}</p><h2>${esc(tr('Cover image'))}</h2><p>${esc(tr('Uses the selected provider with the recipe title and saves the first suitable result as the cover.'))}</p><button class="danger secondary full" data-fill-missing-covers type="button">${esc(tr('Find covers for all missing recipes'))}</button></section>
		</main>
		<aside class="panel privacy-card"><p class="eyebrow">${esc(tr('Privacy'))}</p><h2>${esc(tr('You control every processor'))}</h2><p>${esc(tr('Deterministic URL and text parsing stays in your Nextcloud. Content is sent to an AI or external OCR service only when you enable and invoke it.'))}</p><ul><li>${esc(tr('API keys are encrypted with the Nextcloud server secret.'))}</li><li>${esc(tr('Imported data is always shown as an editable preview.'))}</li><li>${esc(tr('URL imports reject private and reserved network addresses.'))}</li></ul></aside></div>`;
    const aiProvider = view.querySelector('[data-setting="aiProvider"]');
    if (aiProvider)
        aiProvider.value = settings.aiProvider;
    const ocrProvider = view.querySelector('[data-setting="ocrProvider"]');
    if (ocrProvider)
        ocrProvider.value = settings.ocrProvider;
    const coverImageProvider = view.querySelector('[data-setting="coverImageProvider"]');
    if (coverImageProvider)
        coverImageProvider.value = settings.coverImageProvider || 'google';
    view.querySelector('[data-ocr-help]')?.addEventListener('click', openOcrHelp);
    view.querySelector('[data-save-settings]')?.addEventListener('click', async () => {
        const payload = {};
        view.querySelectorAll('[data-setting]').forEach(field => {
            const key = field.dataset.setting || '';
            if (!key)
                return;
            payload[key] = field instanceof HTMLInputElement && field.type === 'number' ? asNumber(field.value) : field.value;
        });
        payload.clearAiApiKey = view.querySelector('[data-setting-clear="aiApiKey"]')?.checked || false;
        payload.clearOcrApiKey = view.querySelector('[data-setting-clear="ocrApiKey"]')?.checked || false;
        payload.clearGoogleImageSearchApiKey = view.querySelector('[data-setting-clear="googleImageSearchApiKey"]')?.checked || false;
        payload.clearPexelsApiKey = view.querySelector('[data-setting-clear="pexelsApiKey"]')?.checked || false;
        payload.clearUnsplashAccessKey = view.querySelector('[data-setting-clear="unsplashAccessKey"]')?.checked || false;
        await working(() => request('/settings', { method: 'PUT', json: { settings: payload } }));
        showNotice(tr('Settings saved'));
        await renderSettings(view);
    });
    view.querySelector('[data-fill-missing-covers]')?.addEventListener('click', async () => {
        if (!window.confirm(tr('This will search for and download covers for all your recipes without an image. Continue?')))
            return;
        let remaining = 1;
        let succeeded = 0;
        let failed = 0;
        while (remaining > 0) {
            const response = await working(() => request('/settings/fill-missing-covers', { method: 'POST', json: {} }));
            const result = response.result || {};
            succeeded += asNumber(result.succeeded);
            failed += asNumber(result.failed);
            remaining = asNumber(result.remaining);
        }
        showNotice(`${tr('Cover search completed')}: ${succeeded} ${fa('check')}${failed ? ` · ${failed} ${tr('failed')}` : ''}`);
    });
}
async function renderPublic(rootNode) {
    const token = rootNode.dataset.token || '';
    const load = async (password = '') => {
        rootNode.innerHTML = `<main class="public-page"><div class="public-brand"><img src="${attr(appIconUrl)}" alt=""><strong>SmartCook</strong></div><p>${esc(tr('Loading...'))}</p></main>`;
        try {
            const payload = await request(`/public/${encodeURIComponent(token)}/data`, { method: 'POST', json: { password } });
            const recipe = payload.recipe;
            const image = recipeImageUrl(recipe.imagePath);
            rootNode.innerHTML = `<main class="public-page"><div class="public-brand"><img src="${attr(appIconUrl)}" alt=""><strong>SmartCook</strong></div><article class="public-recipe">${image ? `<img class="hero" src="${attr(image)}" alt="">` : ''}<p class="eyebrow">${esc(recipe.cuisine)}</p><h1>${esc(recipe.title)}</h1><p class="lead">${esc(recipe.description)}</p><div class="metrics"><div><strong>${recipe.servings}</strong><span>${esc(tr('Servings'))}</span></div><div><strong>${recipe.prepTime} min</strong><span>${esc(tr('Preparation'))}</span></div><div><strong>${recipe.cookTime} min</strong><span>${esc(tr('Cooking'))}</span></div><div><strong>${recipe.totalTime} min</strong><span>${esc(tr('Total'))}</span></div></div><div class="public-grid"><section><h2>${esc(tr('Ingredients'))}</h2><ul>${recipe.ingredients.map(item => { const alternatives = (item.alternatives || []).map(alternative => [alternative.quantity, displayUnit(alternative.unit), alternative.name].filter(Boolean).join(' ')).filter(Boolean); return `<li><b>${esc(item.quantity)} ${esc(displayUnit(item.unit))}</b> ${esc(item.name)} <small>${esc(item.notes)}</small>${alternatives.length ? `<small>${esc(tr('Alternatives'))}: ${esc(alternatives.join(' · '))}</small>` : ''}</li>`; }).join('')}</ul></section><section><h2>${esc(tr('Method'))}</h2><ol>${recipe.steps.map(step => `<li>${esc(step.text)}</li>`).join('')}</ol></section></div></article></main>`;
        }
        catch (error) {
            const message = error instanceof Error ? error.message : tr('Could not load the shared recipe');
            rootNode.innerHTML = `<main class="public-page"><div class="public-brand"><img src="${attr(appIconUrl)}" alt=""><strong>SmartCook</strong></div><form class="password-card"><h1>${esc(tr('Shared recipe'))}</h1><p>${esc(message)}</p><label>${esc(tr('Password'))}<input data-public-password type="password" autocomplete="current-password"></label><button class="primary" type="submit">${esc(tr('Open recipe'))}</button></form></main>`;
            rootNode.querySelector('form')?.addEventListener('submit', event => { event.preventDefault(); void load(rootNode.querySelector('[data-public-password]')?.value || ''); });
        }
    };
    await load();
}
async function route() {
    if (!root)
        return;
    const current = parseRoute();
    const view = renderShell(current.section, current.id);
    try {
        switch (current.section) {
            case 'dashboard':
                await renderDashboard(view);
                break;
            case 'recipes':
                await renderRecipes(view, current.params);
                break;
            case 'recipe':
                await renderRecipe(view, current.id);
                break;
            case 'editor':
                await renderEditor(view, current.id);
                break;
            case 'import':
                await renderImport(view);
                break;
            case 'assistant':
                await renderAssistant(view);
                break;
            case 'planner':
                await renderPlanner(view);
                break;
            case 'shopping':
                await renderShopping(view);
                break;
            case 'administration':
                await renderAdministration(view);
                break;
            case 'settings':
                await renderSettings(view);
                break;
            default: await renderDashboard(view);
        }
    }
    catch (error) {
        if (!(error instanceof ApiError))
            console.error(error);
    }
}
if (publicRoot) {
    void renderPublic(publicRoot);
}
else if (root) {
    window.addEventListener('hashchange', () => { void route(); });
    void route();
}
