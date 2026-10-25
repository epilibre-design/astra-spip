#!/usr/bin/env python3
"""Complétude des décisions de migration, sans modifier l’inventaire historique."""
import json
from pathlib import Path
import sys
from urllib.parse import urlsplit

STATUTS = {'corrige', 'equivalent', 'absent', 'limite-assumee'}


def _preuve_existe(chemin, racine):
    if not isinstance(chemin, str) or not chemin.strip():
        return False
    relative = Path(chemin.split('#', 1)[0])
    if relative.is_absolute():
        return False
    cible = (racine / relative).resolve()
    return racine.resolve() in cible.parents and cible.is_file()


def erreurs_qualification(inventaire: dict, decisions: list, racine: Path) -> list[str]:
    attendus = {c['id'] for c in inventaire['changements'] if c['statut'] in {'I', 'L', 'V'}}
    erreurs, vus = [], set()
    if not isinstance(decisions, list):
        return ['Les décisions doivent former une liste.']
    for numero, decision in enumerate(decisions):
        if not isinstance(decision, dict):
            erreurs.append(f'Décision {numero} : objet attendu.')
            continue
        identifiant = decision.get('id')
        if not isinstance(identifiant, str):
            erreurs.append(f'Décision {numero} : id absent ou invalide.')
            continue
        if identifiant in vus:
            erreurs.append(f'{identifiant} : doublon.')
        vus.add(identifiant)
        if identifiant not in attendus:
            erreurs.append(f'{identifiant} : hors entrées I/L/V.')
        if decision.get('statut') not in STATUTS:
            erreurs.append(f'{identifiant} : statut invalide.')
        for cle in ['source', 'domaine']:
            if not isinstance(decision.get(cle), str) or not decision[cle].strip():
                erreurs.append(f'{identifiant} : {cle} absent.')
        if not _preuve_existe(decision.get('preuve'), racine):
            erreurs.append(f'{identifiant} : preuve absente ou hors dépôt.')
        if decision.get('statut') == 'limite-assumee':
            lien = decision.get('decision', '')
            if not isinstance(lien, str) or not (urlsplit(lien).scheme == 'https' or _preuve_existe(lien, racine)):
                erreurs.append(f'{identifiant} : lien de décision explicite absent.')
    erreurs.extend(f'{i} : décision manquante.' for i in sorted(attendus - vus))
    return erreurs


def main():
    racine = Path(__file__).resolve().parents[2]
    try:
        inventaire = json.loads((racine / 'docs/inventaires/astra-4.11.0-4.13.8/changements.json').read_text())
        decisions = json.loads((racine / 'docs/inventaires/astra-4.13.8-qualification/decisions.json').read_text())
        erreurs = erreurs_qualification(inventaire, decisions, racine)
    except (OSError, ValueError, KeyError) as erreur:
        erreurs = [str(erreur)]
    if erreurs:
        print('\n'.join(erreurs), file=sys.stderr)
        return 1
    print(f'{len(decisions)} décisions I/L/V complètes et reliées à des preuves versionnées.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
