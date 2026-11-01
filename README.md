# SmartCook – integration branch

Fork build of [gabryk91/smartcook](https://github.com/gabryk91/smartcook): upstream `main` + all open PRs + our own patches.
Version = `<upstream>-<patch level>` (`appinfo/info.xml`). Upstream base: **1.0.196**. Current: **1.0.196-15**.

## Patches in this branch

### Upstream PRs (merged here, not ours)

| PR | Since | Keywords |
| --- | --- | --- |
| [#2](https://github.com/gabryk91/smartcook/pull/2) | 1.0.196 | French (fr) translation, fr plural rule |
| [#3](https://github.com/gabryk91/smartcook/pull/3) | 1.0.196 | unit labels (tsp, tbsp, cup, pc, clove, pinch, to_taste) from the translation catalog |
| [#4](https://github.com/gabryk91/smartcook/pull/4) | 1.0.196 | ingredient bulk edit, category + substitutes update |

### Our patches

| Patch (branch / PR) | Since | Keywords |
| --- | --- | --- |
| German (de) translation – `l10n-de`, [#6](https://github.com/gabryk91/smartcook/pull/6) | 1.0.196 | `de.json`/`de.js`, informal "du", Nextcloud terms; plus de texts for #4 (this branch only) |
| Dark theme fix – `fix-dark-mode`, [#8](https://github.com/gabryk91/smartcook/pull/8) | 1.0.196 | tokens on `:root, body` (Nextcloud dark vars live on `body`), hard-coded light colors derived from surface |
| Plural-aware counts – `l10n-plurals` | 1.0.196-1 | `trn()` on `n()`, `_%n x_::_%n xs_` keys for en/it/fr/de, "1 recipe" instead of "1 recipes", l10n test checks plural keys |
| Composed texts – `l10n-composed-texts` | 1.0.196-2 | "Decrease/Increase servings" as full keys, shopping progress `{checked} of %n items completed`, `trn()` vars |
| Hard-coded texts – `l10n-hardcoded-texts` | 1.0.196-3 | backend error messages translated (`localizeApiMessage`, patterns + placeholders), `min` unit, manifest description, `tr(text, vars)`; de "Danger zone" = "Unwiderrufliche Aktionen" |
| AI meal plan prompt – `l10n-ai-prompts` | 1.0.196-4 | prompt in en/it/fr/de via `IL10N`, default instruction not stored (placeholder), no Italian example |
| Default language – `language-setting` | 1.0.196-4 | setting was never used; now default for new recipes, import output, assistant; `auto` = Nextcloud language; datalist autocompletion; validation |
| OCR default language – `ocr-language-default` | 1.0.196-5 | default from Nextcloud language (`deu+eng`, `fra+eng`, …) instead of `ita+eng`; OCR help lists matching packages |
| AI provider errors – `ai-http-errors` | 1.0.196-6 | retry on 429/502/503/504 (Retry-After), readable translated errors instead of raw Guzzle message / "unexpected error", API key no longer in logs or messages |
| Ingredient units de/fr – `ingredient-units` | 1.0.196-7 (fr: -8, labels: -9) | parser knows German and French units (fr: cuillère à soupe/café, pincée, gousse, tranche, boîte, `de`/`d'` after the unit); ingredient text editor shows translated unit labels, all en/it/fr/de labels map back (q.b., nach Geschmack, au goût); German: parser knows German units (TL, EL, Prise, Zehe, Stück, Kopf, Scheibe, Bund, Dose, Päckchen, …) with canonical codes, `/n` and `(n)` plural markers, `Zitrone(n)` / `Ei(er)` no longer become notes, `Knoblauchzehe(n)` → clove + Knoblauch, smoke tests |
| Empty quantity in import editor – `import-empty-quantity` | 1.0.196-10 | empty quantity cell in the ingredient text editor is no quantity instead of `0` ("0 Öl"); fractions and decimal commas typed there are kept |
| Household – `household` | 1.0.196-11 (limit: -12, several groups: -13) | everybody picks one or several of their own Nextcloud groups as household (SmartCook settings); members of the same household see and edit each other's recipes, planner and shopping lists; entries stay owned by their creator (leaving or switching hides them again); AI/cover settings stay personal; taxonomy lists merged by name; admin form (Additional settings) limits the selectable groups (empty = all), a choice that is no longer allowed hides the shared data but is kept |
| Translate recipe – `recipe-translate` | 1.0.196-14 | AI button (only shown when an AI provider is configured) translates the recipe into the user's language: toggle on the overview (view only, cached), button in the editor and in each import preview (fills the form); `POST /recipes/translate`, `AiPromptFactory::forRequest()` replaces the duplicated prompt selection of the four providers |
| AI JSON robustness – `ai-json-diagnostics` | 1.0.196-15 | raw control characters inside AI JSON strings are accepted, one retry when the answer is still not valid JSON, the shape of a failed answer is logged (no recipe content beyond start/end) |

## Maintaining this list

- Add one row per patch merged into `integration` (keywords, branch/PR, version) and raise the patch level in `appinfo/info.xml`.
- Documentation-only changes (like this file) do not raise the patch level.
- When upstream releases a new version: merge `upstream/main`, reset the patch level to `-1`, keep the rows (drop a row once its PR is part of upstream).
