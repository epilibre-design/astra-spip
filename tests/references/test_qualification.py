import copy
from pathlib import Path
import tempfile
import unittest
from verifier_qualification import erreurs_qualification


class QualificationTest(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        self.racine = Path(self.tmp.name)
        (self.racine / 'preuve.json').write_text('{}')
        self.inventaire = {'changements': [{'id': f'a-{s}', 'statut': s} for s in ['I', 'L', 'V', 'H']]}
        self.decisions = [dict(id=f'a-{s}', statut='equivalent', source='source officielle', preuve='preuve.json', domaine='CSS') for s in ['I', 'L', 'V']]

    def testComplet(self):
        self.assertEqual([], erreurs_qualification(self.inventaire, self.decisions, self.racine))

    def testDoublon(self):
        self.assertTrue(erreurs_qualification(self.inventaire, self.decisions + [self.decisions[0]], self.racine))

    def testManquant(self):
        self.assertTrue(erreurs_qualification(self.inventaire, self.decisions[:-1], self.racine))

    def testPreuveAbsente(self):
        self.decisions[0]['preuve'] = 'inexistante.json'
        self.assertTrue(erreurs_qualification(self.inventaire, self.decisions, self.racine))

    def testPreuveHorsDepot(self):
        self.decisions[0]['preuve'] = '../preuve.json'
        self.assertTrue(erreurs_qualification(self.inventaire, self.decisions, self.racine))

    def testStatutInconnu(self):
        self.decisions[0]['statut'] = 'a-voir'
        self.assertTrue(erreurs_qualification(self.inventaire, self.decisions, self.racine))

    def testLimiteExigeDecision(self):
        self.decisions[0]['statut'] = 'limite-assumee'
        self.assertTrue(erreurs_qualification(self.inventaire, self.decisions, self.racine))
        self.decisions[0]['decision'] = 'preuve.json#decision-explicite'
        self.assertEqual([], erreurs_qualification(self.inventaire, self.decisions, self.racine))

    def testChampsRequis(self):
        for cle in ['source', 'preuve', 'domaine']:
            decisions = copy.deepcopy(self.decisions)
            del decisions[0][cle]
            self.assertTrue(erreurs_qualification(self.inventaire, decisions, self.racine))

    def testPasDeClotureInventee(self):
        self.decisions.append(dict(self.decisions[0], id='a-H'))
        self.assertTrue(erreurs_qualification(self.inventaire, self.decisions, self.racine))
