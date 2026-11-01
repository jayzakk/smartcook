<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service;

final class TextNormalizer {
    private const FRACTIONS = [
        '¼' => 0.25,
        '½' => 0.5,
        '¾' => 0.75,
        '⅓' => 1 / 3,
        '⅔' => 2 / 3,
        '⅛' => 0.125,
        '⅜' => 0.375,
        '⅝' => 0.625,
        '⅞' => 0.875,
    ];

    /** @var array<string, string> */
    private const UNIT_ALIASES = [
        'g' => 'g', 'gr' => 'g', 'grammo' => 'g', 'grammi' => 'g', 'gram' => 'g', 'grams' => 'g',
        'kg' => 'kg', 'chilogrammo' => 'kg', 'chilogrammi' => 'kg', 'kilogram' => 'kg', 'kilograms' => 'kg',
        'mg' => 'mg',
        'ml' => 'ml', 'millilitro' => 'ml', 'millilitri' => 'ml',
        'cl' => 'cl', 'dl' => 'dl',
        'l' => 'l', 'lt' => 'l', 'litro' => 'l', 'litri' => 'l', 'liter' => 'l', 'liters' => 'l',
        'tsp' => 'tsp', 'teaspoon' => 'tsp', 'teaspoons' => 'tsp', 'cucchiaino' => 'tsp', 'cucchiaini' => 'tsp',
        'tbsp' => 'tbsp', 'tablespoon' => 'tbsp', 'tablespoons' => 'tbsp', 'cucchiaio' => 'tbsp', 'cucchiai' => 'tbsp',
        'cup' => 'cup', 'cups' => 'cup', 'tazza' => 'cup', 'tazze' => 'cup',
        'oz' => 'oz', 'ounce' => 'oz', 'ounces' => 'oz',
        'lb' => 'lb', 'lbs' => 'lb', 'pound' => 'lb', 'pounds' => 'lb',
        'pz' => 'pc', 'pezzo' => 'pc', 'pezzi' => 'pc', 'piece' => 'pc', 'pieces' => 'pc', 'pc' => 'pc',
        'spicchio' => 'clove', 'spicchi' => 'clove', 'clove' => 'clove', 'cloves' => 'clove',
        'pizzico' => 'pinch', 'pizzichi' => 'pinch', 'pinch' => 'pinch',
        'q.b.' => 'to_taste', 'qb' => 'to_taste', 'quanto basta' => 'to_taste', 'to taste' => 'to_taste',
        'q b' => 'to_taste', 'nach geschmack' => 'to_taste', 'au gout' => 'to_taste',
        // German; keys are lower case with umlauts written out (see normalizeUnit). Units without a canonical
        // code are stored in their singular form, as written in the recipe.
        'gramm' => 'g', 'kilo' => 'kg', 'kilogramm' => 'kg', 'milliliter' => 'ml', 'liter' => 'l',
        'tl' => 'tsp', 'teeloeffel' => 'tsp', 'el' => 'tbsp', 'essloeffel' => 'tbsp',
        'tasse' => 'cup', 'tassen' => 'cup',
        'stueck' => 'pc', 'stk' => 'pc', 'zehe' => 'clove', 'zehen' => 'clove',
        'prise' => 'pinch', 'prisen' => 'pinch',
        'scheiben' => 'Scheibe', 'scheibe' => 'Scheibe', 'dosen' => 'Dose', 'dose' => 'Dose',
        'koepfe' => 'Kopf', 'kopf' => 'Kopf', 'bund' => 'Bund', 'buende' => 'Bund',
        'paeckchen' => 'Päckchen', 'pck' => 'Päckchen', 'packung' => 'Packung', 'packungen' => 'Packung', 'pkg' => 'Packung',
        'glaeser' => 'Glas', 'glas' => 'Glas', 'flaschen' => 'Flasche', 'flasche' => 'Flasche',
        'zweige' => 'Zweig', 'zweig' => 'Zweig', 'stangen' => 'Stange', 'stange' => 'Stange',
        'stiele' => 'Stiel', 'stiel' => 'Stiel', 'blaetter' => 'Blatt', 'blatt' => 'Blatt',
        'knollen' => 'Knolle', 'knolle' => 'Knolle', 'becher' => 'Becher', 'beutel' => 'Beutel',
        'wuerfel' => 'Würfel', 'msp' => 'Msp.', 'messerspitze' => 'Msp.', 'messerspitzen' => 'Msp.',
        'handvoll' => 'Handvoll', 'schuss' => 'Schuss', 'spritzer' => 'Spritzer', 'tropfen' => 'Tropfen',
        // French; keys after normalizeUnit() has written out accents, "c. à soupe" becomes "c a soupe".
        'cuillere a soupe' => 'tbsp', 'cuilleres a soupe' => 'tbsp', 'c a soupe' => 'tbsp', 'c a s' => 'tbsp', 'cas' => 'tbsp',
        'cuillere a cafe' => 'tsp', 'cuilleres a cafe' => 'tsp', 'c a cafe' => 'tsp', 'c a c' => 'tsp', 'cac' => 'tsp',
        'pincee' => 'pinch', 'pincees' => 'pinch', 'gousse' => 'clove', 'gousses' => 'clove',
        'morceau' => 'pc', 'morceaux' => 'pc', 'gramme' => 'g', 'grammes' => 'g', 'litre' => 'l', 'litres' => 'l',
        'tranche' => 'Tranche', 'tranches' => 'Tranche', 'boite' => 'Boîte', 'boites' => 'Boîte',
        'sachet' => 'Sachet', 'sachets' => 'Sachet', 'tete' => 'Tête', 'tetes' => 'Tête',
        'botte' => 'Botte', 'bottes' => 'Botte', 'brin' => 'Brin', 'brins' => 'Brin',
        'feuille' => 'Feuille', 'feuilles' => 'Feuille', 'tige' => 'Tige', 'tiges' => 'Tige',
        'verre' => 'Verre', 'verres' => 'Verre', 'poignee' => 'Poignée', 'poignees' => 'Poignée',
    ];

