# Imported blocks: fresh measurements on merged main

Commit: `7e3dde8b34528658340c68c35aa0ccbb6ea5226f`. Plugin realpath: `/tmp/astra-main-mesures-20261023`.

Sources: WordPress 7.1.3 + Astra4.11.0; SPIP4.4.28; original imported dataset copied into private sites. No reimport performed. WP private tables use `dev_claude_db.wpblocs_main20261023_*`; SPIP private SQLite. See ENVIRONMENT.md for provenance and limits, reproduce-checks.sh for exact check commands.

| Check | Measurements | Outcome |
|---|---|---|
| comparer_blocs.cjs,1280px |15 paired blocks;12 pixel comparisons |Maximum raw mismatch0 (0.00%);all dimensions within2px;exit0|
| comparer_blocs.cjs,390px |15 paired blocks;12 pixel comparisons |Maximum raw mismatch0 (0.00%);all dimensions within2px;exit0|
| Characterized losses |4 per width;8 properties checked total |All pass;3 blocks per width excluded from pixel comparison;cover dim layer masked|
| comparer_medias.php |2 media SHA-256 equalities |cover document4,media-text document5:pass;exit0|
| cascade_blocs.cjs |67 descendants ×21 properties ×2 widths(1280/600)=2814 equalities |0 differences;exit0|

The four characterized import losses remain separator conversion,missing cover dim layer,code textarea conversion,verse conversion. Screenshots include30 WordPress block captures,30 SPIP block captures,and24 pixel diffs(84 PNGs total). `screenshot-measures.json` gives raw per-pair fractions,dimensions,and alignment;`medias.sha256` contains exact image hashes.
