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
- Google Maps URL and Zoneatlas embed URL
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

The map beside the place text uses `lohkareet_zoneatlas_url` when that event has its own Zoneatlas embed. That map is 600px tall and the width of the viewport. An empty value keeps the compact front-page series map. The address is shared, not switched by language, and the theme adds `cooperativeGesturesEnabled=true` when it is missing. `lohkareet_map_point` says whether the stored coordinates are the event location or only the arrival point. `lohkareet_map_url` is the optional external Google Maps link. It is shared data and it does not fall back to the series map. The embedded map does not load Google.

### Language switcher

`parts/header.html` places one Navigation block and `lohkareet/language-switcher` together. Page URLs stay in that menu. Polylang translates the menu; the theme does not store a second one. The switcher reads Polylang’s language list. The visible letters come from the slug. The link’s `hreflang` comes from the WordPress locale (`fi`, `en-US`). On desktop the current language is a small trigger; the other languages appear underneath it, without a panel. In the mobile menu the languages stay in a row, smaller than the page links. With Polylang inactive, or only one language configured, the switcher prints nothing and does not raise a PHP error. The Site Editor shows a non-linked FI / EN placeholder so the slot stays visible. Without JavaScript the desktop trigger is hidden and the language links stay in the row.

The header is fixed, with no bar, background, or blur. Over the front-page video the type is white and uses `mix-blend-mode: difference`. Over a black subpage hero it is white with normal blending. After a hero it is `#141414`. When the black footer reaches the header, the type returns to white without using the hero state. From `60.01rem` the links sit on the right of the 12-column header grid and the LOHKAREET mark sits on the left. The mark is hidden over the front-page video and while the mobile menu is open. It stays visible on subpages. There is no desktop Home link. Below `60rem` the same menu opens fullscreen in black. The footer is one shared template part, not a second navigation system.

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
│   ├── i18n.php              # Current language, Zoneatlas URLs, language switcher, meta sync
│   ├── event-post-type.php   # CPT registration, permalinks, admin columns
│   ├── event-helpers.php     # Reading, sorting, and formatting event data
│   ├── event-meta.php        # Meta registration, sanitizing, editor form
│   ├── works.php             # Contributors, works, and assignment
│   ├── blocks.php            # Map, next event, event list, event details, page hero
│   ├── hero.php              # Front-page hero block
│   ├── navigation.php        # Header mark, mobile Home link, menu button
│   └── footer.php            # Global footer composition
├── languages/
│   └── fi.l10n.php           # Finnish translations of theme UI strings
├── templates/
│   ├── index.html            # Fallback: posts, other archives, search
│   ├── front-page.html       # Home: hero, page content, map, next event, all events
│   ├── page.html             # About, preparation, and other pages
│   ├── singular.html         # Single blog posts
│   ├── archive-event.html    # /tapahtumat/
│   └── single-event.html     # One event
├── parts/
│   ├── header.html
│   └── footer.html
└── assets/
    ├── css/
    │   ├── theme.css         # Map, event list, and language switcher
    │   ├── layout.css        # 4 / 6 / 12 page grid and editorial placement
    │   ├── hero.css          # Hero frame, type, and overlay
    │   ├── navigation.css    # Fixed header and the mobile menu
    │   └── footer.css        # Black footer poster
    ├── images/
    │   └── siirtyma-Eventimage-profilelogo-white.svg
    ├── js/
    │   ├── blocks-editor.js  # Editor preview only
    │   ├── hero-video.js     # Subtle scroll influence on the hero video
    │   └── navigation.js     # Mobile menu open, close, and focus
    └── video/
        └── hero-anim.mp4
```

Presicav is not in `assets/`. The hero video and the Siirtymä logo are the files above. Do not add a second copy of either.

## Typography

Presicav is the identity typeface. It is served by Adobe Fonts, kit `lgz0wqd`. The public site loads `https://use.typekit.net/lgz0wqd.css` from `lohkareet_enqueue_assets()` in `functions.php`. Do not add a second import in CSS or in a template.

The kit’s CSS family name is `presicav` (lowercase). That is the name in `theme.json`. The stack is `"presicav", "Helvetica Neue", Helvetica, Arial, sans-serif`. No Presicav files are stored in this repository, and the theme does not declare its own `@font-face` for it.

The Adobe Fonts kit must allow the site’s domain, including a local host if you preview there.

## Front page hero

`templates/front-page.html` places `lohkareet/hero` between the header and the page content. The block is registered in `inc/hero.php`. The introduction, the next-event and event-list blocks, the map, and the short note on Siirtymä are the front page’s own content in WordPress, so the copy stays editable. The theme file does not contain that copy.

