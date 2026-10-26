import unittest
from widgets import extraire_widgets

class WidgetsExtractionTest(unittest.TestCase):
    def test_media_propriete_et_ordre(self):
        s='.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"].footer-widget-area-inner'
        css=s+'{text-align:left;width:auto}.ast-footer-copyright{text-align:right}@media(max-width:921px){'+s+'{text-align:center}}'
        self.assertEqual([s+'{text-align:left;}','[@media (max-width:921px)] '+s+'{text-align:center;}'],extraire_widgets(css))
    def test_ancien_profil_et_entete_non_transformes(self):
        self.assertEqual([],extraire_widgets('.footer-widget-area[data-section="sidebar-widgets-footer-widget-1"] .footer-widget-area-inner{text-align:center}.header-widget-area{text-align:left}'))
