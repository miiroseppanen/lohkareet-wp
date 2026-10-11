# Lohkareet

Custom WordPress theme for [lohkareet.fi](https://lohkareet.fi).

Lohkareet is a series of nine ambient music events in different municipalities in Finland. This repository holds the theme source. It is not a WordPress installation. The site runs on a Hostaan WordPress server. WordPress core, the database, media uploads, and plugins stay on that server. All custom website code lives in `/theme`.

Intended Git remote: `git@github.com:miiroseppanen/lohkareet-wp.git`.

## Architecture

| Location | What lives there |
| --- | --- |
| This Git repository, `/theme` | Theme source: templates, `theme.json`, event PHP, CSS, the editor preview script |
| Hostaan WordPress server | WordPress core, database, uploads, plugins, the activated copy of the theme |
| WordPress database | Pages, events, media, menus, and any Site Editor overrides saved on the server |

On the server, the theme is installed as `wp-content/themes/lohkareet`. The folder in Git is `/theme`. Deploy by copying the contents of `/theme` into `wp-content/themes/lohkareet`. The directory name on the server must be `lohkareet` so the theme slug matches the text domain.

Lohkareet is a block theme. Templates are HTML files made of Gutenberg blocks. Colors, type, and layout live in `theme.json`. Event facts that must stay queryable live in registered post meta, not in block content and not in the templates.

Content edited in WordPress admin is stored in the database, not in Git. A change made only in the Site Editor on the server can override a theme file. Treat the files in Git as the source of truth.

```
Browser
  → Hostaan (WordPress)
      → active theme: wp-content/themes/lohkareet
          ← deployed from this repo's /theme
      → database (pages, events, media, Site Editor overrides)
```

There is no build step. The public site uses PHP, HTML, and CSS. `assets/js/blocks-editor.js` loads only in the block editor, and only to preview the theme's dynamic blocks with WordPress core packages. It is not a front-end React application.

## Languages

Finnish is the primary language. English is the secondary language. The public site needs a complete version in each.

The theme does not contain a translation system or language URL prefixes. Polylang is installed on the server, not shipped in the theme. The theme still activates and renders if Polylang is inactive. Polylang is responsible for:

- linking the Finnish and English versions of pages and events
- selecting the WordPress locale, so theme strings and month names follow the language
- language-specific navigation
- translated post slugs, using what Polylang Free already supports
- deciding which language the front end is showing

The theme does not route `/en/` itself.

### Polylang language settings

Add Polylang’s predefined languages and leave the locale fields as Polylang fills them in. The language slug and the WordPress locale are not the same setting.

| Language | Polylang slug | WordPress locale | Theme UI strings |
| --- | --- | --- | --- |
| Suomi, default | `fi` | `fi` | `theme/languages/fi.l10n.php` |
| English | `en` | `en_US` | English source strings in the theme PHP |

`fi` is the standard Finnish WordPress locale. Do not change it to `fi_FI`. The theme file is named for `fi`, and the Finnish WordPress language pack uses the same code. English needs no theme translation file: `en_US` shows the English strings written in the PHP.

Polylang switches the locale. The theme does not. Without Polylang, an English WordPress install stays English, and a Finnish install stays Finnish. Theme sentences use the normal WordPress translation functions either way.

### What is translated

Create a separate English event (or page) and link it to the Finnish original in the plugin. The plugin stores that link. Each version has its own:

- title
- block-editor description
- location description (`lohkareet_location_description`)
- artist description (`lohkareet_artist_description`)

Theme sentences such as “The date will be announced later.” are English in the PHP source. WordPress loads `theme/languages/fi.l10n.php` when the active locale is `fi`. Do not branch on the Polylang slug to choose a sentence.

### What is shared

These facts are language-independent. The field names do not have `_fi` or `_en` suffixes. Each translation stores its own copy so the event can be queried on its own, and saving the event form copies the values onto the linked translation when Polylang is active:

- year, month, and day
- start and end time
- municipality
- venue and street address
- per-event map URL
- artist name and artist status (`confirmed`, `tentative`, `unannounced`)

Sorting is calculated from the date fields. It is not stored and it is not translated.

Venue and street address are treated as shared proper names. The location description is the editorial text that gets translated. Say so if a venue name itself needs an English form; that would mean moving `lohkareet_venue` out of the shared list.

The featured image is the same photograph of the same event. When Polylang has linked the two posts, changing or removing the image on one copies that change to the other. The copy is skipped when the other post already uses the same attachment, and it does not copy back onto the post that was just saved.

### Event queries

`lohkareet_get_events()` is the only query behind the next event and the event list. It asks Polylang for the current language slug and sets the `lang` query argument. Filters stay enabled so Polylang can apply that limit. A Finnish event and its English translation are two posts; the front page in one language does not list both.

Without Polylang, the query returns every published event. That is expected on a single-language site.

### Zoneatlas

The home-page map URL depends on the current Polylang slug. The only place to set those URLs is the `fi` and `en` entries in `lohkareet_get_zoneatlas_urls()` in `theme/inc/i18n.php`. Both values are still the current embed:

`https://siirtyma.com/en/lohkareet?cooperativeGesturesEnabled=true`

The `/en/` segment is not assumed to mean English. Replace the `fi` and `en` entries when the final Zoneatlas URLs are known. The helper adds `cooperativeGesturesEnabled=true` if a replacement URL omits it. The map block’s URL field is an optional override for one instance; leave it empty on the home page so the language URLs are used. The iframe stays responsive, has an accessible name, and keeps `allow="fullscreen; geolocation"`.

A per-event map is the event’s own `lohkareet_map_url`. It is shared data, not switched by language, and it does not fall back to the series map.

### Language switcher

`parts/header.html` places a Navigation block and `lohkareet/language-switcher` together. The switcher prints FI / EN from Polylang’s language list. The visible letters come from the slug. The link’s `hreflang` comes from the WordPress locale (`fi`, `en-US`). With Polylang inactive, or only one language configured, the switcher prints nothing and does not raise a PHP error. The Site Editor shows a non-linked FI / EN placeholder so the slot stays visible.

Create the Finnish navigation and its English translation in Polylang. The theme does not store a second menu.

### Pages

Create these as normal pages, then link each English page to its Finnish page in the plugin. The theme does not insert them.

| Page | Finnish | English | Template |
| --- | --- | --- | --- |
| Home | Etusivu | Home | `front-page.html` |
| About Lohkareet | Lohkareista | About Lohkareet | `page.html` |
| Preparing for the event | Varustautuminen | Preparing for the event | `page.html` |
| Events | Tapahtumat | Events | `archive-event.html`, not a page |

Set **Settings → Reading** to a static front page and choose the Finnish home page. Polylang uses the linked English page as the English front page. Each event also needs a Finnish version and an English version.

The event archive base registered by the theme is `/tapahtumat/` in both languages. That is intentional. Polylang Free can still give each event and each page its own translated slug. Do not create a page with the slug `tapahtumat`. The theme does not add language prefixes or extra rewrite rules.

## Directory structure

```
theme/
├── style.css
├── theme.json
├── functions.php
├── inc/
│   ├── i18n.php              # Current language, Zoneatlas URLs, language switcher
│   ├── event-post-type.php   # CPT registration, permalinks, admin columns
│   ├── event-helpers.php     # Reading, sorting, and formatting event data
│   ├── event-meta.php        # Meta registration, sanitizing, editor form
│   └── blocks.php            # Map, next event, event list, event details
├── languages/
│   └── fi.l10n.php           # Finnish translations of theme UI strings
├── templates/
│   ├── index.html            # Fallback: posts, other archives, search
│   ├── front-page.html       # Home: page content, map, next event, all events
│   ├── page.html             # About, preparation, and other pages
│   ├── singular.html         # Single blog posts
│   ├── archive-event.html    # /tapahtumat/
│   └── single-event.html     # One event
├── parts/
│   ├── header.html
│   └── footer.html
└── assets/
    ├── css/theme.css         # Map, event list, and language switcher
    └── js/blocks-editor.js   # Editor preview only
```

`templates/index.html` remains the required fallback. WordPress uses a more specific template when it has one.

## Event post type

Events are the custom post type `event`. They are edited under **Events** in wp-admin (Tapahtumat when the locale is Finnish). Each event has its own page. The shared templates are `single-event.html` and `archive-event.html`. A Finnish event and its English translation are two linked posts, not two sets of custom fields.

Permalinks:

- Archive: `/tapahtumat/`
- Single event: `/tapahtumat/post-name/`

Do not create a page with the slug `tapahtumat`. It would collide with the archive.

The theme does not create the nine events. Enter them in wp-admin. The title and the main description are normal post fields. The description is the block editor content. The image is the featured image.

After the theme is first activated, open **Settings → Permalinks** and save once if an event URL returns a 404. The theme also flushes rewrite rules on activation and once after the event type is introduced.

## Event metadata

Metadata is registered post meta with a meta box under the content editor. Advanced Custom Fields is not used. The values are sanitized on save and escaped on output. Editors with `edit_post` can change them through the REST API as well.

Empty means unknown. Do not type “julkaistaan myöhemmin” into a field. Those sentences are presentation, and the templates add them only when a fact is missing.

| Field | Meta key | Languages | Empty means |
| --- | --- | --- | --- |
| Year | `lohkareet_date_year` | Shared | Year unknown |
| Month | `lohkareet_date_month` | Shared | Month unknown |
| Day | `lohkareet_date_day` | Shared | Day unknown |
| Start time | `lohkareet_start_time` | Shared | `HH:MM`, or unknown |
| End time | `lohkareet_end_time` | Shared | `HH:MM`, or unknown |
| Municipality | `lohkareet_municipality` | Shared | One of the nine names, or unknown |
| Venue | `lohkareet_venue` | Shared | Venue unknown |
| Street address | `lohkareet_address` | Shared | Address unknown |
| Location description | `lohkareet_location_description` | Translated | No description |
| Event map URL | `lohkareet_map_url` | Shared | https URL, or no per-event map |
| Artist name | `lohkareet_artist_name` | Shared | No public name |
| Artist description | `lohkareet_artist_description` | Translated | No description |
| Artist status | `lohkareet_artist_status` | Shared | `confirmed`, `tentative`, or `unannounced` |

Municipality is separate from the venue. The select list is the nine municipalities of this series, in program order. Change the list with the `lohkareet_municipalities` filter in a small plugin or a child theme. A value outside the list is discarded.

A day cannot be saved without a month. An impossible calendar day is discarded. February 29 is allowed when the year is a leap year, and also when the year is still unknown.

The artist name is shown only when the status is confirmed or tentative. Unannounced keeps a stored name off the public page. The status value is shared; the label is a theme string.

### Dates

Precision is derived from which parts are filled in:

| Year | Month | Day | Public text | Machine `datetime` |
| --- | --- | --- | --- | --- |
| yes | yes | yes | Finnish `15. marraskuuta 2026`, English `15 November 2026` | `2026-11-15` |
| yes | yes | no | localized month and year, for example `helmikuu 2027` or `February 2027` | `2027-02` |
| yes | no | no | `2027` | `2027` |
| no | yes | yes | Finnish `1. marraskuuta`, English `1 November` | none |
| no | no | no | The date will be announced later. | none |

`wp_date()` supplies the month name from the active locale. The theme does not list month names. Finnish punctuation lives in the translated format `j. F Y` in `languages/fi.l10n.php`. WordPress then declines the month when the Finnish language pack asks it to, which is what produces “marraskuuta” after the day. A month and year without a day stay in the locale’s standalone month form. The stored year, month, and day do not change with the language.

Nothing stores a placeholder day to make sorting easier. Sorting uses the real parts. Inside the comparator only, a missing day sorts after the exact dates of that month, and a year without a month sorts after the dated events of that year. Events with no year sort after every dated event. Fully unknown dates sort last.

The next event is the earliest event that has a year and is not certainly past. A known day is past after that day, or after the end time if the event is today and an end time exists. A month without a day stays current until the month ends. A year without a month stays current until the year ends. An event with no year is never chosen as the next event, because it cannot be placed on the timeline. It still appears in the full list.

### What the templates hide

Single event pages always show a date line, a place line, and an artist line, using the “will be announced later” sentence when that fact is unknown. Time, street address, location description, per-event map, and artist description render only when they contain something. Cards show the date sentence when the date is unknown, and show venue and time only when those values exist.

The series map on the home page is separate from a per-event map. Its URL comes from `lohkareet_get_zoneatlas_urls()`. Only https URLs are accepted.

## Pages to create in WordPress

The theme does not insert these pages.

1. Create the Finnish and English pages listed under Languages. Home-page content is the introduction. Write the texts about Suomen Ambient-yhdistys and Siirtymä in that page, or on their own pages, when the copy exists. The template places the page content first, then the series map, the next event, and the full event list.
2. Under **Settings → Reading**, choose a static front page and select the Finnish home page. `front-page.html` is used for the site front in every reading mode. Without a static page, WordPress can show the latest post in the content area above the map.
3. Create the nine events from the notes below, in Finnish, and an English translation of each. Publish an event as soon as the municipality is known. Leave unknown fields empty. Saving the event form copies the shared fields onto the translation.
4. Assign a menu to the Navigation block in the header, and a translation of that menu for English. The language switcher reads the plugin; it is already in the header template.

### Known event facts

These notes are for the person entering events. They are not stored in the theme. Do not fill a year, time, venue, or artist that is not listed here.

| Municipality | Date to enter | Time | Venue | Artist |
| --- | --- | --- | --- | --- |
| Punkalaidun | Day 1, month 11. Year was not provided. Leave the year empty until it is confirmed. | 16:00–18:00 | Konatun kellari | Empath, vahvistettu |
| Nokia | 15.11.2026 | 15:00–18:00 | Koukkujärvi / Koukun maja | Jimi Tenor, vahvistettu |
| Sastamala | 22.11.2026 | 15:00–18:00 | Ritajärvi | Jori Hulkkonen, alustava |
| Hämeenkyrö | 28.11.2026 | empty | empty | Aleksi Myllykoski, vahvistettu |
| Ylöjärvi | 6.12.2026 | empty | empty | Heli Hartikainen, vahvistettu |
| Valkeakoski | 16.1.2027 | 16:00–19:00 | Korkkiksen boulder | MESAK, vahvistettu |
| Pirkkala | all date fields empty | empty | empty | status Ei julkaistu, name empty |
| Lempäälä | year 2027 only | empty | empty | Aho, alustava |
| Pälkäne | February 2027, day empty | empty | empty | status Ei julkaistu, name empty |

Because Punkalaidun has no year in the source notes, it is not selected as the next event until a year is saved. With the years above, the next event on 11 October 2026 is Nokia.

## Where to change something

| Task | Where |
| --- | --- |
| Colors, fonts, spacing, content width | `theme/theme.json` |
| Header or footer | `theme/parts/` |
| Home page structure | `theme/templates/front-page.html` |
| Event page structure | `theme/templates/single-event.html` |
| How a missing fact is phrased | `theme/inc/blocks.php`, then `theme/languages/fi.l10n.php` |
| How dates sort or how “next” is chosen | `theme/inc/event-helpers.php` |
| Editor fields, shared versus translated meta | `theme/inc/event-meta.php` |
| Zoneatlas URL for Finnish or English | `lohkareet_get_zoneatlas_urls()` in `theme/inc/i18n.php` |
| Language switcher | `theme/inc/i18n.php` and `theme/parts/header.html` |
| Map, card, and switcher layout | `theme/assets/css/theme.css` |

## Local development

1. Install WordPress locally. PHP 8.1 or newer and WordPress 6.6 or newer are required. This repository does not include a local environment.
2. Link or copy `/theme` to `wp-content/themes/lohkareet`.
3. Activate **Lohkareet**.
4. Install Polylang on the server, not in this theme. Add the predefined languages Suomi (slug `fi`, locale `fi`, default) and English (slug `en`, locale `en_US`). Leave those locale values unchanged. Create the pages and events, and set a static front page. The theme also runs without this step.
5. Edit files in `/theme` and reload. There is no build step.

```bash
ln -s /absolute/path/to/siirtyma-wp/theme /absolute/path/to/wordpress/wp-content/themes/lohkareet
```

## Deploying to Hostaan

Copy the contents of `/theme` to `wp-content/themes/lohkareet` on the Hostaan server, then activate the theme if it is not already active. Deploy the theme directory only.

Leave these on the server, out of Git:

- WordPress core
- `wp-config.php` and secrets
- `wp-content/uploads`
- the database
- plugins

No deploy script is set up yet. Add one when the Hostaan workflow is decided, and document the command here.

## Development principles

- Use native WordPress and Gutenberg before adding PHP. Event meta exists because a template cannot query or conditionally print structured dates by itself.
- Put global colors, typography, spacing, and layout in `theme.json`. `assets/css/theme.css` is only the map frame and the event stack.
- Do not add React, Tailwind, Bootstrap, jQuery, a plugin, or a build step unless a later task has a clear reason and that reason is written here.
- Do not modify WordPress core.
- Prefix functions, hooks, and handles with `lohkareet_`. Use the text domain `lohkareet`.
- Escape output, sanitize input, and keep the event meta nonce on the editor form.
- Keep the public site responsive. The map is a full-width frame with a bounded height. Do not rely on the iframe's inline styles.
- Keep focus outlines. The header site title is not a heading, so the page title can be the `h1`. Event cards use the next heading level under the section title.
- Bump `Version` in `style.css` when CSS or the editor script should be cache-busted. The current theme version is `0.3.0`.
- Keep theme UI strings in English inside `__()`, with Finnish in `theme/languages/fi.l10n.php`. Polylang is installed in production and is not bundled in the theme. Do not call Polylang functions unless `function_exists()` says they are there.
