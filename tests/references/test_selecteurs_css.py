import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

from css_selecteurs import decouper_selecteurs
from extraire_css import selecteurs


class SelecteursCss(unittest.TestCase):
    def test_virgules_internes_et_echappements(self):
        for texte, attendu in [
            ('.a:where(.b,.c), .d', ['.a:where(.b,.c)', '.d']),
            ('a[href=","], .d', ['a[href=","]', '.d']),
            ('.entry-content :where(h1,h2)', ['.entry-content :where(h1,h2)']),
            (r'.a\,b, .c', [r'.a\,b', '.c']),
            (r'a[title="a\\"], .c', [r'a[title="a\\"]', '.c']),
            (':is(.a, :not(.b,.c)), .d', [':is(.a, :not(.b,.c))', '.d']),
        ]:
            with self.subTest(texte=texte):
                self.assertEqual(attendu, decouper_selecteurs(texte))

    def test_extraction_ne_coupe_pas_where(self):
        self.assertEqual({'.entry-content :where(h1,h2)', '.d'},
                         selecteurs('.entry-content :where(h1,h2), .d{color:red}'))

    def test_graisse_cible_uniquement_where(self):
        with tempfile.TemporaryDirectory() as tmp:
            css = Path(tmp)/'test.css'
            css.write_text('.entry-content :where(h1){font-weight:700;}')
            resultat = subprocess.run([sys.executable, str(Path(__file__).parent/'styles/graisses.py'), str(css)],
                                      capture_output=True, text=True, check=True)
            self.assertEqual('.entry-content :where(h1){font-weight:700;}\n', resultat.stdout)
