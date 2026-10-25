import json
import os
import tempfile
import unittest
from pathlib import Path

from extraire_css_base import extraire_base
from extraire_css import paires


class ExtractionBase(unittest.TestCase):
    def test_substitution_limitee_au_selecteur_et_propriete(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            (root/'dynamique.css').write_text('.a{color:#123456;background:#123456;}.b{color:#123456;}')
            regles = {'adaptations':[{'selecteur':'.a','propriete':'color','avant':'#123456','apres':'var(--couleur)'}]}
            css = extraire_base(root, regles)
            self.assertIn('.a{color:var(--couleur);background:#123456;}', css)
            self.assertIn('.b{color:#123456;}', css)

    def test_complement_insere_a_son_ancre(self):
        with tempfile.TemporaryDirectory() as tmp:
            root=Path(tmp)
            (root/'dynamique.css').write_text('.a{color:red;}.b{color:blue;}')
            (root/'article.html').write_text('<style id="astra-theme-css-inline-css">.c{color:green;}</style>')
            configuration={'complements':[{'fichier':'article.html','media':'','selecteur':'.c','occurrence':0,
                                          'apres':{'media':'','selecteur':'.a','occurrence':0}}]}
            self.assertEqual(['.a{color:red;}','.c{color:green;}','.b{color:blue;}'],[r for _,r in paires(extraire_base(root,configuration))])
            configuration['complements'][0]['apres']['selecteur'] = '.absente'
            with self.assertRaisesRegex(ValueError,'ancre'):
                extraire_base(root,configuration)

    def test_palette_appartient_aux_variables_du_plugin(self):
        with tempfile.TemporaryDirectory() as tmp:
            root=Path(tmp)
            (root/'dynamique.css').write_text(':root{--ast-global-color-0:#000;--ast-global-color-8:transparent;}:root{--autre:1;}')
            css=extraire_base(root,{'exclusions':[{'selecteur':':root','propriete_prefixe':'--ast-global-color-0:'}]})
            self.assertNotIn('--ast-global-color-0',css)
            self.assertIn('--autre:1',css)

    def test_deplacement_dune_regle_vers_un_autre_contexte(self):
        with tempfile.TemporaryDirectory() as tmp:
            root=Path(tmp)
            (root/'dynamique.css').write_text('.c{color:green;}.a{color:red;}')
            (root/'article.html').write_text('<style id="astra-theme-css-inline-css">.c{color:green;}</style>')
            configuration={'exclusions':[{'selecteur':'.c','fichier':'dynamique.css'}],
                           'complements':[{'fichier':'article.html','media':'','selecteur':'.c','occurrence':0,
                                          'apres':{'media':'','selecteur':'.a','occurrence':0}}]}
            self.assertEqual(['.a{color:red;}','.c{color:green;}'],[r for _,r in paires(extraire_base(root,configuration))])

    def test_where_groupe_retrouve_la_provenance_sans_changer_la_sortie(self):
        with tempfile.TemporaryDirectory() as tmp:
            root=Path(tmp)
            (root/'dynamique.css').write_text('.a{color:red;}')
            (root/'article.html').write_text('<style id="astra-theme-css-inline-css">.entry-content :where(h1,h2){clear:none;}</style>')
            configuration={'complements':[{'fichier':'article.html','media':'','selecteur':'.entry-content h1,.entry-content h2','occurrence':0,
                                          'apres':{'media':'','selecteur':'.a','occurrence':0}}]}
            self.assertIn('.entry-content :where(h1,h2){clear:none;}',extraire_base(root,configuration))

    def test_nouveaux_selecteurs_retrouvent_la_provenance_sans_reecriture(self):
        for ancien,nouveau in [('.search .ast-archive-description','.search .ast-container section.ast-archive-description'),
                               ('.a,.woocommerce .b,.c','.a,.c')]:
            with self.subTest(ancien=ancien), tempfile.TemporaryDirectory() as tmp:
                root=Path(tmp)
                (root/'dynamique.css').write_text('.ancre{color:red;}')
                (root/'article.html').write_text('<style id="astra-theme-css-inline-css">'+nouveau+'{clear:none;}</style>')
                configuration={'complements':[{'fichier':'article.html','media':'','selecteur':ancien,'occurrence':0,
                                              'apres':{'media':'','selecteur':'.ancre','occurrence':0}}]}
                self.assertIn(nouveau+'{clear:none;}',extraire_base(root,configuration))

    @unittest.skipUnless(os.environ.get('ASTRA_LEGACY_R0'),'Archive R0 4.11.0 privée requise')
    def test_reference411_reproduit_les_regles_existantes(self):
        configuration=json.loads((Path(__file__).parent/'base/substitutions.json').read_text())
        css=extraire_base(Path(os.environ['ASTRA_LEGACY_R0']),configuration)
        ancien=Path(os.environ['ASTRA_LEGACY_CSS']).read_text().split('*/',1)[1].strip()
        self.assertEqual(paires(ancien),paires(css))
