<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service\Import;

use OCA\SmartCook\Service\TextNormalizer;

final class IngredientParser {
    private const UNITS = [
        'kg', 'g', 'gr', 'mg', 'l', 'lt', 'dl', 'cl', 'ml', 'oz', 'lb', 'lbs',
        'cup', 'cups', 'tazza', 'tazze', 'tbsp', 'tablespoon', 'tablespoons', 'cucchiaio', 'cucchiai',
        'tsp', 'teaspoon', 'teaspoons', 'cucchiaino', 'cucchiaini', 'pz', 'pc', 'piece', 'pieces', 'pezzo', 'pezzi',
        'clove', 'cloves', 'spicchio', 'spicchi', 'pinch', 'pizzico', 'pizzichi', 'bunch', 'mazzo', 'mazzetto',
        'slice', 'slices', 'fetta', 'fette', 'can', 'cans', 'lattina', 'lattine', 'package', 'confezione',
        // German
        'gramm', 'kilo', 'kilogramm', 'milliliter', 'liter', 'tl', 'el', 'teelöffel', 'esslöffel', 'tasse', 'tassen',
        'stück', 'stk', 'zehe', 'zehen', 'prise', 'prisen', 'scheibe', 'scheiben', 'dose', 'dosen', 'kopf', 'köpfe',
        'bund', 'bünde', 'päckchen', 'pck', 'packung', 'packungen', 'pkg', 'glas', 'gläser', 'flasche', 'flaschen',
        'zweig', 'zweige', 'stange', 'stangen', 'stiel', 'stiele', 'blatt', 'blätter', 'knolle', 'knollen', 'becher',
        'beutel', 'würfel', 'msp', 'messerspitze', 'messerspitzen', 'handvoll', 'schuss', 'spritzer', 'tropfen',
        // French
        'cuillère à soupe', 'cuillères à soupe', 'c. à soupe', 'c. à s.', 'càs', 'cuillère à café', 'cuillères à café',
        'c. à café', 'c. à c.', 'càc', 'pincée', 'pincées', 'gousse', 'gousses', 'tranche', 'tranches', 'boîte', 'boîtes',
        'sachet', 'sachets', 'tête', 'têtes', 'botte', 'bottes', 'brin', 'brins', 'feuille', 'feuilles', 'tige', 'tiges',
        'morceau', 'morceaux', 'pièce', 'pièces', 'gramme', 'grammes', 'litre', 'litres', 'verre', 'verres', 'poignée', 'poignées',
    ];
    /** Optional plural written behind a unit or name: "Scheibe/n", "Dose(n)", "Ei(er)". */
    private const PLURAL_MARKER = '(?:\\/\\p{L}{1,3}|\\(\\p{L}{1,3}\\))';

    public function __construct(private TextNormalizer $normalizer) {
    }

