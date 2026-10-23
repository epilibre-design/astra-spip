Measurements on merged main commit `7e3dde8b34528658340c68c35aa0ccbb6ea5226f`.

Private fixture copies: `/tmp/astra-main-blocs-20261023/{wp,spip}` from Claude scratchpad `dc097d1c-c7f6-455a-b514-0ce253b369df`, `{wp-blocs,spip-blocs}`. Original fixture files, original tables, and servers not modified.

Private WordPress database tables: `dev_claude_db.wpblocs_main20261023_*` cloned with CREATE TABLE LIKE / INSERT SELECT from original `wpblocs_*`; same MySQL schema, separate tables. No schema-creation grants available. Session sql_mode='' required for historical zero-date defaults. Original SQL data never updated. WP source 7.1.3, Astra 4.11.0. Private site URLs rewritten from port8405 to8465 with wp search-replace --all-tables-with-prefix. DISABLE_WP_CRON=true in private config.

Private SPIP4.4.28 SQLite database: copied config/bases/spip.sqlite; adresse_site=port8466. Private caches cleared. plugins/astra symlink points to frozen `/tmp/astra-main-mesures-20261023` checkout; no source or CSS regeneration. wp2spip source commit e3c262720b518bc7007e5f37779a9d6ca84f17f1 (used by original import, not reimported).

Servers PHP8.4.27, date.timezone=Europe/Paris, ports8465(WP)/8466(SPIP). Browser bundled Chromium1228, node dependencies existing scratchpad/pw/node_modules. Tests executed directly from frozen main checkout. Existing fixture data retained; this checks current main rendering of that import, not a fresh wp2spip import.

Cascade harness compares 67 HTML descendants,21 properties,2 widths(1280/600)=2814 equality checks. Screenshot harness compares15 direct blocks per width1280/390;3 converted blocks per width are checked by properties instead of pixels;cover opacity masked as characterized. Pixelmatch threshold0.1,seuil1%,vertical alignment±2px. Four loss properties checked per width.