`.lohkareet-hero` is a normal document section, `90svh` tall, and it is not sticky. The next block follows it in document flow, so a strip of that block is already visible at the bottom of the first screen. There is no extra scroll region and no scrub distance. The video is `theme/assets/video/hero-anim.mp4`, with `object-fit: cover`. The white Siirtymä mark is `theme/assets/images/siirtyma-Eventimage-profilelogo-white.svg`, shown above the text LOHKAREET. Both stay the same in Finnish and English.

On load the video plays when the browser allows it. Page scroll stays ordinary. While the hero is at least 20% visible, `assets/js/hero-video.js` reads the change in `scrollY` on animation frames and queues a small timeline nudge: about `0.0012` seconds per pixel, at most `0.28` seconds waiting, and at most `0.06` seconds applied in one frame. Scrolling down advances the playhead a little. Scrolling up eases it slightly backward. When scrolling stops, that queue drains and normal forward playback continues. Scroll position is not mapped to the video duration. The script is enqueued only when the hero renders on the public site.

`prefers-reduced-motion: reduce` leaves the video on a still frame and does not couple scroll to the timeline. Without JavaScript the video does not autoplay, and the logo and LOHKAREET still show. The video is decorative.

`hero-anim.mp4` is about 8.9 seconds at 24fps, with a keyframe every second. Re-encoding it with more frequent keyframes may make the small seeks smoother. Do not paper over that in JavaScript.

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
| Location description | `lohkareet_location_description` | Translated | No place text, so no Paikka section unless a verified map remains |
| Arrival point | `lohkareet_arrival_point` | Shared | No named arrival point |
| Arrival instructions | `lohkareet_arrival_description` | Translated | No arrival text |
| Map latitude | `lohkareet_map_latitude` | Shared | No verified latitude |
| Map longitude | `lohkareet_map_longitude` | Shared | No verified longitude |
| Mapped point | `lohkareet_map_point` | Shared | `event` or `arrival`; empty hides the map and the Maps button |
| Google Maps URL | `lohkareet_map_url` | Shared | Optional https link; empty builds the button from the coordinates |
| Zoneatlas URL | `lohkareet_zoneatlas_url` | Shared | Optional https embed; empty shows the series map |
| Artist name | `lohkareet_artist_name` | Shared | No public name |
| Artist description | `lohkareet_artist_description` | Translated | No description |
| Artist status | `lohkareet_artist_status` | Shared | `confirmed`, `tentative`, or `unannounced` |
| Artist image | `lohkareet_artist_image` | Shared | No performing-artist image |
| Assigned works | `lohkareet_work_ids` | Shared | No recurring works at this event |

Municipality is separate from the venue. The select list is the nine municipalities of this series, in program order. Change the list with the `lohkareet_municipalities` filter in a small plugin or a child theme. A value outside the list is discarded.

A day cannot be saved without a month. An impossible calendar day is discarded. February 29 is allowed when the year is a leap year, and also when the year is still unknown.

The artist name is shown only when the status is confirmed or tentative. Unannounced keeps a stored name off the public page. The status value is shared; the label is a theme string. The performing artist is not where recurring works are entered.

### Recurring works

A work that appears at more than one event is stored once and assigned to each event.

| Record | Where it is edited | What it holds |
| --- | --- | --- |
| Performing artist | The event | Name, status, description, and an optional image. The image is not the event’s featured image. |
| Contributor | Contributors | Public name (the title), an optional biography, an optional image, and a role. The name stays the same in every language. |
| Work | Works | Work type, an optional public title, the description, and one or more contributors. |
| Assignment | Works at this event, on the event | Which works appear, and their order. |

The description is edited on the work. Assigning that work to another event reuses the same text. Do not paste it into the event. Contributor names are read from the original-language contributor. The work type and description can be translated on the linked Polylang version. There is no separate English copy of the assignment list.

A role is kept for editors. The public event page shows the contributor’s name, the work type, and the description. It does not turn those works into additional performing artists.

### Dates

Precision is derived from which parts are filled in:

| Year | Month | Day | Public text | Machine `datetime` |
| --- | --- | --- | --- | --- |
| yes | yes | yes | Finnish `1.11.2026`, English `1 November 2026` | `2026-11-15` |
| yes | yes | no | localized month and year, for example `helmikuu 2027` or `February 2027` | `2027-02` |
| yes | no | no | `2027` | `2027` |
| no | yes | yes | Finnish `1. marraskuuta`, English `1 November` | none |
| no | no | no | The date will be announced later. | none |

