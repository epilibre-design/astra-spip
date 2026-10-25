#!/usr/bin/env python3
"""CSS de base depuis les captures WordPress ; adaptations et contextes explicitement répertoriés."""
import json
import re
import sys
from functools import lru_cache
from pathlib import Path

from extraire_css import paires
from css_selecteurs import decouper_selecteurs


@lru_cache(maxsize=4096)
def cle(media, selecteur):
    # Correspondances de provenance seulement : la sortie conserve les sélecteurs de sa source.
    branches = []
    for simple in decouper_selecteurs(selecteur):
        # Les sélecteurs WooCommerce ne sont plus émis sans son runtime en 4.13.8.
        # Cette clé relie les groupes ; elle ne filtre ni ne réécrit leur sortie CSS.
        if 'woocommerce' in simple or 'wc-block' in simple:
            continue
        courantes = [simple]
        while any(re.search(r':where\([^()]+\)', branche) for branche in courantes):
            suivantes = []
            for branche in courantes:
                match = re.search(r':where\(([^()]+)\)', branche)
                if not match:
                    suivantes.append(branche)
                    continue
                suivantes.extend(branche[:match.start()] + argument + branche[match.end():]
                                 for argument in decouper_selecteurs(match[1]))
            courantes = suivantes
        branches.extend(courantes)
    selecteur = ','.join(branches)
    # inc/modules/posts-structures/css/special-dynamic.css.php, sélecteur recherche 4.13.8.
    selecteur = selecteur.replace('.search .ast-container section.ast-archive-description', '.search .ast-archive-description')
    selecteur = selecteur.replace('.ast-footer-copyright.site-footer-focus-item', '.ast-footer-copyright')
    selecteur = re.sub(r'\s*([,>+~])\s*', r'\1', selecteur.strip())
    media = re.sub(r'\s+', '', media)
    media = re.sub(r'921\.9+px', '921.fractionpx', media)
    return media, selecteur


def charger(racine, fichier, configuration):
    texte = (racine/fichier).read_text()
    if fichier.endswith('.html'):
        match = re.search(r'<style id="astra-theme-css-inline-css">(.*?)</style>', texte, re.S)
        if not match:
            raise ValueError(f'CSS dynamique absent : {fichier}')
        texte = match[1]
    resultat = []
    for media, regle in paires(texte):
        if '{' not in regle:
            raise ValueError(f'Instruction inattendue : {fichier}')
        selecteur, declarations = regle.split('{', 1)
        if any((not e.get('fichier') or e['fichier'] == fichier)
               and cle(media, selecteur) == cle(e.get('media', media), e['selecteur'])
               and (not e.get('propriete_prefixe') or declarations.startswith(e['propriete_prefixe']))
               for e in configuration.get('exclusions', [])):
            continue
        for adaptation in configuration.get('adaptations', []):
            if cle(media, selecteur) != cle(adaptation.get('media', media), adaptation['selecteur']):
                continue
            propriete = re.escape(adaptation['propriete'])
            avant = re.escape(adaptation['avant'])
            declarations, nombre = re.subn(r'(^|;)(' + propriete + r':)' + avant + r'(?=;|})',
                                          lambda m: m[1] + m[2] + adaptation['apres'], declarations)
            if nombre and 'selecteur_apres' in adaptation:
                selecteur = adaptation['selecteur_apres']
        resultat.append((media, selecteur + '{' + declarations))
    return resultat


def extraire_base(racine: Path, configuration: dict) -> str:
    principal = charger(racine, 'dynamique.css', configuration)
    emplacements = {}
    for i, (media, regle) in enumerate(principal):
        emplacements.setdefault(cle(media, regle.split('{', 1)[0]), []).append(i)
    sources = {}
    insertions = {}
    for complement in configuration.get('complements', []):
        fichier = complement['fichier']
        if fichier not in sources:
            sources[fichier] = charger(racine, fichier, configuration)
        candidats = [(m, r) for m, r in sources[fichier]
                     if cle(m, r.split('{', 1)[0]) == cle(complement['media'], complement['selecteur'])]
        occurrence = complement.get('occurrence', 0)
        if occurrence >= len(candidats):
            raise ValueError(f'Complément absent : {fichier} {complement["selecteur"]}')
        ancre = complement['apres']
        positions = emplacements.get(cle(ancre['media'], ancre['selecteur']), [])
        rang = ancre.get('occurrence', 0)
        if rang >= len(positions):
            raise ValueError(f'ancre absente : {ancre["selecteur"]}')
        insertions.setdefault(positions[rang], []).append(candidats[occurrence])
    resultat = []
    for i, paire in enumerate(principal):
        resultat.append(paire)
        resultat.extend(insertions.get(i, []))
    return '\n'.join(f'{m}{{{r}}}' if m else r for m, r in resultat) + '\n'


if __name__ == '__main__':
    try:
        racine = Path(sys.argv[1])
        configuration = json.loads(Path(sys.argv[2]).read_text())
        css = extraire_base(racine, configuration)
        html = (racine/'accueil.html').read_text()
        version = re.search(r'\bastra-(\d+\.\d+\.\d+)\b', html)
        if not version:
            raise ValueError('Version Astra du HTML introuvable')
        print(f'/* Astra {version[1]} — CSS de base extrait de WordPress ; extraire_css_base.py et base/substitutions.json. */')
        print(css, end='')
    except (IndexError, OSError, ValueError, KeyError) as erreur:
        print(str(erreur), file=sys.stderr)
        sys.exit(1)
