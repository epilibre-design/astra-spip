"""Règles des marges des rangées d'un CSS complet de WordPress, dans l'ordre d'Astra.

Usage : python3 marges.py complet.css  → une règle par ligne, au format de regles.py.

Garde les règles des sélecteurs de prepare_advanced_margin_padding_css() (rangées du builder,
pied entier) et de section-header-builder-layout-margin (en-tête entier) qui ne déclarent que des
marges : celles qu'Astra émet pour ces options. Les autres règles de ces sélecteurs (fond, bordure,
affichage) relèvent des réglages des rangées.
"""
import re
import sys

from regles import regles

SELECTEURS = {
    '.site-below-footer-wrap[data-section="section-below-footer-builder"]',
    '.site-above-footer-wrap[data-section="section-above-footer-builder"]',
    '.ast-hfb-header .site-footer',
    '.site-primary-footer-wrap[data-section="section-primary-footer-builder"]',
    '.ast-desktop .ast-primary-header-bar.main-header-bar, .ast-header-break-point #masthead .ast-primary-header-bar.main-header-bar',
    '.ast-hfb-header .site-header',
    '.ast-above-header.ast-above-header-bar, .ast-header-break-point #masthead.site-header .ast-above-header-bar',
    '.ast-below-header-bar.ast-below-header, .ast-header-break-point .ast-below-header-bar.ast-below-header',
}


def marges(css):
    out = []
    for r in regles(css):
        m = re.match(r'(?:\[@media [^\]]*\] )?(.*?)\{(.*)\}$', r, re.S)
        proprietes = [d.split(':', 1)[0] for d in m.group(2).split(';') if d]
        if m.group(1) in SELECTEURS and proprietes and all(re.match(r'(padding|margin)-(top|bottom|left|right)$', p) for p in proprietes):
            out.append(r)
    return out


if __name__ == '__main__':
    for r in marges(open(sys.argv[1], encoding='utf-8').read()):
        print(r)
