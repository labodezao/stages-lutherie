# Repo stages-lutherie — notes pour Claude Code

## Synchronisation Gutenberg ↔ seed (règle absolue)

Les fichiers Gutenberg ont **deux emplacements** qui doivent toujours être identiques :

| Source de vérité (édition humaine) | Copie déployée (lue par seed.php) |
|---|---|
| `comm/page-blocs-gutenberg.txt` | `wordpress/themes/savoy/includes/stages-fr.html` |
| `comm/page-blocs-gutenberg-en.txt` | `wordpress/themes/savoy/includes/stages-en.html` |
| `inscriptions/formulaire-inscription-gutenberg.txt` | `wordpress/themes/savoy/includes/inscriptions-fr.html` |
| `inscriptions/formulaire-inscription-en-gutenberg.txt` | `wordpress/themes/savoy/includes/inscriptions-en.html` |

**Règle** : toute modification d'un fichier source doit être répercutée dans la copie `includes/` dans le même commit, et vice-versa.

## Architecture WordPress

- **mu-plugin** : `wordpress/mu-plugins/evd.php` → copier dans `wp-content/mu-plugins/evd.php`
- **seed** : `wordpress/themes/savoy/includes/seed.php` → copier dans `wp-content/themes/savoy/includes/seed.php`
- **block JS** : `wordpress/themes/savoy/assets/block.js` → copier dans `wp-content/themes/savoy/assets/block.js`
- Thème actif : **Savoy** (dossier `includes/`, pas `inc/`)

## Règles seed.php

- Ne jamais utiliser `wp_insert_post()` / `wp_update_post()` pour le contenu de page — corruption JSON via `wp_unslash`. Toujours `$wpdb->update()` direct.
- `evd_read_include('fichier.html')` lit depuis `get_template_directory() . '/includes/'`
- Slugs réels : `stages-lutherie` (FR), `stages-accordion-workshop` (EN)

## Messaging — principe directeur

Le stage est un **parcours d'apprentissage**, pas une promesse de résultat. Ne pas écrire "repartez avec votre instrument" comme garanti. Le non-achèvement en 10 jours est normal ; les séances de retour à **80 €/jour** sont une option naturelle à mentionner.
