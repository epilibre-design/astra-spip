# Site Garden Maintenance pour SPIP — plan d'exécution (lots 1 et 2)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** un dépôt de site `garden-spip` dont `bin/install.sh` installe depuis zéro, et relance sans dégâts, un SPIP 4.4 configuré comme le modèle Garden Maintenance avec un contenu de substitution.

**Architecture:** organisation de `/src/site-tuto` : la racine du dépôt est la racine SPIP (cœur téléchargé, non versionné), un plugin de site `garden` porte la configuration et le contenu, une commande `garden:seed` les applique. Le moteur du seed sépare une décision pure (testée par PHPUnit) des écritures SPIP ; chaque valeur fournie est suivie par deux empreintes (valeur du seed, valeur relue après écriture), et chaque page porte une clé stable (champ `page` = `garden_<clé>` (le plugin Pages n'accepte que [a-z0-9_])) qui évite les doublons après une interruption.

**Tech Stack:** Bash, spip-cli avec la MR 91, SPIP 4.4 (SQLite), PHP 8.1+ pour l'outillage (le plugin `garden` vise PHP 7.4+ comme Astra SPIP), PHPUnit 11.5, plugins Pages, a2a, tiptap_spip, Astra SPIP.

Spec : `docs/superpowers/specs/2026-10-22-site-garden-installation-design.md` (dépôt astra-spip).

**Périmètre de ce plan :** lot 1 (socle) et lot 2 (accueil statique, pages pleine largeur sans titre), plus le mécanisme de relance et de migration valable pour tous les lots. Les lots 3 (habillage) et 4 (contenu riche, formulaire de contact, extensions d'édition) dépendent de fonctions d'Astra SPIP non encore spécifiées et auront leur propre plan.

## Global Constraints

- Choix validés : **dépôt séparé**, **contenu de substitution** (aucun texte ni aucune photo du modèle), **tiptap_spip installé dès le lot 1** (jamais modifié), **formulaire de contact CVT propre au site** (lot 4).
- SPIP **4.4** seul ; installation **SQLite** ; dépôt SVP `https://files.spip.net/spip-zone/archives.xml` ; spip-cli avec la **MR 91** (`refs/merge-requests/91/merge`).
- Une relance au même lot ne modifie rien ; une valeur personnalisée n'est jamais réécrite hors `--reinitialiser` ; un objet supprimé n'est jamais recréé hors `--reinitialiser`.
- Empreintes calculées sur la **valeur réellement enregistrée** (relue après écriture).
- Chaque lot déclare la **version minimale d'Astra SPIP** ; vérifiée avant toute écriture, arrêt en code 1 sinon.
- Licences : dépôt sous **GPL-3.0-or-later** ; pas de contenu de modèle versionné.
- Messages de commit sans trailer.
- Rien n'est poussé sur un dépôt distant ni étiqueté sans l'accord de Gilles (création du dépôt GitHub, étiquette `v1.0.0` d'Astra SPIP).

## Fichiers (dépôt `/src/garden-spip`)

| Fichier | Rôle |
|---|---|
| `bin/install.sh` | installation et relance |
| `bin/tester-installation.sh` | scénarios de bout en bout (dossier vide, relance, personnalisations, lots, interruption, réinitialisation, Astra trop ancien) |
| `plugins/garden/paquet.xml` | plugin du site : `necessite` astra (version du dernier lot), pages ; `utilise` a2a, tiptap_spip |
| `plugins/garden/inc/garden_decision.php` | fonctions pures : empreintes, décision par valeur |
| `plugins/garden/inc/garden_seed.php` | moteur : lecture des lots, résolution des références, écritures, mémorisation, signalements |
| `plugins/garden/spip-cli/GardenSeed.php` | commande `garden:seed` |
| `plugins/garden/seed/lots/1.php`, `2.php` | état complet du site à chaque lot |
| `plugins/garden/tests/unit/DecisionTest.php`, `phpunit.xml`, `composer.json` | tests unitaires du plugin |
| `plugins/garden/tests/lots/1.php`, `2.php`, `3.php` | lots de test des scénarios de relance |
| `README.md`, `LICENSE`, `.gitignore` | dépôt |

---

### Task 1 : dépôt et installation sans contenu

**Files:**
- Create: `/src/garden-spip/.gitignore`, `LICENSE`, `README.md`, `bin/install.sh`, `bin/tester-installation.sh`, `plugins/garden/paquet.xml`

**Interfaces:**
- Produces : `bin/install.sh` lisant `SPIP_CLI`, `ADRESSE_SITE` (défaut `http://localhost:8770/`), `ADMIN_LOGIN`, `ADMIN_PASS`, `ADMIN_EMAIL`, `ASTRA_SPIP` (dépôt local, prioritaire) ou `ASTRA_SPIP_REF` (étiquette à cloner), `GARDEN_SEED_OPTIONS` (options passées à `garden:seed`, tâche 4) ; `bin/tester-installation.sh` avec la fonction `verifier <message> <commande…>`.

- [ ] **Step 1 : créer le dépôt**

```bash
mkdir -p /src/garden-spip/bin /src/garden-spip/plugins/garden && cd /src/garden-spip && git init -q
cp /src/astra-spip/LICENSE LICENSE
```

`.gitignore` (repris de site-tuto, plus Astra SPIP installé par le script) :

```gitignore
# Cœur SPIP et plugins-dist : téléchargés par bin/install.sh
/CHANGELOG.md
/SECURITY.md
/composer.json
/composer.lock
/htaccess.txt
/.htaccess
/index.php
/phpcs.xml.dist
/plugins-dist.json
/spip.php
/spip.png
/spip.svg
/ecrire/
/prive/
/plugins-dist/
/squelettes-dist/
/vendor/

# Plugins téléchargés par SVP, Astra SPIP (lien ou clone)
/plugins/auto/
/plugins/astra

# Données du site : recréées par l'installation et le seed
/config/*
/IMG/
/local/
/tmp/

# Outils locaux
/.outils/
/plugins/garden/vendor/
```

Le zip de SPIP contient aussi `LICENSE` et `README.md` : `install.sh` les exclut de la décompression (step 3).

- [ ] **Step 2 : plugin minimal**

`plugins/garden/paquet.xml` :

```xml
<paquet
	prefix="garden"
	categorie="squelette"
	version="0.1.0"
	etat="dev"
	compatibilite="[4.4.0;4.4.*]"
>
	<nom>Garden Maintenance</nom>
	<!-- Site de démonstration : modèle Astra « Garden Maintenance » reproduit avec Astra SPIP, contenu de substitution -->
	<auteur>Gilles Vincent</auteur>
	<licence lien="https://spdx.org/licenses/GPL-3.0-or-later.html">GPL-3.0-or-later</licence>

	<necessite nom="astra" compatibilite="[1.0.0;]" />
	<necessite nom="pages" compatibilite="[2.0.0;]" />
	<utilise nom="a2a" compatibilite="[4.2.0;]" />
	<utilise nom="tiptap_spip" />
</paquet>
```

- [ ] **Step 3 : `bin/install.sh`**

```bash
#!/usr/bin/env bash
#
# Installe le site Garden Maintenance depuis zéro, ou le met à jour :
#   1. spip-cli (avec la MR 91 qui répare plugins:svp:telecharger)
#   2. cœur SPIP 4.4
#   3. installation SQLite et compte administrateur
#   4. plugins depuis SPIP Zone, Astra SPIP, plugin du site
#   5. configuration et contenu (garden:seed)
#
# Chaque étape est sautée si elle a déjà été faite : le script peut être relancé. Le seed
# ne réécrit jamais un réglage ou un contenu personnalisé (voir README).
#
# Variables d'environnement :
#   SPIP_CLI        exécutable spip-cli à utiliser (sinon clone dans .outils/)
#   ADRESSE_SITE    URL publique du site (défaut : http://localhost:8770/)
#   ADMIN_LOGIN, ADMIN_PASS, ADMIN_EMAIL : compte administrateur
#                   (sans ADMIN_PASS, un mot de passe aléatoire est généré et affiché)
#   ASTRA_SPIP      dépôt local d'Astra SPIP (lien symbolique) ; sinon clone de
#   ASTRA_SPIP_REF  l'étiquette indiquée (défaut : v1.0.0) de github.com/epilibre-design/astra-spip
#   GARDEN_SEED_OPTIONS  options supplémentaires de garden:seed (ex. --reinitialiser=config -y)

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

SPIP_ZIP_URL="https://files.spip.net/spip/stable/spip-4.4.zip"
SPIP_CLI_GIT="https://git.spip.net/spip-contrib-outils/spip-cli.git"
SPIP_CLI_REF="refs/merge-requests/91/merge"
DEPOT_SVP="https://files.spip.net/spip-zone/archives.xml"
ASTRA_GIT="https://github.com/epilibre-design/astra-spip.git"
ASTRA_SPIP_REF="${ASTRA_SPIP_REF:-v1.0.0}"
PLUGINS=(pages a2a tiptap_spip)

ADRESSE_SITE="${ADRESSE_SITE:-http://localhost:8770/}"
ADMIN_LOGIN="${ADMIN_LOGIN:-admin}"
ADMIN_PASS="${ADMIN_PASS:-$(php -r 'echo bin2hex(random_bytes(8));')}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@localhost}"

etape() { printf '\n\033[1;35m==> %s\033[0m\n' "$*"; }
echec() { printf '\033[1;31m%s\033[0m\n' "$*" >&2; exit 1; }

# Répertoires où PHP (www-data) et la ligne de commande écrivent tous les deux
ouvrir_droits() {
	mkdir -p config IMG local tmp plugins/auto
	find config IMG local tmp plugins/auto -user "$(id -u)" -exec chmod a+rwX {} +
}

# Version d'un paquet.xml
version_paquet() { php -r '$x = simplexml_load_file($argv[1]); echo (string) $x["version"];' "$1"; }
# Borne basse de la compatibilité d'un plugin nécessité par un paquet.xml
version_requise() { php -r '$x = simplexml_load_file($argv[1]); foreach ($x->necessite as $n) { if ((string) $n["nom"] === $argv[2] && preg_match("/^\[([0-9.]+);/", (string) $n["compatibilite"], $m)) { echo $m[1]; } }' "$1" "$2"; }

etape "spip-cli"
if [[ -z "${SPIP_CLI:-}" ]]; then
	SPIP_CLI_DIR="$ROOT/.outils/spip-cli"
	if [[ ! -d "$SPIP_CLI_DIR" ]]; then
		git clone -q "$SPIP_CLI_GIT" "$SPIP_CLI_DIR"
		git -C "$SPIP_CLI_DIR" fetch -q origin "$SPIP_CLI_REF"
		git -C "$SPIP_CLI_DIR" checkout -q FETCH_HEAD
		composer install -q --no-dev -d "$SPIP_CLI_DIR"
	fi
	SPIP_CLI="php $SPIP_CLI_DIR/bin/spip"
fi
spip() { $SPIP_CLI "$@"; }
spip --version

etape "Cœur SPIP 4.4"
if [[ ! -f ecrire/inc_version.php ]]; then
	# « spip dl » refuse un répertoire non vide : on décompresse le zip officiel
	# par-dessus le dépôt, sans écraser ses fichiers.
	zip="$(mktemp --suffix=.zip)"
	curl -fsSL "$SPIP_ZIP_URL" -o "$zip"
	unzip -q -o "$zip" -x .gitignore .gitattributes LICENSE README.md
	rm -f "$zip"
fi
ouvrir_droits
grep -m1 'spip_version_branche =' ecrire/inc_version.php

etape "Installation (SQLite, compte $ADMIN_LOGIN)"
compte_cree=0
if [[ ! -f config/connect.php ]]; then
	compte_cree=1
	spip core:installer \
		--db-server=sqlite3 \
		--db-database=spip \
		--admin-nom="$ADMIN_LOGIN" \
		--admin-login="$ADMIN_LOGIN" \
		--admin-pass="$ADMIN_PASS" \
		--admin-email="$ADMIN_EMAIL" \
		--adresse-site="$ADRESSE_SITE"
fi

etape "Astra SPIP"
if [[ -n "${ASTRA_SPIP:-}" ]]; then
	[[ -L plugins/astra || ! -e plugins/astra ]] || echec "plugins/astra existe et n'est pas un lien : le retirer pour utiliser ASTRA_SPIP"
	ln -sfn "$ASTRA_SPIP" plugins/astra
elif [[ ! -d plugins/astra ]]; then
	git clone -q --depth 1 --branch "$ASTRA_SPIP_REF" "$ASTRA_GIT" plugins/astra
fi
astra_version=$(version_paquet plugins/astra/paquet.xml)
astra_requise=$(version_requise plugins/garden/paquet.xml astra)
php -r 'exit(version_compare($argv[1], $argv[2], ">=") ? 0 : 1);' "$astra_version" "$astra_requise" \
	|| echec "Astra SPIP $astra_version trouvé, $astra_requise requis par plugins/garden/paquet.xml"
echo "Astra SPIP $astra_version (requis : $astra_requise)"

etape "Plugins"
if [[ "$(spip php:eval 'echo sql_countsel("spip_depots");')" == "0" ]]; then
	spip plugins:svp:depoter "$DEPOT_SVP"
fi
for prefixe in "${PLUGINS[@]}"; do
	if ! ls -d plugins/auto/"$prefixe"* >/dev/null 2>&1; then
		spip plugins:svp:telecharger "$prefixe" -y
	fi
done
mapfile -t telecharges < <(ls plugins/auto)
spip plugins:activer "${telecharges[@]}" astra garden -y
spip plugins:maj:bdd
# Un plugin installé par SVP dans le même processus que son téléchargement
# n'a pas encore ses déclarations de tables chargées : on complète les colonnes manquantes.
spip php:eval 'include_spip("base/create"); maj_tables(array_keys(lister_tables_principales()));'
# plugins:activer peut sortir en code 0 sans rien activer : on vérifie chaque plugin.
for prefixe in "${PLUGINS[@]}" astra garden; do
	[[ "$(spip php:eval "include_spip('inc/plugin'); echo test_plugin_actif('$prefixe') ? 'actif' : 'inactif';")" == "actif" ]] \
		|| echec "Le plugin $prefixe n'est pas actif"
done
ouvrir_droits

etape "Terminé : $ADRESSE_SITE"
if [[ $compte_cree == 1 ]]; then
	echo "Compte administrateur : $ADMIN_LOGIN / $ADMIN_PASS"
fi
```

`chmod +x bin/install.sh`.

- [ ] **Step 4 : scénario « dossier vide » (test d'abord)**

`bin/tester-installation.sh` :

```bash
#!/usr/bin/env bash
# Scénarios de bout en bout sur une copie fraîche du dépôt (dossier vide, relances, lots…).
# Usage : SPIP_CLI=… ASTRA_SPIP=<dépôt Astra SPIP> bin/tester-installation.sh [scénario…]
set -euo pipefail
DEPOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
: "${SPIP_CLI:?}" "${ASTRA_SPIP:?}"
export ADMIN_PASS=adminadmin
echecs=0

verifier() { # <message> <commande…>
	local message=$1; shift
	if "$@" >/dev/null 2>&1; then echo "ok    $message"; else echo "ÉCHEC $message"; echecs=$((echecs + 1)); fi
}
spip_eval() { (cd "$SITE" && $SPIP_CLI php:eval "$1"); }
nouveau_site() {
	SITE=$(mktemp -d)/garden
	git clone -q "$DEPOT" "$SITE"
	# Copie de travail non commitée (développement) : reprise des fichiers modifiés.
	(cd "$DEPOT" && git ls-files -m -o --exclude-standard -z | xargs -0 -I{} cp --parents {} "$SITE/")
}

scenario_dossier_vide() {
	nouveau_site
	verifier "installation depuis un dossier vide" "$SITE/bin/install.sh"
	verifier "plugins actifs" test "$(spip_eval 'include_spip("inc/plugin"); foreach (["pages","a2a","tiptap_spip","astra","garden"] as $p) { if (!test_plugin_actif($p)) { echo $p; } }')" = ""
	verifier "relance sans erreur" "$SITE/bin/install.sh"
}

scenarios=("$@")
[ ${#scenarios[@]} -gt 0 ] || scenarios=(dossier_vide)
for s in "${scenarios[@]}"; do
	echo "== $s"
	"scenario_$s"
done
[ $echecs -eq 0 ] || { echo "$echecs échec(s)"; exit 1; }
echo "Tous les scénarios passent."
```

`chmod +x bin/tester-installation.sh`.

- [ ] **Step 5 : lancer**

Le clone par défaut d'Astra SPIP exige l'étiquette `v1.0.0`, qui n'existe pas encore : les tests utilisent `ASTRA_SPIP=/src/astra-spip`. **Demander à Gilles** s'il faut créer et pousser l'étiquette `v1.0.0` sur astra-spip (commit du builder, `d04a70f` ou suivant) ; ne pas le faire sans accord.

Run: `cd /src/garden-spip && git add -A && git commit -qm "Créer le dépôt du site Garden Maintenance" && SPIP_CLI="php /src/spip-cli-custom/bin/spip" ASTRA_SPIP=/src/astra-spip bin/tester-installation.sh dossier_vide`
Expected: trois lignes `ok`. Si `plugins:svp:telecharger` échoue pour un préfixe, noter le message dans le README et, comme dans astra-spip, télécharger l'archive depuis `files.spip.org` en dernier recours.

- [ ] **Step 6 : commit**

```bash
git add -A
git commit -m "Installer SPIP, les plugins et Astra SPIP depuis zéro"
```

(Le commit du step 5 sert de base au clone du test ; s'il n'y a rien de nouveau, ce step est sans objet.)

---

### Task 2 : décision et empreintes (fonctions pures)

**Files:**
- Create: `plugins/garden/inc/garden_decision.php`, `plugins/garden/tests/unit/DecisionTest.php`, `plugins/garden/composer.json`, `plugins/garden/phpunit.xml`

**Interfaces:**
- Produces :
  - `garden_canonique($valeur)` : tableaux triés par clé, récursivement ;
  - `garden_empreinte($valeur): string` : SHA-256 de `json_encode(garden_canonique($valeur))` ;
  - `garden_decider(array $etat, bool $reinitialiser): string`, avec `$etat` = `['enregistre' => bool, 'existe' => bool, 'source' => ?string, 'enregistree' => ?string, 'actuelle' => ?string, 'nouvelle' => string]` et un résultat parmi `creer`, `remplacer`, `adopter_intacte`, `adopter_personnalisee`, `signaler`, `supprimee`, `rien`.

Sens des champs : `source` = empreinte de la dernière valeur fournie par le seed ; `enregistree` = empreinte de cette valeur relue juste après écriture (`null` si la valeur n'a jamais été écrite par le seed) ; `actuelle` = empreinte de la valeur relue maintenant (`null` si absente) ; `nouvelle` = empreinte de la valeur du lot courant ; `enregistre` = le seed a déjà mémorisé cette valeur (ou l'objet qui la porte) ; `existe` = la valeur (ou l'objet) est présente dans le site.

- [ ] **Step 1 : tests (échouent)**

`plugins/garden/tests/unit/DecisionTest.php` :

```php
<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/garden_decision.php';

final class DecisionTest extends TestCase {

	private function etat(array $e): array {
		return $e + ['enregistre' => true, 'existe' => true, 'source' => 'A', 'enregistree' => 'a', 'actuelle' => 'a', 'nouvelle' => 'A'];
	}

	public function testEmpreinteIndependanteDeLOrdreDesCles(): void {
		$this->assertSame(garden_empreinte(['b' => 1, 'a' => [2, 3]]), garden_empreinte(['a' => [2, 3], 'b' => 1]));
		$this->assertNotSame(garden_empreinte([2, 3]), garden_empreinte([3, 2]));
		$this->assertSame(64, strlen(garden_empreinte('x')));
	}

	public function testCreationInitiale(): void {
		$this->assertSame('creer', garden_decider($this->etat(['enregistre' => false, 'existe' => false, 'source' => null, 'enregistree' => null, 'actuelle' => null]), false));
	}

	public function testRelanceAuMemeLot(): void {
		$this->assertSame('rien', garden_decider($this->etat([]), false));
	}

	public function testValeurIntacteMiseAJour(): void {
		$this->assertSame('remplacer', garden_decider($this->etat(['nouvelle' => 'B']), false));
	}

	public function testValeurPersonnaliseeConservee(): void {
		$this->assertSame('signaler', garden_decider($this->etat(['actuelle' => 'perso', 'nouvelle' => 'B']), false));
		$this->assertSame('rien', garden_decider($this->etat(['actuelle' => 'perso']), false), 'Pas de signalement si le seed ne change pas');
	}

	public function testPersonnalisationEgaleALaNouvelleValeur(): void {
		$this->assertSame('adopter_intacte', garden_decider($this->etat(['actuelle' => 'B', 'nouvelle' => 'B']), false));
	}

	public function testObjetSupprime(): void {
		$this->assertSame('supprimee', garden_decider($this->etat(['existe' => false, 'actuelle' => null, 'nouvelle' => 'B']), false));
		$this->assertSame('rien', garden_decider($this->etat(['existe' => false, 'actuelle' => null]), false));
	}

	public function testReprisApresInterruption(): void {
		// Objet créé mais non mémorisé : retrouvé par sa clé stable.
		$this->assertSame('adopter_intacte', garden_decider($this->etat(['enregistre' => false, 'source' => null, 'enregistree' => null, 'actuelle' => 'A', 'nouvelle' => 'A']), false));
		$this->assertSame('adopter_personnalisee', garden_decider($this->etat(['enregistre' => false, 'source' => null, 'enregistree' => null, 'actuelle' => 'perso', 'nouvelle' => 'A']), false));
	}

	public function testReinitialisation(): void {
		$this->assertSame('remplacer', garden_decider($this->etat(['actuelle' => 'perso']), true));
		$this->assertSame('creer', garden_decider($this->etat(['existe' => false, 'actuelle' => null]), true));
	}

	public function testAdoptionPersonnaliseeJamaisIntacte(): void {
		// Après une adoption personnalisée, enregistree est null : la valeur n'est jamais considérée intacte.
		$this->assertSame('signaler', garden_decider($this->etat(['enregistree' => null, 'actuelle' => 'perso', 'nouvelle' => 'B']), false));
	}
}
```

`plugins/garden/composer.json` :

```json
{
	"name": "epilibre-design/garden-spip",
	"description": "Plugin du site Garden Maintenance (outillage de test)",
	"license": "GPL-3.0-or-later",
	"require-dev": {
		"phpunit/phpunit": "^11.5"
	},
	"config": {
		"platform": {"php": "8.2"}
	},
	"scripts": {
		"tests-unit": "phpunit --colors --testsuite unit"
	}
}
```

`plugins/garden/phpunit.xml` :

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd" colors="true">
	<testsuites>
		<testsuite name="unit">
			<directory suffix="Test.php">tests/unit</directory>
		</testsuite>
	</testsuites>
</phpunit>
```

Run: `cd plugins/garden && composer install -q && composer tests-unit`
Expected: FAIL (`inc/garden_decision.php` absent).

- [ ] **Step 2 : implémentation**

`plugins/garden/inc/garden_decision.php` :

```php
<?php
/**
 * Garden — décision du seed pour une valeur (réglage ou champ d'objet). Fonctions pures.
 *
 * Une valeur est intacte si sa valeur actuelle est celle que le seed a écrite (empreinte relue
 * après écriture) ; sinon elle est personnalisée et n'est jamais réécrite hors réinitialisation.
 *
 * @license GPL-3.0-or-later
 */

/** Tableaux triés par clé, récursivement : même empreinte quel que soit l'ordre des clés. */
function garden_canonique($valeur) {
	if (!is_array($valeur)) {
		return $valeur;
	}
	$liste = array_keys($valeur) === range(0, count($valeur) - 1);
	if (!$liste) {
		ksort($valeur);
	}
	return array_map('garden_canonique', $valeur);
}

function garden_empreinte($valeur): string {
	return hash('sha256', json_encode(garden_canonique($valeur), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

/**
 * Action du seed pour une valeur.
 *
 * @param array $etat enregistre, existe, source, enregistree, actuelle, nouvelle (voir le plan)
 * @return string creer | remplacer | adopter_intacte | adopter_personnalisee | signaler | supprimee | rien
 */
function garden_decider(array $etat, bool $reinitialiser): string {
	if ($reinitialiser) {
		return $etat['existe'] ? 'remplacer' : 'creer';
	}
	if (!$etat['enregistre']) {
		if (!$etat['existe']) {
			return 'creer';
		}
		return $etat['actuelle'] === $etat['nouvelle'] ? 'adopter_intacte' : 'adopter_personnalisee';
	}
	if ($etat['nouvelle'] === $etat['source']) {
		return 'rien';
	}
	if (!$etat['existe']) {
		return 'supprimee';
	}
	if ($etat['enregistree'] !== null && $etat['actuelle'] === $etat['enregistree']) {
		return 'remplacer';
	}
	return $etat['actuelle'] === $etat['nouvelle'] ? 'adopter_intacte' : 'signaler';
}
```

`testReprisApresInterruption` compare `actuelle` (valeur relue) à `nouvelle` (valeur du seed) : ces deux empreintes portent sur des valeurs comparables tant que SPIP ne transforme pas la valeur à l'écriture. Pour les champs texte écrits par `sql_updateq` et les réglages écrits par `ecrire_config`, c'est le cas ; le moteur (tâche 3) le vérifie en relisant chaque valeur écrite et signale tout écart (`valeur transformée à l'écriture`).

- [ ] **Step 3 : tests**

Run: `composer tests-unit && php7.4 -l inc/garden_decision.php`
Expected: 10 tests OK ; pas d'erreur de syntaxe.

- [ ] **Step 4 : commit**

```bash
git add plugins/garden/inc/garden_decision.php plugins/garden/tests plugins/garden/composer.json plugins/garden/phpunit.xml
git commit -m "Décider de chaque valeur du seed par empreintes"
```

---

### Task 3 : moteur du seed et commande `garden:seed`

**Files:**
- Create: `plugins/garden/inc/garden_seed.php`, `plugins/garden/spip-cli/GardenSeed.php`, `plugins/garden/tests/lots/1.php`, `2.php`, `3.php`
- Modify: `bin/tester-installation.sh` (scénarios), `bin/install.sh` (appel du seed)

**Interfaces:**
- Consumes : `garden_empreinte()`, `garden_decider()`.
- Produces :
  - format d'un lot : fichier PHP renvoyant `['lot' => int, 'astra_min' => string, 'pages' => [clé => ['titre' => string, 'texte' => string]], 'config' => [clé => valeur]]`. Clés de configuration : `astra/…` (écrite par `ecrire_config`) ou `meta:<nom>` (écrite par `ecrire_meta`). Références résolues avant écriture, dans les clés et les valeurs (chaînes, récursivement) : `@id:<page>` → identifiant, `@article:<page>` → `article<id>`, `@url:<page>` → URL publique de la page ;
  - `garden_seed(array $options, callable $journal): int` (code de sortie) ; options : `lot` (int|null = dernier), `dossier` (dossier des lots), `reinitialiser` (null | `tout` | `config` | `pages` | clé exacte), `arret_apres` (int|null, test d'interruption : arrêt brutal après la création de N pages) ;
  - méta `garden` : `seed/version`, `seed/objets/page.<clé>` (identifiant), `seed/empreintes/<valeur>` = `['source' => …, 'enregistree' => …]` avec `<valeur>` = `config.<clé, / remplacés par .>` ou `page.<clé>.<champ>`, `seed/en_attente/<valeur>` = `['lot' => int, 'raison' => 'personnalisee' | 'supprimee']` ;
  - commande `garden:seed [--lot=N] [--dossier=…] [--reinitialiser[=partie]] [-y|--yes]`.

- [ ] **Step 1 : lots de test**

`plugins/garden/tests/lots/1.php` :

```php
<?php
return [
	'lot' => 1,
	'astra_min' => '1.0.0',
	'pages' => [
		'a' => ['titre' => '10. Page A', 'texte' => 'Texte A, lot 1.'],
		'b' => ['titre' => '20. Page B', 'texte' => 'Texte B, lot 1.'],
		'c' => ['titre' => '30. Page C', 'texte' => 'Texte C, lot 1.'],
		'd' => ['titre' => '40. Page D', 'texte' => 'Texte D, lot 1.'],
	],
	'config' => [
		'astra/couleur_0' => '#40bf4f',
		'astra/couleur_1' => '#0d3c00',
		'astra/hfb' => ['elements' => ['entete' => ['button-1' => ['libelle' => 'Contact', 'lien' => '@url:d']]]],
	],
];
```

`plugins/garden/tests/lots/2.php` : le lot 1 avec `'lot' => 2`, les textes de `a`, `b`, `c` remplacés par `Texte X, lot 2.`, une page `e` (`'50. Page E'`, `'Texte E, lot 2.'`), `astra/couleur_0` à `#2f9e3d` et `astra/couleur_1` inchangé.

`plugins/garden/tests/lots/3.php` : le lot 2 avec `'lot' => 3` et `'astra_min' => '99.0.0'`.

- [ ] **Step 2 : scénarios (échouent)**

Ajouter à `bin/tester-installation.sh`, avant `scenarios=("$@")` :

```bash
seed() { (cd "$SITE" && $SPIP_CLI garden:seed "$@"); }
val() { spip_eval "include_spip('inc/config'); \$v = lire_config('$1'); echo is_array(\$v) ? json_encode(\$v) : \$v;"; }
id_page() { spip_eval "echo (int) sql_getfetsel('id_article', 'spip_articles', 'page=' . sql_quote('garden_$1'));"; }
texte_page() { spip_eval "echo sql_getfetsel('texte', 'spip_articles', 'page=' . sql_quote('garden_$1'));"; }
nb_pages() { spip_eval "echo sql_countsel('spip_articles', \"page LIKE 'garden_%'\");"; }
LOTS=plugins/garden/tests/lots

scenario_lots() {
	nouveau_site
	GARDEN_SEED_OPTIONS="--dossier=$LOTS --lot=1" "$SITE/bin/install.sh" >/dev/null
	verifier "lot 1 : quatre pages" test "$(nb_pages)" = 4
	hfb=$(val astra/hfb)
	verifier "lot 1 : lien du bouton résolu" grep -q "$(id_page d)" <<<"$hfb"
	memoire=$(val garden)
	seed --dossier=$LOTS --lot=1 >/dev/null
	verifier "relance au même lot : rien ne change" test "$(val garden)" = "$memoire"
	# Personnalisations : texte de B, couleur 0 ; suppression de C ; A reste intacte.
	spip_eval "sql_updateq('spip_articles', ['texte' => 'Texte B personnalisé.'], 'page=' . sql_quote('garden_b'));"
	spip_eval "include_spip('inc/config'); ecrire_config('astra/couleur_0', '#123456');"
	spip_eval "sql_delete('spip_articles', 'page=' . sql_quote('garden_c'));"
	sortie=$(seed --dossier=$LOTS --lot=2)
	verifier "lot 2 : page intacte mise à jour" test "$(texte_page a)" = "Texte X, lot 2."
	verifier "lot 2 : page personnalisée conservée" test "$(texte_page b)" = "Texte B personnalisé."
	verifier "lot 2 : page supprimée non recréée" test "$(id_page c)" = 0
	verifier "lot 2 : nouvelle page créée" test "$(id_page e)" -gt 0
	verifier "lot 2 : couleur personnalisée conservée" test "$(val astra/couleur_0)" = "#123456"
	verifier "lot 2 : page personnalisée signalée" grep -q 'page.b.texte' <<<"$sortie"
	verifier "lot 2 : page supprimée signalée" grep -q 'page.c.texte' <<<"$sortie"
	verifier "lot 2 : couleur signalée" grep -q 'config.astra.couleur_0' <<<"$sortie"
	attente=$(val garden/seed/en_attente)
	verifier "lot 2 : mises à jour en attente enregistrées" grep -q 'page.b.texte' <<<"$attente"
	verifier "lot 2 : version enregistrée" test "$(val garden/seed/version)" = 2
	# Réinitialisation partielle : la configuration seulement, puis une page supprimée.
	seed --dossier=$LOTS --lot=2 --reinitialiser=config -y >/dev/null
	verifier "réinitialisation : couleur remise" test "$(val astra/couleur_0)" = "#2f9e3d"
	verifier "réinitialisation : pages non touchées" test "$(texte_page b)" = "Texte B personnalisé."
	seed --dossier=$LOTS --lot=2 --reinitialiser=page.c -y >/dev/null
	verifier "réinitialisation : page supprimée recréée sur demande" test "$(id_page c)" -gt 0
}

scenario_interruption() {
	nouveau_site
	GARDEN_SEED_OPTIONS="--dossier=$LOTS --lot=1" "$SITE/bin/install.sh" >/dev/null
	# Repartir d'un site sans contenu ni mémoire du seed.
	spip_eval "sql_delete('spip_articles', \"page LIKE 'garden_%'\"); include_spip('inc/config'); effacer_config('garden');"
	verifier "arrêt simulé après la création de la troisième page" sh -c "cd '$SITE' && ! GARDEN_ARRET_APRES=3 $SPIP_CLI garden:seed --dossier=$LOTS --lot=1"
	verifier "troisième page créée mais non mémorisée" test "$(val garden/seed/objets/page.c)" = ""
	seed --dossier=$LOTS --lot=1 >/dev/null
	verifier "reprise : quatre pages, sans doublon" test "$(nb_pages)" = 4
	verifier "reprise : aucune mise à jour en attente" test "$(val garden/seed/en_attente)" = ""
}

scenario_astra_ancien() {
	nouveau_site
	GARDEN_SEED_OPTIONS="--dossier=$LOTS --lot=2" "$SITE/bin/install.sh" >/dev/null
	avant=$(val astra)
	verifier "Astra trop ancien : code 1" sh -c "cd '$SITE' && ! $SPIP_CLI garden:seed --dossier=$LOTS --lot=3"
	verifier "Astra trop ancien : rien d'écrit" test "$(val astra)" = "$avant"
	verifier "Astra trop ancien : version inchangée" test "$(val garden/seed/version)" = 2
}
```

et remplacer la liste par défaut : `scenarios=(dossier_vide lots interruption astra_ancien)`.

Run: `SPIP_CLI=… ASTRA_SPIP=/src/astra-spip bin/tester-installation.sh lots`
Expected: FAIL (commande `garden:seed` inconnue).

- [ ] **Step 3 : moteur**

`plugins/garden/inc/garden_seed.php` :

```php
<?php
/**
 * Garden — moteur du seed : applique un lot (état complet du site) sans défaire les
 * personnalisations. Voir docs/superpowers/specs/2026-10-22-site-garden-installation-design.md
 * (dépôt astra-spip), section « Relances et personnalisations ».
 *
 * @license GPL-3.0-or-later
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/config');
include_spip('inc/garden_decision');

/** Lot demandé (ou le plus récent) du dossier. */
function garden_lire_lot(string $dossier, ?int $lot): array {
	$fichiers = glob(rtrim($dossier, '/') . '/*.php');
	$numeros = array_map(function ($f) { return (int) basename($f, '.php'); }, $fichiers);
	$lot = $lot ?? max($numeros);
	$fichier = rtrim($dossier, '/') . "/$lot.php";
	if (!is_file($fichier)) {
		throw new RuntimeException("Lot $lot introuvable dans $dossier");
	}
	return include $fichier;
}

/** Version d'Astra SPIP active (méta plugin), null si inactif. */
function garden_version_astra(): ?string {
	$plugins = @unserialize($GLOBALS['meta']['plugin'] ?? '');
	return $plugins['ASTRA']['version'] ?? null;
}

function garden_cle_valeur_config(string $cle): string {
	return 'config.' . str_replace('/', '.', $cle);
}

function garden_memoire(string $chemin) {
	return lire_config('garden/seed/' . $chemin);
}

function garden_memoriser(string $chemin, $valeur): void {
	$valeur === null ? effacer_config('garden/seed/' . $chemin) : ecrire_config('garden/seed/' . $chemin, $valeur);
}

/** Page portant la clé stable garden_<clé>, 0 sinon. */
function garden_page_par_cle(string $cle): int {
	return (int) sql_getfetsel('id_article', 'spip_articles', 'page=' . sql_quote('garden_' . $cle));
}

/** Remplace @id:, @article:, @url: par les valeurs des pages créées. */
function garden_resoudre($valeur, array $ids) {
	if (is_array($valeur)) {
		$r = [];
		foreach ($valeur as $k => $v) {
			$r[garden_resoudre($k, $ids)] = garden_resoudre($v, $ids);
		}
		return $r;
	}
	if (!is_string($valeur)) {
		return $valeur;
	}
	return preg_replace_callback('/@(id|article|url):([a-z0-9_-]+)/', function ($m) use ($ids) {
		$id = (int) ($ids[$m[2]] ?? 0);
		if ($m[1] === 'id') {
			return (string) $id;
		}
		if ($m[1] === 'article') {
			return 'article' . $id;
		}
		include_spip('inc/filtres');
		return generer_objet_url($id, 'article', '', '', true);
	}, $valeur);
}

/** Valeur actuelle d'un réglage (null si absent). */
function garden_lire_config(string $cle) {
	if (strpos($cle, 'meta:') === 0) {
		return $GLOBALS['meta'][substr($cle, 5)] ?? null;
	}
	return lire_config($cle);
}

function garden_ecrire_config(string $cle, $valeur): void {
	if (strpos($cle, 'meta:') === 0) {
		ecrire_meta(substr($cle, 5), $valeur);
		return;
	}
	ecrire_config($cle, $valeur);
}

/** Correspondance d'une valeur avec la partie demandée de --reinitialiser. */
function garden_concernee(string $valeur, ?string $partie): bool {
	if ($partie === null) {
		return false;
	}
	if ($partie === 'tout') {
		return true;
	}
	if ($partie === 'config' || $partie === 'pages') {
		return strpos($valeur, $partie === 'config' ? 'config.' : 'page.') === 0;
	}
	return $valeur === $partie || strpos($valeur, $partie . '.') === 0;
}

/**
 * Applique la décision pour une valeur. $lire et $ecrire lisent et écrivent la valeur dans le
 * site ; l'empreinte enregistrée est celle de la valeur relue après écriture.
 */
function garden_appliquer(string $cle, $nouvelle, bool $enregistre, bool $existe, callable $lire, callable $ecrire, bool $reinitialiser, int $lot, array &$bilan): void {
	$e = (array) garden_memoire('empreintes/' . $cle);
	$etat = [
		'enregistre' => $enregistre && $e !== [],
		'existe' => $existe,
		'source' => $e['source'] ?? null,
		'enregistree' => $e['enregistree'] ?? null,
		'actuelle' => $existe ? garden_empreinte($lire()) : null,
		'nouvelle' => garden_empreinte($nouvelle),
	];
	$action = garden_decider($etat, $reinitialiser);
	switch ($action) {
		case 'creer':
		case 'remplacer':
			$ecrire($nouvelle);
			$relue = $lire();
			if (garden_empreinte($relue) !== $etat['nouvelle']) {
				$bilan['transformees'][] = $cle;
			}
			garden_memoriser('empreintes/' . $cle, ['source' => $etat['nouvelle'], 'enregistree' => garden_empreinte($relue)]);
			garden_memoriser('en_attente/' . $cle, null);
			break;
		case 'adopter_intacte':
			garden_memoriser('empreintes/' . $cle, ['source' => $etat['nouvelle'], 'enregistree' => $etat['actuelle']]);
			garden_memoriser('en_attente/' . $cle, null);
			break;
		case 'adopter_personnalisee':
			garden_memoriser('empreintes/' . $cle, ['source' => $etat['nouvelle'], 'enregistree' => null]);
			garden_memoriser('en_attente/' . $cle, ['lot' => $lot, 'raison' => 'personnalisee']);
			break;
		case 'signaler':
			garden_memoriser('en_attente/' . $cle, ['lot' => $lot, 'raison' => 'personnalisee']);
			break;
		case 'supprimee':
			garden_memoriser('en_attente/' . $cle, ['lot' => $lot, 'raison' => 'supprimee']);
			break;
	}
	$bilan[$action][] = $cle;
}

/**
 * Applique un lot. Renvoie le code de sortie : 0, ou 1 si Astra SPIP est trop ancien
 * (rien n'est alors écrit).
 */
function garden_seed(array $options, callable $journal): int {
	$lot = garden_lire_lot($options['dossier'], $options['lot'] ?? null);
	$astra = garden_version_astra();
	if ($astra === null || version_compare($astra, $lot['astra_min'], '<')) {
		$journal("Astra SPIP " . ($astra ?? 'inactif') . " : le lot {$lot['lot']} exige la version {$lot['astra_min']} ou plus. Rien n'a été écrit.");
		return 1;
	}
	$partie = $options['reinitialiser'] ?? null;
	$bilan = [];
	$ids = [];
	$creees = 0;

	foreach ($lot['pages'] as $cle => $page) {
		$objet = 'page.' . $cle;
		$memorise = (int) garden_memoire('objets/' . $objet);
		$id = $memorise && sql_countsel('spip_articles', 'id_article=' . $memorise) ? $memorise : 0;
		if (!$memorise && ($trouve = garden_page_par_cle($cle))) {
			$id = $trouve; // créée par un passage interrompu avant sa mémorisation : adoptée
			garden_memoriser('objets/' . $objet, $id);
		}
		$reinit = garden_concernee($objet, $partie);
		if (!$id && (!$memorise || $reinit)) {
			$id = (int) sql_insertq('spip_articles', [
				'titre' => $page['titre'], 'texte' => $page['texte'], 'id_rubrique' => -1, 'page' => 'garden_' . $cle,
				'statut' => 'publie', 'accepter_forum' => 'non', 'lang' => 'fr', 'date' => date('Y-m-d H:i:s'),
			]);
			if (!empty($options['arret_apres']) && ++$creees >= $options['arret_apres']) {
				// Test d'interruption : arrêt brutal entre la création de la page et la mémorisation
				// de son identifiant. Le passage suivant la retrouve par sa clé stable (champ page).
				exit(3);
			}
			garden_memoriser('objets/' . $objet, $id);
			$memorise = 0; // valeurs écrites à l'instant : adoption intacte (empreintes relues)
		}
		$ids[$cle] = $id;
		foreach (['titre', 'texte'] as $champ) {
			$lire = function () use ($id, $champ) { return (string) sql_getfetsel($champ, 'spip_articles', 'id_article=' . (int) $id); };
			$ecrire = function ($v) use ($id, $champ) { sql_updateq('spip_articles', [$champ => $v], 'id_article=' . (int) $id); };
			garden_appliquer("$objet.$champ", $page[$champ], (bool) $memorise, $id > 0, $lire, $ecrire, $reinit, $lot['lot'], $bilan);
		}
	}

	foreach (garden_resoudre($lot['config'], $ids) as $cle => $valeur) {
		$nom = garden_cle_valeur_config($cle);
		$lire = function () use ($cle) { return garden_lire_config($cle); };
		$ecrire = function ($v) use ($cle) { garden_ecrire_config($cle, $v); };
		$e = garden_memoire('empreintes/' . $nom);
		garden_appliquer($nom, $valeur, $e !== null, garden_lire_config($cle) !== null, $lire, $ecrire, garden_concernee($nom, $partie), $lot['lot'], $bilan);
	}

	garden_memoriser('version', $lot['lot']);
	include_spip('inc/invalideur');
	suivre_invalideur('1');

	foreach (['creer' => 'créé', 'remplacer' => 'mis à jour', 'adopter_intacte' => 'repris', 'adopter_personnalisee' => 'repris (personnalisé)', 'signaler' => 'personnalisé, mise à jour disponible', 'supprimee' => 'supprimé, mise à jour disponible', 'transformees' => 'transformé à l\'écriture'] as $action => $libelle) {
		foreach ($bilan[$action] ?? [] as $cle) {
			$journal("$libelle : $cle");
		}
	}
	$attente = (array) garden_memoire('en_attente');
	if ($attente) {
		$journal(count($attente) . " mise(s) à jour en attente (garden:seed --reinitialiser=<clé> pour l'appliquer).");
	}
	return 0;
}
```

Une page supprimée dont aucun champ ne change dans le nouveau lot donne `rien` (pas de signalement). Les champs d'une page supprimée sont signalés sous `page.<clé>.titre` et `page.<clé>.texte` : le test `grep -q 'page.c'` les couvre.

- [ ] **Step 4 : commande**

`plugins/garden/spip-cli/GardenSeed.php` :

```php
<?php

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GardenSeed extends Command
{
	protected function configure(): void
	{
		$this
			->setName('garden:seed')
			->setDescription('Applique la configuration et le contenu du site Garden Maintenance.')
			->setHelp("Crée ce qui manque, met à jour ce qui est resté intact, conserve et signale ce qui a été personnalisé ou supprimé.\nAvec --reinitialiser, remplace aussi les valeurs personnalisées (tout, config, pages ou une clé).")
			->addOption('lot', null, InputOption::VALUE_REQUIRED, 'Lot à appliquer (défaut : le plus récent)')
			->addOption('dossier', null, InputOption::VALUE_REQUIRED, 'Dossier des lots', 'plugins/garden/seed/lots')
			->addOption('reinitialiser', null, InputOption::VALUE_OPTIONAL, 'Partie à réinitialiser : tout, config, pages ou une clé', false)
			->addOption('yes', 'y', InputOption::VALUE_NONE, 'Ne pas demander de confirmation');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->demarrerSpip();
		$dossier = $input->getOption('dossier');
		if (!str_starts_with($dossier, '/')) {
			$dossier = _ROOT_RACINE . $dossier;
		}
		$reinitialiser = $input->getOption('reinitialiser');
		if ($reinitialiser === null) {
			$reinitialiser = 'tout';
		}
		if ($reinitialiser !== false && !$input->getOption('yes')
			&& !$this->io->confirm("Remplacer les valeurs personnalisées ($reinitialiser) par celles du seed ?", false)) {
			return Command::FAILURE;
		}
		include_spip('inc/garden_seed');
		$code = garden_seed([
			'dossier' => $dossier,
			'lot' => $input->getOption('lot') !== null ? (int) $input->getOption('lot') : null,
			'reinitialiser' => $reinitialiser === false ? null : $reinitialiser,
			'arret_apres' => (int) getenv('GARDEN_ARRET_APRES') ?: null,
		], fn($message) => $this->io->text($message));
		if ($code === 0) {
			$this->io->success('Site à jour');
		}
		return $code === 0 ? Command::SUCCESS : Command::FAILURE;
	}
}
```

Dans `bin/install.sh`, avant `etape "Terminé …"` :

```bash
etape "Configuration et contenu"
# shellcheck disable=SC2086
spip garden:seed ${GARDEN_SEED_OPTIONS:-}
spip cache:vider
ouvrir_droits
```

Tant que `plugins/garden/seed/lots/` est vide (avant la tâche 4), `garden:seed` doit sortir sans erreur : dans `garden_lire_lot()`, si aucun fichier n'est trouvé, renvoyer `['lot' => 0, 'astra_min' => '0', 'pages' => [], 'config' => []]` et ne pas écrire `seed/version`. Ajouter ce cas et créer `plugins/garden/seed/lots/.gitkeep`.

- [ ] **Step 5 : scénarios**

Run: `SPIP_CLI=… ASTRA_SPIP=/src/astra-spip bin/tester-installation.sh`
Expected: tous les `ok`, « Tous les scénarios passent. » En cas d'échec, corriger le moteur, pas les scénarios ; si une valeur est signalée `transformé à l'écriture`, comparer la valeur écrite et relue et adapter l'écriture (jamais la comparaison).

Run: `for f in plugins/garden/inc/*.php plugins/garden/spip-cli/*.php; do php7.4 -l $f; done` — `GardenSeed.php` utilise `str_starts_with` (PHP 8), comme la commande de site-tuto : spip-cli tourne sous PHP 8.1+ ; seuls les fichiers de `inc/` doivent passer en 7.4.

- [ ] **Step 6 : commit**

```bash
git add plugins/garden bin
git commit -m "Appliquer les lots sans défaire les personnalisations"
```

---

### Task 4 : lot 1 — configuration et contenu de substitution

**Files:**
- Create: `plugins/garden/seed/lots/1.php`
- Modify: `bin/tester-installation.sh` (scénario `site`), `README.md`

**Interfaces:**
- Consumes : format des lots (tâche 3) ; réglages d'Astra SPIP : `astra/couleur_0` … `couleur_7`, `astra/largeur_conteneur`, `astra/texte_pied`, `astra/hfb` (structure de `astra_hfb_defauts()` dans `inc/astra_hfb.php` d'astra-spip) ; source de menu `pages`.

- [ ] **Step 1 : scénario « site » (échoue)**

Ajouter à `bin/tester-installation.sh` (après les fonctions de la tâche 3) :

```bash
scenario_site() {
	nouveau_site
	"$SITE/bin/install.sh" >/dev/null
	verifier "six pages publiées" test "$(spip_eval "echo sql_countsel('spip_articles', \"page LIKE 'garden_%' AND statut='publie' AND id_rubrique=-1\");")" = 6
	verifier "palette du modèle" test "$(val astra/couleur_0)" = "#40bf4f"
	hfb=$(val astra/hfb)
	verifier "menu de pages dans l'en-tête" grep -q '"source":"pages"' <<<"$hfb"
	verifier "nom du site" test "$(spip_eval 'echo $GLOBALS["meta"]["nom_site"];')" = "Les Jardins du Val"
	verifier "version du seed" test "$(val garden/seed/version)" = 1
	(cd "$SITE" && php -S 127.0.0.1:8771 >/dev/null 2>&1 &)
	sleep 1
	page=$(curl -s -A 'Mozilla/5.0 (X11; Linux x86_64) Chrome/140.0' http://127.0.0.1:8771/)
	verifier "accueil : bouton de l'en-tête" grep -q 'Demander un devis' <<<"$page"
	verifier "accueil : six pages dans le menu de l'en-tête" test "$(grep -o 'menu-item-article-[0-9]*' <<<"$page" | sort -u | wc -l)" = 6
	pkill -f "php -S 127.0.0.1:8771" || true
}
```

et l'ajouter à la liste par défaut. Run: `bin/tester-installation.sh site` → FAIL (aucun lot).

- [ ] **Step 2 : lot 1**

`plugins/garden/seed/lots/1.php` (textes écrits pour le site ; aucun texte du modèle) :

```php
<?php
/**
 * Lot 1 — socle : palette, en-tête et pied de page du modèle Garden Maintenance (relevés dans
 * son site.json le 2026-10-22), six pages de substitution, menu de pages.
 */
$widget = function (string $titre, string $texte) {
	return ['blocs' => ['texte'], 'titre' => $titre, 'texte' => $texte];
};
return [
	'lot' => 1,
	'astra_min' => '1.0.0',
	'pages' => [
		'accueil' => ['titre' => '10. Accueil', 'texte' => "{{Les Jardins du Val}} entretiennent les jardins des particuliers et des copropriétés : tonte, taille des haies, désherbage et petits aménagements.\n\nUne équipe de quatre jardiniers intervient toute l'année, sur devis gratuit."],
		'a_propos' => ['titre' => '20. À propos', 'texte' => "L'entreprise est née de l'envie de jardiner autrement : sans produits chimiques, avec des outils sobres et en laissant une place à la faune.\n\nNous travaillons dans un rayon de vingt kilomètres pour limiter nos déplacements."],
		'services' => ['titre' => '30. Services', 'texte' => "-* Tonte et entretien des pelouses\n-* Taille des haies et des arbustes\n-* Désherbage manuel et paillage\n-* Création de massifs et de potagers\n-* Ramassage des feuilles et évacuation des déchets verts"],
		'realisations' => ['titre' => '40. Réalisations', 'texte' => "Quelques chantiers récents : un jardin de ville transformé en potager partagé, une haie mixte replantée pour les oiseaux, une cour minérale végétalisée.\n\nLes photographies seront ajoutées avec des images libres de droits."],
		'faq' => ['titre' => '50. Questions fréquentes', 'texte' => "{{{Intervenez-vous toute l'année ?}}}\n\nOui : la tonte cède la place à la taille et au paillage en hiver.\n\n{{{Le devis est-il payant ?}}}\n\nNon, il est gratuit et sans engagement.\n\n{{{Que faites-vous des déchets verts ?}}}\n\nIls sont broyés et, si vous le souhaitez, laissés sur place en paillage."],
		'contact' => ['titre' => '60. Contact', 'texte' => "Pour un devis ou une question, écrivez-nous ou appelez-nous du lundi au vendredi.\n\nLe formulaire de contact sera ajouté au lot 4."],
	],
	'config' => [
		'meta:nom_site' => 'Les Jardins du Val',
		'meta:descriptif_site' => 'Entretien de jardins',
		// global-color-palette du modèle (8 premières valeurs ; la 9e, transparente, au lot 3).
		'astra/couleur_0' => '#40bf4f',
		'astra/couleur_1' => '#0d3c00',
		'astra/couleur_2' => '#0d3c00',
		'astra/couleur_3' => '#5a5a5a',
		'astra/couleur_4' => '#f2f2f2',
		'astra/couleur_5' => '#ffffff',
		'astra/couleur_6' => '#f3f2ed',
		'astra/couleur_7' => '#222821',
		'astra/largeur_conteneur' => '1200',
		'astra/texte_pied' => 'Copyright © [annee] [site]',
		'astra/hfb' => [
			'entete_ordinateur' => [
				'primary' => ['primary_left' => ['logo'], 'primary_right' => ['menu-1', 'button-1']],
			],
			'entete_mobile' => [
				'popup' => ['popup_content' => ['menu-1', 'button-1']],
				'primary' => ['primary_left' => ['logo'], 'primary_right' => ['mobile-trigger']],
			],
			'menu_mobile' => 'dropdown',
			'pied' => [
				'primary' => ['primary_1' => ['widget-1'], 'primary_2' => ['widget-2'], 'primary_3' => ['widget-3'], 'primary_4' => ['widget-4']],
				'below' => ['below_1' => ['copyright'], 'below_2' => ['social-icons-1']],
			],
			'rangees' => [
				'pied_primary' => ['colonnes' => 4, 'repartition' => '4-equal'],
				'pied_below' => ['colonnes' => 2, 'repartition' => '2-equal'],
			],
			'elements' => [
				'entete' => [
					'menu-1' => ['source' => 'pages', 'id' => 0],
					'button-1' => ['libelle' => 'Demander un devis', 'lien' => '@url:contact'],
				],
				'pied' => [
					'widget-1' => $widget('Nous joindre', "Les Jardins du Val\n\nadresse et téléphone à compléter"),
					'widget-2' => $widget('Liens rapides', "-* [Accueil->@article:accueil]\n-* [À propos->@article:a_propos]\n-* [Contact->@article:contact]"),
					'widget-3' => $widget('Nos services', "-* [Tonte et taille->@article:services]\n-* [Potagers->@article:services]\n-* [Réalisations->@article:realisations]"),
					'widget-4' => $widget('Les Jardins du Val', 'Un jardin vivant, entretenu sans produits chimiques.'),
					'social-icons-1' => ['reseaux' => [['reseau' => 'facebook', 'url' => ''], ['reseau' => 'twitter', 'url' => ''], ['reseau' => 'linkedin', 'url' => '']]],
				],
			],
		],
	],
];
```

Les menus des widgets 2 et 3 sont des listes de liens : le builder n'a pas encore de bloc « menu » dans les widgets (lot 3).

- [ ] **Step 3 : scénarios et contrôle visuel**

Run: `SPIP_CLI=… ASTRA_SPIP=/src/astra-spip bin/tester-installation.sh`
Expected: tous les scénarios passent.

Contrôle : servir le site (`php -S 127.0.0.1:8770` dans une installation de travail), ouvrir l'accueil sur ordinateur et mobile, comparer l'en-tête et le pied avec `https://websitedemos.net/garden-maintenance-09/` et consigner dans le README, section « Écarts connus », chaque différence visible avec sa cause (logo, polices, 9e couleur, répartition tablette du pied, menus des widgets, icône Google Reviews : manques listés par la spec, traités au lot 3). Une différence sans cause listée est un défaut à corriger ici.

- [ ] **Step 4 : README**

`README.md` : présentation (modèle reproduit, contenu de substitution, licences), installation (`bin/install.sh`, variables), relances et personnalisations (règles, `garden:seed --reinitialiser`, en attente), tests (`bin/tester-installation.sh`, `composer tests-unit` dans `plugins/garden`), écarts connus.

- [ ] **Step 5 : commit**

```bash
git add plugins/garden/seed bin/tester-installation.sh README.md
git commit -m "Lot 1 : configuration du modèle et contenu de substitution"
```

---

### Task 5 : lot 2 — accueil statique et pages pleine largeur

Prérequis : Astra SPIP **1.1.0** (plan `2026-10-22-pages-uniques-blocs.md` exécuté jusqu'à sa tâche 10).

**Files:**
- Create: `plugins/garden/seed/lots/2.php`
- Modify: `plugins/garden/paquet.xml` (`necessite astra [1.1.0;]`), `bin/install.sh` (`ASTRA_SPIP_REF` par défaut `v1.1.0`), `bin/tester-installation.sh` (scénario `lot2`), `README.md`

**Interfaces:**
- Consumes : réglages `astra/accueil_type`, `astra/accueil_page`, `astra/pages/<id>` (`titre`, `disposition`) d'Astra SPIP 1.1.0.

- [ ] **Step 1 : scénario (échoue)**

```bash
scenario_lot2() {
	nouveau_site
	"$SITE/bin/install.sh" >/dev/null
	verifier "accueil statique" test "$(val astra/accueil_type)" = "page"
	verifier "accueil = page accueil" test "$(val astra/accueil_page)" = "$(id_page accueil)"
	verifier "pages pleine largeur sans titre" test "$(val astra/pages/$(id_page services))" = '{"titre":"masquer","disposition":"pleine"}'
	texte=$(texte_page services)
	verifier "titre de niveau 1 dans le texte" grep -q '<h1' <<<"$texte"
	verifier "version du seed" test "$(val garden/seed/version)" = 2
}

scenario_lot1_vers_lot2() {
	nouveau_site
	GARDEN_SEED_OPTIONS="--lot=1" "$SITE/bin/install.sh" >/dev/null
	spip_eval "sql_updateq('spip_articles', ['texte' => 'Mon texte.'], 'page=' . sql_quote('garden_faq'));"
	seed --lot=2 >/dev/null
	texte=$(texte_page services)
	verifier "lot 1 → 2 : page intacte enrichie" grep -q '<h1' <<<"$texte"
	verifier "lot 1 → 2 : page personnalisée conservée" test "$(texte_page faq)" = "Mon texte."
	verifier "lot 1 → 2 : accueil statique ajouté" test "$(val astra/accueil_type)" = "page"
}
```

Ajouter les deux à la liste par défaut. Run → FAIL.

- [ ] **Step 2 : lot 2**

`plugins/garden/seed/lots/2.php` : reprend intégralement le lot 1 (copier le fichier) avec `'lot' => 2`, `'astra_min' => '1.1.0'`, et :

```php
// Lot 2 : accueil statique, pages pleine largeur sans titre (comme les six pages du modèle).
// Le titre de niveau 1 passe dans le texte de chaque page.
foreach ($pages as $cle => &$p) {
	$p['texte'] = '<h1 class="garden-titre">' . preg_replace('/^\d+\.\s*/', '', $p['titre']) . "</h1>\n\n" . $p['texte'];
}
unset($p);
$config['astra/accueil_type'] = 'page';
$config['astra/accueil_page'] = '@id:accueil';
foreach (array_keys($pages) as $cle) {
	$config["astra/pages/@id:$cle"] = ['titre' => 'masquer', 'disposition' => 'pleine'];
}
```

Pour que ce code s'applique, structurer `2.php` ainsi : `$lot1 = include __DIR__ . '/1.php'; $pages = $lot1['pages']; $config = $lot1['config'];`, le bloc ci-dessus, puis `return ['lot' => 2, 'astra_min' => '1.1.0', 'pages' => $pages, 'config' => $config];`. La valeur `@id:accueil` résolue est une chaîne (`"12"`) : c'est ce qu'attend le scénario.

Dans `plugins/garden/paquet.xml` : `<necessite nom="astra" compatibilite="[1.1.0;]" />` et `version="0.2.0"`. Dans `bin/install.sh` : `ASTRA_SPIP_REF="${ASTRA_SPIP_REF:-v1.1.0}"`. Mettre à jour `plugins/garden/tests/lots/3.php` si besoin (il exige `99.0.0`, inchangé).

- [ ] **Step 3 : scénarios**

Run: `SPIP_CLI=… ASTRA_SPIP=/src/astra-spip bin/tester-installation.sh`
Expected: tous les scénarios passent (Astra SPIP local en 1.1.0).

- [ ] **Step 4 : commit**

```bash
git add plugins/garden bin README.md
git commit -m "Lot 2 : accueil statique et pages pleine largeur"
```

---

## Suite

Lots 3 (habillage : 9e couleur, polices, répartition tablette du pied, menus des widgets, Google Reviews) et 4 (sections de substitution, formulaire de contact CVT fourni par `garden`, extensions d'édition avec aller-retour tiptap_spip vérifié) : plans à écrire quand les évolutions correspondantes d'Astra SPIP seront spécifiées.