`wp_date()` supplies the month name from the active locale. The theme does not list month names. A complete date uses `j F Y` in the English source and `j.n.Y` in `languages/fi.l10n.php`, so Finnish stays numeric. A month and year without a day stay in the locale’s month form, for example `February 2027`. The stored year, month, and day do not change with the language.

Nothing stores a placeholder day to make sorting easier. Sorting uses the real parts. Inside the comparator only, a missing day sorts after the exact dates of that month, and a year without a month sorts after the dated events of that year. Events with no year sort after every dated event. Fully unknown dates sort last.

The next event is the earliest event that has a year and is not certainly past. A known day is past after that day, or after the end time if the event is today and an end time exists. A month without a day stays current until the month ends. A year without a month stays current until the year ends. An event with no year is never chosen as the next event, because it cannot be placed on the timeline. It still appears in the full list.

### What the templates hide

Single event pages show the date and the venue, and the time when it is known. The programme below that shows the performing artist, then any assigned works. A missing performing artist uses “The artist will be announced later.” After that, Paikka renders only when a location description or a verified map point exists, and Saapuminen renders only when arrival instructions or a verified Google Maps destination exists. The event’s own Zoneatlas map is 600px tall and the width of the viewport. An empty Zoneatlas URL keeps the compact front-page series map. It does not load Google. The Maps link is a separate https URL. A point marked `arrival` is labeled as an arrival point, not as the event location. Listings do not show works.

Event listings show a complete date and its time on one line, the venue when it is known, and a confirmed artist. A year or a month without a day stays stored and is left off the listing. Tentative and unannounced artists are left off the listing. The single event page still shows the fuller facts, including a partial date and a tentative artist.

The series map on the home page sits between the next event and the full event list. It is separate from a per-event map. Its URL comes from `lohkareet_get_zoneatlas_urls()`. Only https URLs are accepted.

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
| Punkalaidun | 1.11.2026 | 16:00–18:00 | Konatun kellari | Empath, vahvistettu |
| Nokia | 15.11.2026 | 15:00–18:00 | Koukkujärvi; the event is a boulder north of Koukun maja, not the hut | Jimi Tenor, vahvistettu |
| Sastamala | 22.11.2026 | 15:00–18:00 | Ritajärvi | Jori Hulkkonen, vahvistettu |
| Hämeenkyrö | 28.11.2026 | empty | empty | Aleksi Myllykoski, vahvistettu |
| Ylöjärvi | 6.12.2026 | empty | empty | Heli Hartikainen, vahvistettu |
| Valkeakoski | 16.1.2027 | 16:00–19:00 | Korkkiksen boulder | MESAK, vahvistettu |
| Pirkkala | all date fields empty | empty | empty | status Ei julkaistu, name empty |
| Lempäälä | year 2027 only | empty | empty | Aho, alustava |
| Pälkäne | February 2027, day empty | empty | empty | status Ei julkaistu, name empty |

QODOVV and Heikki and Melanie Lindgren are one contributor and one work each. Both works are assigned to Nokia, Sastamala, Ylöjärvi, Valkeakoski, Pirkkala, and Lempäälä. They are not assigned to Punkalaidun, Hämeenkyrö, or Pälkäne.

With these dates, the next complete event on 12 October 2026 is Punkalaidun. The theme does not hardcode that result. It chooses the earliest event that has a year and is not past.

## Where to change something

| Task | Where |
| --- | --- |
| Colors, fonts, spacing, content width | `theme/theme.json`. Presicav itself is Adobe Fonts kit `lgz0wqd`, enqueued in `functions.php` |
| Hero video or Siirtymä logo | `theme/assets/video/hero-anim.mp4` and `theme/assets/images/siirtyma-Eventimage-profilelogo-white.svg` |
| Hero scroll and overlay | `theme/assets/css/hero.css` and `theme/assets/js/hero-video.js` |
| Header or footer | `theme/parts/`, `theme/inc/navigation.php`, `theme/inc/footer.php` |
| Home page structure | `theme/templates/front-page.html` |
| Event page structure | `theme/templates/single-event.html` |
| How a missing fact is phrased | `theme/inc/blocks.php`, then `theme/languages/fi.l10n.php` |
| How dates sort or how “next” is chosen | `theme/inc/event-helpers.php` |
| Editor fields, shared versus translated meta | `theme/inc/event-meta.php` |
| Zoneatlas URL for Finnish or English | `lohkareet_get_zoneatlas_urls()` in `theme/inc/i18n.php` |
| Header and mobile menu | `theme/parts/header.html`, `theme/assets/css/navigation.css`, `theme/assets/js/navigation.js` |
| Language switcher | `theme/inc/i18n.php` and `theme/parts/header.html` |
| Map, card, and switcher layout | `theme/assets/css/theme.css` |

