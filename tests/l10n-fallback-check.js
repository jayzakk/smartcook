"use strict";

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.resolve(__dirname, '..');
const bundle = fs.readFileSync(path.join(root, 'js', 'smartcook-main.js'), 'utf8');
const catalog = JSON.parse(fs.readFileSync(path.join(root, 'l10n', 'it.json'), 'utf8')).translations;
const fallbackSource = bundle.slice(0, bundle.indexOf('const tr =')) + ';fallbackTranslations';
const fallback = vm.runInNewContext(fallbackSource, {
    window: {},
    document: { getElementById: () => null },
}).it;
const keys = [...bundle.matchAll(/tr\('([^']+)'\)/g)].map(match => match[1]);
const missing = [...new Set(keys.filter(key => !(key in catalog) && !(key in fallback)))].sort();

// Plural-aware texts (trn(singular, plural, count)) live in the catalog as '_<singular>_::_<plural>_'.
const pluralKeys = [...bundle.matchAll(/trn\('([^']+)', '([^']+)'/g)].map(match => `_${match[1]}_::_${match[2]}_`);
const missingPlurals = [...new Set(pluralKeys.filter(key => !(key in catalog)))].sort();

if (missing.length || missingPlurals.length) {
    throw new Error(`Italian translations missing for: ${[...missing, ...missingPlurals].join(', ')}`);
}

console.log('Italian translations cover every bundle key.');
