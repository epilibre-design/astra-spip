#!/usr/bin/env python3
"""
Extrait le CSS ajouté par le builder d'en-tête et de pied de page d'Astra.

Usage : python3 tests/references/extraire_css.py [sorties] > css/astra-hfb.css

Compare le CSS dynamique des configurations R1 à R4 à celui de R0 (disposition par
défaut, déjà portée dans css/astra-dynamic.css) et ne garde que les règles absentes de R0,
règle par règle, y compris à l'intérieur des media queries : une règle de R0 n'est jamais
recopiée, pour ne pas annuler les adaptations de css/astra-dynamic.css.
"""
import os
import sys

CONFIGURATIONS = ['R1-entete-complet', 'R2-entete-centre', 'R3-pied-colonnes', 'R4-pied-asymetrique']


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


def main():
    sorties = sys.argv[1] if len(sys.argv) > 1 else os.path.join(os.path.dirname(__file__), 'sorties')
    lire = lambda conf: open(os.path.join(sorties, conf, 'dynamique.css'), encoding='utf-8').read()
    connues = set(paires(lire('R0-defaut')))
    nouvelles = []
    for conf in CONFIGURATIONS:
        for p in paires(lire(conf)):
            if p not in connues:
                connues.add(p)
                nouvelles.append(p)

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
