"""Projection des règles d'identité du CSS officiel, sans modifier leur cascade."""
import re
import sys
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'styles'))
from regles import regles


def extraire_identite(css):
    resultat = []
    for regle in regles(css):
        m = re.match(r'^(\[@media[^\]]+\] )?(.*?)\{(.*)\}$', regle)
        if not m:
            continue
        media, sel, dec = m.groups()
        if sel in ('.site-title', '.site-header .site-description'):
            props = {'font-size', 'display'}
        elif any(x in sel for x in ('.custom-logo-link img', '.custom-mobile-logo-link img', '.astra-logo-svg')):
            props = {'width', 'max-width'}
        else:
            continue
        gardees = [d.strip() for d in dec.split(';') if d.partition(':')[0].strip() in props]
        if gardees:
            resultat.append((media or '') + sel.replace(', ', ',') + '{' + ';'.join(gardees) + ';}')
    return resultat

if __name__ == '__main__':
    print('\n'.join(extraire_identite(Path(sys.argv[1]).read_text())))
