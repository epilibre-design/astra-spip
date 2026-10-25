"""L'oracle conserve les media, sélecteurs et déclarations pertinentes d'Astra."""
import unittest
from identite import extraire_identite

class IdentiteExtractionTest(unittest.TestCase):
    def test_font_size_duplique_et_media(self):
        css = '.entry-content{color:red;font-size:80px}@media(max-width:921px){.site-title{color:red;font-size:18px;font-size:1.125rem;display:block}}@media(max-width:544px){.site-title{font-size:12px}}'
        self.assertEqual([
            '[@media (max-width:921px)] .site-title{font-size:18px;font-size:1.125rem;display:block;}',
            '[@media (max-width:544px)] .site-title{font-size:12px;}',
        ], extraire_identite(css))

    def test_selecteur_groupe_conserve(self):
        selector = 'header .custom-logo-link img,.astra-logo-svg:not(.sticky-custom-logo .astra-logo-svg,.transparent-custom-logo .astra-logo-svg)'
        self.assertEqual([selector + '{width:32px;}'], extraire_identite(selector + '{width:32px;fill:red;}'))

    def test_couleur_seule_omise(self):
        self.assertEqual([], extraire_identite('.ast-site-identity .site-title a{color:red}'))