## Local development

The local site is a persistent WordPress Playground installation. Its database and uploads stay in this project. They are not kept in an anonymous temporary directory, and they are not committed.

Do not use `npx @wp-playground/cli server` by itself for editorial work. That command creates `node-playground-cli-site-<pid>-*` under the operating-system temp directory and deletes it when the process ends.

Node is required. There is no PHP or Docker install on the machine. The theme source remains `theme/` and is mounted into the local site on every start.

```bash
npm run wp:start
npm run wp:stop
npm run wp:restart
npm run wp:status
npm run wp:bootstrap
npm run wp:backup
npm run wp:restore -- .local/backups/<timestamp>
```

`wp:start` always reopens `.local/wordpress`. The first start downloads WordPress into that directory. Later starts reuse it. The site listens on http://127.0.0.1:9400/ and logs in as `admin` / `password`.

Run `wp:bootstrap` after the first start, with the server stopped or running. It writes the known pages, nine events, Nokia works, and the primary navigation from `dev/site-content.php`. Run it again only when those known posts should be reset to the fixture. It does not edit posts that are not in the fixture.

| What | Where |
| --- | --- |
| WordPress core and `wp-config.php` | `.local/wordpress/` |
| Database (SQLite) | `.local/wordpress/wp-content/database/` |
| Uploads | `.local/wordpress/wp-content/uploads/` |
| Server pid and log | `.local/run/` |
| Local backups | `.local/backups/<timestamp>/` |
| Theme source | `theme/` (git) |
| Content fixture and importer | `dev/site-content.php`, `dev/bootstrap.php` (git) |

In git: the theme, the fixture, the blueprint, and `scripts/local-wordpress.sh`.

Not in git: `.local/`, including the database, uploads, WordPress core, logs, and backups.

A backup copies the database, uploads, and `wp-config.php`. Restore only while the server is stopped. Then start the site again.

## Migrating to Hostaan later

Do not deploy until the server is available. The local site is the working copy until then.

1. Export the database with WordPress’s export tool, or copy `.local/wordpress/wp-content/database` and convert SQLite to MySQL with WP-CLI or the SQLite plugin’s export. Prefer a WXR export plus the uploads directory if the host is MySQL.
2. Copy `.local/wordpress/wp-content/uploads/` to the server’s `wp-content/uploads/`.
3. Copy `theme/` to `wp-content/themes/lohkareet` and activate it. Do not copy `.local/wordpress` core over the host.
4. Replace `http://127.0.0.1:9400` in the database, including serialized options, with the production URL. Use WP-CLI `search-replace` so serialized lengths stay valid.
5. Save permalinks once, or run `wp rewrite flush`.
6. Install plugins on the server. Polylang is not part of this local site yet. Add Suomi (`fi` / `fi`) and English (`en` / `en_US`) there, then translate pages, events, and the navigation post. Do not invent those translations in the theme.
7. After deployment, check the front page, `/tapahtumat/`, one event, Lohkareista, Varustautuminen, the menu, and a media file.

Leave WordPress core, `wp-config.php`, uploads, the database, and plugins on the server, out of Git.

## Development principles

- Use native WordPress and Gutenberg before adding PHP. Event meta exists because a template cannot query or conditionally print structured dates by itself.
- Put global colors, typography, spacing, and layout in `theme.json`. `assets/css/theme.css` is only the map frame and the event stack.
- Do not add React, Tailwind, Bootstrap, jQuery, a plugin, or a build step unless a later task has a clear reason and that reason is written here.
- Do not modify WordPress core.
- Prefix functions, hooks, and handles with `lohkareet_`. Use the text domain `lohkareet`.
- Escape output, sanitize input, and keep the event meta nonce on the editor form.
- Keep the public site responsive. The map is a full-width frame with a bounded height. Do not rely on the iframe's inline styles.
- Keep focus outlines. The page title stays the `h1`. Event cards use the next heading level under the section title.
- Bump `Version` in `style.css` when CSS or the editor script should be cache-busted. The current theme version is `0.4.50`.
- Presicav stays on Adobe Fonts. Do not commit font files or add a local `@font-face` for it.
- Keep theme UI strings in English inside `__()`, with Finnish in `theme/languages/fi.l10n.php`. Polylang is installed in production and is not bundled in the theme. Do not call Polylang functions unless `function_exists()` says they are there.