    public function normalizeName(string $value): string {
        $value = trim(mb_strtolower($value));
        $value = str_replace(['’', '`'], "'", $value);
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($transliterated) && $transliterated !== '') {
            $value = $transliterated;
        }
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? $value;
        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    public function capitalizeLabel(string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        preg_match('/^./us', $value, $match);
        $first = $match[0] ?? $value[0];
        $upper = function_exists('mb_strtoupper') ? mb_strtoupper($first) : strtoupper(strtr($first, ['à' => 'À', 'è' => 'È', 'é' => 'É', 'ì' => 'Ì', 'ò' => 'Ò', 'ù' => 'Ù']));
        return $upper . substr($value, strlen($first));
    }

    public function normalizeUnit(?string $unit): ?string {
        if ($unit === null || trim($unit) === '') {
            return null;
        }
        // Write umlauts and accents out first: transliteration to ASCII depends on the server locale.
        $normalized = $this->normalizeName(strtr(mb_strtolower(trim($unit)), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a', 'ç' => 'c', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'û' => 'u', 'ù' => 'u']));
        return self::UNIT_ALIASES[$normalized] ?? trim($unit);
    }

    public function parseQuantity(?string $quantity): ?float {
        if ($quantity === null) {
            return null;
        }
        $quantity = trim(str_replace(',', '.', $quantity));
        if ($quantity === '') {
            return null;
        }

        $unicodeTotal = 0.0;
        foreach (self::FRACTIONS as $symbol => $value) {
            if (str_contains($quantity, $symbol)) {
                $unicodeTotal += $value;
                $quantity = str_replace($symbol, '', $quantity);
            }
        }

        $quantity = trim($quantity);
        if ($quantity === '') {
            return $unicodeTotal > 0 ? $unicodeTotal : null;
        }

        $parts = preg_split('/\s+/', $quantity) ?: [];
        $total = $unicodeTotal;
        foreach ($parts as $part) {
            if (preg_match('/^(-?\d+)\/(\d+)$/', $part, $match) === 1 && (int)$match[2] !== 0) {
                $total += (int)$match[1] / (int)$match[2];
            } elseif (is_numeric($part)) {
                $total += (float)$part;
            }
        }

        return $total === 0.0 && !preg_match('/\b0(?:\.0+)?\b/', $quantity) ? null : $total;
    }

    public function parseDuration(mixed $value): int {
        if (is_int($value) || is_float($value)) {
            return max(0, (int)round((float)$value));
        }
        if (!is_string($value) || trim($value) === '') {
            return 0;
        }
        $value = trim($value);
        if (preg_match('/^P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/i', $value, $m) === 1) {
            return ((int)($m[1] ?? 0) * 1440) + ((int)($m[2] ?? 0) * 60) + (int)($m[3] ?? 0) + (int)ceil(((int)($m[4] ?? 0)) / 60);
        }
        $minutes = 0;
        if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*(d|day|days|giorno|giorni|h|hr|hour|hours|ora|ore|min|mins|minute|minutes|minuto|minuti|s|sec|second|seconds|secondo|secondi)/i', $value, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $number = (float)str_replace(',', '.', $match[1]);
                $unit = mb_strtolower($match[2]);
                $minutes += match ($unit) {
                    'd', 'day', 'days', 'giorno', 'giorni' => (int)round($number * 1440),
                    'h', 'hr', 'hour', 'hours', 'ora', 'ore' => (int)round($number * 60),
                    's', 'sec', 'second', 'seconds', 'secondo', 'secondi' => (int)ceil($number / 60),
                    default => (int)round($number),
                };
            }
            return max(0, $minutes);
        }
        return is_numeric($value) ? max(0, (int)round((float)$value)) : 0;
    }

    public function slug(string $value): string {
        $slug = str_replace(' ', '-', $this->normalizeName($value));
        return trim($slug, '-') ?: 'recipe';
    }

    /** @return list<string> */
    public function normalizeStringList(mixed $values): array {
        if (is_string($values)) {
            $values = preg_split('/[,;\n]+/', $values) ?: [];
        }
        if (!is_array($values)) {
            return [];
        }
        $result = [];
        $seen = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                $value = $value['name'] ?? '';
            }
            if (!is_scalar($value)) {
                continue;
            }
            $label = trim((string)$value);
            $key = $this->normalizeName($label);
            if ($label !== '' && $key !== '' && !isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $label;
            }
        }
        return $result;
    }

    public function compactText(string $value): string {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[ \t]+/', ' ', $value) ?? $value;
        return trim(preg_replace('/\n{3,}/', "\n\n", $value) ?? $value);
    }
}
