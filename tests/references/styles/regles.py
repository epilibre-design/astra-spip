"""Règles d'un CSS ([media] sélecteur{déclarations}), et règles de B absentes de A.

Usage : python3 regles.py A.css B.css  → une règle par ligne, dans l'ordre de B.
"""
import re
import sys


def regles(css):
    out, ctx = [], ''
    for t in re.findall(r'@media[^{]*\{|[^{}]+\{[^{}]*\}|\}', css):
        t = t.strip()
        if t.startswith('@media'):
            ctx = t[len('@media'):-1].strip()
            continue
        if t == '}':
            ctx = ''
            continue
        sel, dec = t.split('{', 1)
        out.append((f'[@media {ctx}] ' if ctx else '') + sel.strip() + '{' + dec.rstrip('}').strip() + '}')
    return out


if __name__ == '__main__':
    a = set(regles(open(sys.argv[1], encoding='utf-8').read()))
    for r in regles(open(sys.argv[2], encoding='utf-8').read()):
        if r not in a:
            print(r)
