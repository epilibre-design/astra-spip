#!/usr/bin/env python3
"""
Règles de graisse des titres d'un CSS dynamique d'Astra, dans l'ordre : règles de premier niveau
dont un sélecteur vise un titre (h1 à h6, .widget-title, .site-title) et qui déclarent font-weight.
Astra modifie des règles figées (graisse retirée d'une règle qui porte aussi la taille) : les
graisses calculées se comparent par la cascade (tests/unit/StylesTitresTest.php), non règle à règle.

Usage : python3 tests/references/styles/graisses.py complet.css > attendus/titres.css
"""
import os
import re
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))
from extraire_css import paires  # noqa: E402

TITRE = re.compile(r'(^|[\s,>.(])(h[1-6]|widget-title|site-title)(?![\w-])')

for contexte, regle in paires(open(sys.argv[1], encoding='utf-8').read()):
    if contexte or '{' not in regle:
        continue
    selecteurs, declarations = regle.split('{', 1)
    graisses = [d.strip() for d in declarations.rstrip('}').split(';') if d.strip().startswith('font-weight:')]
    if graisses and TITRE.search(selecteurs):
        print(re.sub(r'\s*,\s*', ',', selecteurs.strip()) + '{' + graisses[-1] + ';}')
