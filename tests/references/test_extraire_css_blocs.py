"""Tests de l'évaluation des sélecteurs : python3 -m unittest tests/references/test_extraire_css_blocs.py"""
import os
import sys
import unittest

sys.path.insert(0, os.path.dirname(__file__))
from extraire_css_blocs import filtrer_css, filtrer_liste, satisfiable  # noqa: E402


class Selecteurs(unittest.TestCase):

    def test_classes(self):
        self.assertTrue(satisfiable('.wp-block-columns > .wp-block-column'))
        self.assertFalse(satisfiable('.wp-block-columns.has-background'))
        self.assertTrue(satisfiable('figure'))

    def test_not_sur_classe_perdue_reste_vrai(self):
        self.assertTrue(satisfiable('.wp-block-columns:not(.is-not-stacked-on-mobile) > .wp-block-column'))

    def test_not_sur_classe_conservee(self):
        self.assertTrue(satisfiable('.wp-block-group:not(.wp-block-columns)'))

    def test_is_et_where(self):
        self.assertTrue(satisfiable(':where(.wp-block-group.has-background, .wp-block-group) > p'))
        self.assertFalse(satisfiable(':is(.has-background, .is-style-outline) p'))
        self.assertTrue(satisfiable(':is(.wp-block-quote, .has-x)'))

    def test_attribut_style(self):
        self.assertFalse(satisfiable('.wp-block-column[style*=flex-basis]'))
        self.assertTrue(satisfiable('.wp-block-cover[data-x]'))

    def test_pseudo_element_et_echappement(self):
        self.assertTrue(satisfiable('.wp-block-quote::before'))
        self.assertFalse(satisfiable('.md\\:flex'))

    def test_liste_partielle_garde_les_selecteurs_intacts(self):
        self.assertEqual(
            filtrer_liste(':is(.wp-block-group, .has-background) p, .has-text-color, .wp-block-quote'),
            ':is(.wp-block-group, .has-background) p,.wp-block-quote')
        self.assertIsNone(filtrer_liste('.has-text-color, .is-style-outline'))

    def test_media_query(self):
        css = '@media (min-width:782px){.wp-block-columns{flex-wrap:nowrap}.has-x{color:red}}.is-y{color:blue}'
        self.assertEqual(filtrer_css(css), '@media (min-width:782px){.wp-block-columns{flex-wrap:nowrap}}')

    def test_commentaires_et_keyframes(self):
        css = '/* a { } */@keyframes x{from{opacity:0}to{opacity:1}}.wp-block-cover{animation:x 1s}'
        self.assertEqual(filtrer_css(css), '@keyframes x{from{opacity:0}to{opacity:1}}.wp-block-cover{animation:x 1s}')


if __name__ == '__main__':
    unittest.main()
