#!/usr/bin/env python3
"""
Génère css/astra-blocs.css : CSS des blocs natifs WordPress conservés par wp2spip.

Usage : WP=<racine WordPress> python3 tests/references/extraire_css_blocs.py <page capturée> > css/astra-blocs.css

Sources (WordPress 7.1.3) : styles communs, styles des blocs gardés par wp2spip, puis
global-styles-inline-css de la page capturée. wp2spip ne garde que les classes wp-block-* et
retire l'attribut style : un sélecteur qui exige une autre classe, hors négation, ne peut plus
rien sélectionner. Le générateur retire ces sélecteurs entiers d'une liste, sans jamais réécrire
l'intérieur d'un sélecteur (la spécificité de :is() et :not() est celle de leur argument le plus
spécifique : retirer une branche, même impossible, changerait la cascade). En cas de doute, le
sélecteur est gardé. Les règles identiques à celles de css/astra-dynamic.css ne sont pas répétées.
"""
import os
import re
import sys

sys.path.insert(0, os.path.dirname(__file__))
from extraire_css import instructions, paires  # noqa: E402

BLOCS = ['paragraph', 'heading', 'list', 'quote', 'pullquote', 'code', 'preformatted', 'verse',
         'separator', 'table', 'columns', 'column', 'group', 'buttons', 'button', 'cover', 'media-text']
PSEUDO_LISTES = ('is', 'where', 'matches', '-webkit-any', '-moz-any')


def conservee(classe):
    return classe.startswith('wp-block-')


def fin_groupe(texte, debut, ouvrant, fermant):
    """Indice du caractère fermant correspondant à texte[debut] (== ouvrant)."""
    profondeur, guillemet = 0, None
    for i in range(debut, len(texte)):
        c = texte[i]
        if guillemet:
            if c == guillemet and texte[i - 1] != '\\':
                guillemet = None
        elif c in '"\'':
            guillemet = c
        elif c == ouvrant:
            profondeur += 1
        elif c == fermant:
            profondeur -= 1
            if profondeur == 0:
                return i
    raise ValueError('groupe non fermé : ' + texte[debut:])


def decouper_liste(prelude):
    """Sélecteurs complexes d'une liste (virgules de premier niveau)."""
    resultat, profondeur, courant, guillemet = [], 0, '', None
    for c in prelude:
        if guillemet:
            courant += c
            if c == guillemet:
                guillemet = None
            continue
        if c in '"\'':
            guillemet = c
        elif c in '([':
            profondeur += 1
        elif c in ')]':
            profondeur -= 1
        elif c == ',' and profondeur == 0:
            resultat.append(courant.strip())
            courant = ''
            continue
        courant += c
    if courant.strip():
        resultat.append(courant.strip())
    return resultat


def satisfiable(selecteur):
    """Faux seulement si le sélecteur ne peut, de façon démontrée, rien sélectionner après l'import."""
    i, n = 0, len(selecteur)
    while i < n:
        c = selecteur[i]
        if c == '\\':
            i += 2
        elif c == '.':
            j = i + 1
            while j < n and (selecteur[j].isalnum() or selecteur[j] in '-_\\' or ord(selecteur[j]) > 127):
                j += 2 if selecteur[j] == '\\' else 1
            if not conservee(selecteur[i + 1:j]):
                return False
            i = j
        elif c == '[':
            j = fin_groupe(selecteur, i, '[', ']')
            if re.match(r'\[\s*style\b', selecteur[i:j + 1]):
                return False
            i = j + 1
        elif c == ':':
            m = re.match(r'::?(-?[a-zA-Z][\w-]*)', selecteur[i:])
            if not m:
                i += 1
                continue
            nom, j = m.group(1).lower(), i + m.end()
            if j < n and selecteur[j] == '(':
                k = fin_groupe(selecteur, j, '(', ')')
                if nom in PSEUDO_LISTES and not any(satisfiable(b) for b in decouper_liste(selecteur[j + 1:k])):
                    return False
                j = k + 1  # :not(), :has(), :nth-*() : gardés (prudence)
            i = j
        elif c in '"\'':
            i = selecteur.index(c, i + 1) + 1
        else:
            i += 1
    return True


def filtrer_liste(prelude):
    """Liste de sélecteurs sans les sélecteurs impossibles, chacun intact ; None si aucun ne reste."""
    gardes = [s for s in decouper_liste(prelude) if satisfiable(s)]
    return ','.join(gardes) if gardes else None


def filtrer_css(css):
    css = re.sub(r'/\*.*?\*/', '', css, flags=re.S)
    sortie = []
    for instr in instructions(css):
        if instr.startswith('@'):
            entete, _, corps = instr.partition('{')
            nom = entete.split()[0].lower()
            if nom in ('@media', '@supports', '@layer', '@container') and corps:
                interieur = filtrer_css(corps[:-1])
                if interieur:
                    sortie.append(entete.strip() + '{' + interieur + '}')
            else:
                sortie.append(instr)  # @keyframes, @font-face, @import… : tels quels
            continue
        prelude, _, bloc = instr.partition('{')
        liste = filtrer_liste(prelude.strip())
        if liste is not None:
            sortie.append(liste + '{' + bloc)
    return ''.join(sortie)


def main():
    wp = os.environ['WP']
    page = sys.argv[1]
    sources = [open(os.path.join(wp, 'wp-includes/css/dist/block-library/common.css'), encoding='utf-8').read()]
    for bloc in BLOCS:
        chemin = os.path.join(wp, 'wp-includes/blocks', bloc, 'style.css')
        if os.path.exists(chemin):
            sources.append(open(chemin, encoding='utf-8').read())
    m = re.search(r'<style id=[\'"]global-styles-inline-css[\'"][^>]*>(.*?)</style>', open(page, encoding='utf-8').read(), re.S)
    if m:
        sources.append(m.group(1))
    genere = filtrer_css('\n'.join(sources))
    dynamique = set(paires(open(os.path.join(os.path.dirname(__file__), '..', '..', 'css', 'astra-dynamic.css'), encoding='utf-8').read()))
    print('/*')
    print(' * Astra pour SPIP — CSS des blocs natifs WordPress conservés par wp2spip.')
    print(' *')
    print(' * Règles de WordPress 7.1.3 (wp-includes, GPL v2 ou ultérieure) dont un sélecteur au moins')
    print(' * peut encore correspondre au HTML importé. Fichier produit par')
    print(' * tests/references/extraire_css_blocs.py : ne pas modifier à la main.')
    print(' */')
    for instr in instructions(genere):
        if instr.startswith('@charset'):
            continue  # seulement valide en tête de fichier
        if re.match(r'@(-\w+-)?keyframes\b', instr):
            print(instr)  # paires() découperait les étapes de l'animation en règles séparées
            continue
        for contexte, regle in paires(instr):
            if (contexte, regle) not in dynamique:
                print((contexte + '{' + regle + '}') if contexte else regle)


if __name__ == '__main__':
    main()
