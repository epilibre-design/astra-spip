"""Règles d'alignement des quatre widgets, profil flex d'Astra 4.13.8."""
import re,sys
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[1]/'styles'))
from regles import regles

def extraire_widgets(css):
    resultat=[]
    for r in regles(css):
        m=re.match(r'^(\[@media[^\]]+\] )?(.*?)\{(.*)\}$',r)
        if not m:continue
        media,sel,dec=m.groups()
        if not re.fullmatch(r'\.footer-widget-area\[data-section="sidebar-widgets-footer-widget-[1-4]"\]\.footer-widget-area-inner',sel):continue
        d=[x.strip() for x in dec.split(';') if x.partition(':')[0].strip()=='text-align']
        if d:resultat.append((media or '')+sel+'{'+(';'.join(d))+';}')
    return resultat
if __name__=='__main__': print('\n'.join(extraire_widgets(Path(sys.argv[1]).read_text())))