    /** @return array<string, mixed> */
    public function parse(string $line, int $sortOrder = 0): array {
        $original = $this->withoutLeadingMarkers($line);
        $line = $original;
        $optional = preg_match('/\b(optional|facoltativ[oaie])\b/i', $line) === 1;
        $group = null;

        if (preg_match('/^(?:q\.?\s*b\.?|quanto basta|to taste)\s+(.+)$/iu', $line, $m) === 1) {
            return $this->build($m[1], 'q.b.', null, 'to_taste', null, $optional, $group, $original, $sortOrder);
        }

        $fractionChars = '¼½¾⅓⅔⅛⅜⅝⅞';
        $quantityPattern = '(?:\d+\s*[' . $fractionChars . ']|\d+(?:[.,]\d+)?(?:\s+\d+\/\d+)?|\d+\/\d+|[' . $fractionChars . '])(?:\s*(?:-|–|—|a|to)\s*(?:\d+(?:[.,]\d+)?|\d+\/\d+|[' . $fractionChars . ']))?';
        $unitPattern = implode('|', array_map(static fn (string $unit): string => preg_quote($unit, '/'), self::UNITS));
        $quantity = null;
        $unit = null;
        $name = $line;

        if (preg_match('/^(' . $quantityPattern . ')\s*(?:x\s*)?(?:(' . $unitPattern . ')' . self::PLURAL_MARKER . '?\.?(?![\p{L}\p{N}]))?\s*(?:di\s+|of\s+|de\s+|d[\x27’]\s*)?(.+)$/iu', $line, $m) === 1) {
            $quantity = trim($m[1]);
            $unit = isset($m[2]) && $m[2] !== '' ? $this->normalizer->normalizeUnit($m[2]) : null;
            $name = trim($m[3]);
        } elseif (preg_match('/^(' . $unitPattern . ')' . self::PLURAL_MARKER . '?\.?\s+(?:di\s+|of\s+|de\s+|d[\x27’]\s*)?(.+)$/iu', $line, $m) === 1) {
            $unit = $this->normalizer->normalizeUnit($m[1]);
            $name = trim($m[2]);
        } elseif (preg_match('/^(.+?)\s+(' . $quantityPattern . ')\s+(' . $unitPattern . ')' . self::PLURAL_MARKER . '?\.?(?![\p{L}\p{N}])(?:\s+(.+))?$/iu', $line, $m) === 1) {
            $name = trim($m[1]);
            $quantity = trim($m[2]);
            $unit = $this->normalizer->normalizeUnit($m[3]);
            $notes = isset($m[4]) && trim($m[4]) !== '' ? trim($m[4]) : null;
        }

        $notes ??= null;
        // "Zitrone(n)", "Ei(er)": the bracket only spells out the plural, it is not a note.
        $name = preg_replace('/(?<=\p{L})\(\p{L}{1,3}\)(?=\s|,|;|$)/u', '', $name) ?? $name;
        if ($notes === null && preg_match('/^(.+?)\s*\(([^)]+)\)\s*$/u', $name, $m) === 1) {
            $name = trim($m[1]);
            $notes = trim($m[2]);
        } elseif ($notes === null && preg_match('/^(.+?),\s*(.+)$/u', $name, $m) === 1 && mb_strlen($m[2]) < 100) {
            $name = trim($m[1]);
            $notes = trim($m[2]);
        }
        $name = preg_replace('/\b(optional|facoltativ[oaie])\b/iu', '', $name) ?? $name;
        $name = trim($name, " \t\n\r\0\x0B,;-");
        // German "Knoblauchzehe(n)": the clove is the unit, the rest is the ingredient.
        if ($unit === null && preg_match('/^(\p{L}{3,}?)zehen?$/iu', $name, $m) === 1) {
            $unit = 'clove';
            $name = $m[1];
        }

        return $this->build($name, $quantity, $this->normalizer->parseQuantity($quantity), $unit, $notes, $optional, $group, $original, $sortOrder);
    }

    public function looksLikeIngredient(string $line): bool {
        $line = $this->withoutLeadingMarkers($line);
        if ($line === '' || mb_strlen($line) > 240) {
            return false;
        }
        if (preg_match('/^(?:\d+(?:[.,]\d+)?|\d+\/\d+|[¼½¾⅓⅔⅛⅜⅝⅞]|q\.?\s*b\.?|quanto basta|to taste)\b/iu', $line) === 1) {
            return true;
        }
        return preg_match('/\b(?:kg|g|gr|mg|ml|cl|dl|l|cup|cups|tbsp|tsp|cucchiai?|cucchiaini?|grammi?|litri?|oz|lb|pezzi?|spicchi?)\b/iu', $line) === 1;
    }

    private function withoutLeadingMarkers(string $line): string {
        $line = trim($line);
        // Captions commonly use food emoji as bullets (for example "🧅 1 cipolla").
        // Keep letters, numbers and fraction characters while removing presentation-only prefixes.
        return trim(preg_replace('/^[^\p{L}\p{N}¼½¾⅓⅔⅛⅜⅝⅞]+/u', '', $line) ?? $line);
    }

    /** @return array<string, mixed> */
    private function build(string $name, ?string $quantity, ?float $amount, ?string $unit, ?string $notes, bool $optional, ?string $group, string $original, int $sortOrder): array {
        return [
            'name' => trim($name),
            'originalText' => $original,
            'quantity' => $quantity,
            'amount' => $amount,
            'unit' => $unit,
            'notes' => $notes,
            'optional' => $optional,
            'group' => $group,
            'category' => null,
            'allergens' => [],
            'substitutes' => [],
            'sortOrder' => $sortOrder,
        ];
    }
}
