#!/usr/bin/env python3
"""
Extrait le CSS ajouté par le builder d'en-tête et de pied de page d'Astra (configurations du
builder seulement : les références P* des pages uniques ne sont pas lues).

Usage : python3 tests/references/extraire_css.py [sorties] > css/astra-hfb.css

Compare le CSS dynamique des configurations R1 à R4 à celui de R0 (disposition par
défaut, déjà portée dans css/astra-dynamic.css) et ne garde que les règles absentes de R0,
règle par règle, y compris à l'intérieur des media queries : une règle de R0 n'est recopiée
que pour respecter l'ordre de la cascade (voir main). S'y ajoutent les grilles de toutes les
répartitions de colonnes du pied, et la largeur des rangées du pied suit celle du thème.
"""
import os
import re
import sys

CONFIGURATIONS = ['R1-entete-complet', 'R2-entete-centre', 'R3-pied-colonnes', 'R4-pied-asymetrique']

# Répartitions des colonnes du pied : Astra_Builder_Helper::$grid_size_mapping (Astra 4.11.0).
# Astra n'émet que les règles des répartitions configurées ; le formulaire SPIP les propose
# toutes, d'où la table complète, reprise de Astra_Builder_Base_Dynamic_CSS::footer_dynamic_css()
# (aperçu du personnalisateur) : ordinateur, tablette puis mobile.
GRILLES = [
    ('6-equal', 'repeat( 6, 1fr )'), ('5-equal', 'repeat( 5, 1fr )'), ('4-equal', 'repeat( 4, 1fr )'),
    ('4-lheavy', '2fr 1fr 1fr 1fr'), ('4-rheavy', '1fr 1fr 1fr 2fr'), ('3-equal', 'repeat( 3, 1fr )'),
    ('3-lheavy', '2fr 1fr 1fr'), ('3-rheavy', '1fr 1fr 2fr'), ('3-cheavy', '1fr 2fr 1fr'),
    ('3-cwide', '1fr 3fr 1fr'), ('3-firstrow', '1fr 1fr'), ('3-lastrow', '1fr 1fr'),
    ('2-equal', 'repeat( 2, 1fr )'), ('2-lheavy', '2fr 1fr'), ('2-rheavy', '1fr 2fr'), ('2-full', '2fr'),
    ('full', '1fr'),
]
TABLETTE, MOBILE = '@media (max-width:921px)', '@media (max-width:544px)'


def grilles():
    """Règles grid-template-columns de toutes les répartitions, comme l'aperçu d'Astra."""
    resultat = []
    for nom, colonnes in GRILLES:
        if nom not in ('3-firstrow', '3-lastrow'):
            resultat.append(('', '.ast-builder-grid-row-%s .ast-builder-grid-row{grid-template-columns:%s;}' % (nom, colonnes)))
    for contexte, appareil in ((TABLETTE, 'tablet'), (MOBILE, 'mobile')):
        for nom, colonnes in GRILLES:
            if nom != '2-full':
                resultat.append((contexte, '.ast-builder-grid-row-container.ast-builder-grid-row-%s-%s .ast-builder-grid-row{grid-template-columns:%s;}' % (appareil, nom, colonnes)))
    return resultat


def largeur_conteneur(paire):
    """Rangées du pied « largeur du contenu » : Astra y écrit la largeur du site en pixels
    (1200px dans les références) ; le portage utilise la variable de largeur du thème, comme
    pour la rangée basse dans css/astra-dynamic.css."""
    contexte, regle = paire
    if '-footer-wrap[data-section=' in regle.split('{', 1)[0]:
        regle = regle.replace('max-width:1200px', 'max-width:var(--ast-normal-container-width)')
    return contexte, regle


def instructions(css):
    """Découpe un CSS en instructions de premier niveau (règles, @media {...}, @import;)."""
    resultat, profondeur, debut = [], 0, 0
    for i, c in enumerate(css):
        if c == '{':
            profondeur += 1
        elif c == '}':
            profondeur -= 1
            if profondeur == 0:
                resultat.append(css[debut:i + 1].strip())
                debut = i + 1
        elif c == ';' and profondeur == 0:
            resultat.append(css[debut:i + 1].strip())
            debut = i + 1
    return [r for r in resultat if r]


def paires(css):
    """Liste de (contexte, règle) : contexte vide au premier niveau, sinon l'en-tête @media/@supports."""
    resultat = []
    for instr in instructions(css):
        if instr.startswith('@') and '{' in instr and not instr.startswith('@font-face'):
            entete, corps = instr.split('{', 1)
            for regle in instructions(corps[:-1]):
                resultat.append((entete.strip(), regle))
        else:
            resultat.append(('', instr))
    return resultat


def selecteurs(regle):
    """Sélecteurs d'une règle, normalisés (espaces)."""
    entete = regle.split('{', 1)[0]
    return {re.sub(r'\s*([>+~])\s*', r' \1 ', re.sub(r'\s+', ' ', sel)).strip() for sel in entete.split(',')}


def main():
    sorties = sys.argv[1] if len(sys.argv) > 1 else os.path.join(os.path.dirname(__file__), 'sorties')
    lire = lambda conf: open(os.path.join(sorties, conf, 'dynamique.css'), encoding='utf-8').read()
    r0 = paires(lire('R0-defaut'))
    connues = set(r0)
    nouvelles = []
    for conf in CONFIGURATIONS:
        for p in paires(lire(conf)):
            if p not in connues:
                connues.add(p)
                nouvelles.append(p)
    # Ordre de la cascade. Une règle de R0 qui suit, dans une configuration, une règle ajoutée sur le
    # même sélecteur l'emporte dans Astra ; or les règles de R0 sont dans css/astra-dynamic.css,
    # chargé avant ce fichier. On les répète donc ici, après les règles ajoutées, si elles figurent
    # telles quelles dans css/astra-dynamic.css (une règle adaptée par le portage n'est pas reprise).
    # La règle mobile des rangées du pied suit de même les règles tablette de chaque répartition.
    dynamique = set(paires(open(os.path.join(os.path.dirname(__file__), '..', '..', 'css', 'astra-dynamic.css'), encoding='utf-8').read()))
    ensemble_r0, ensemble_nouvelles = set(r0), set(nouvelles)
    reprises = []
    for conf in CONFIGURATIONS:
        vus = set()
        for p in paires(lire(conf)):
            if p in ensemble_nouvelles:
                vus |= {(p[0], sel) for sel in selecteurs(p[1])}
            elif p in ensemble_r0 and p in dynamique and p not in reprises and (
                    any((p[0], sel) in vus for sel in selecteurs(p[1])) or 'ast-builder-grid-row-mobile-' in p[1]):
                reprises.append(p)
    nouvelles += reprises
    nouvelles = [largeur_conteneur(p) for p in nouvelles] + grilles()

    print('/*')
    print(' * Astra pour SPIP — CSS du builder d\'en-tête et de pied de page.')
    print(' *')
    print(' * Règles générées par Astra 4.11.0 pour les rangées et éléments du builder, absentes de la')
    print(' * disposition par défaut (css/astra-dynamic.css). Fichier produit par')
    print(' * tests/references/extraire_css.py à partir des références WordPress : ne pas modifier à la main.')
    print(' */')
    contexte_courant = None
    for contexte, regle in nouvelles:
        if contexte != contexte_courant:
            if contexte_courant:
                print('}')
            if contexte:
                print(contexte + '{')
            contexte_courant = contexte
        print(('\t' if contexte else '') + regle)
    if contexte_courant:
        print('}')


if __name__ == '__main__':
    main()
