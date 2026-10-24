"""Listes de sélecteurs : ne couper que les virgules hors fonctions/attributs/chaînes."""


def decouper_selecteurs(texte: str) -> list[str]:
    resultat = []
    debut = profondeur = crochets = 0
    guillemet = None
    echappe = False
    for i, caractere in enumerate(texte):
        if echappe:
            echappe = False
            continue
        if caractere == '\\':
            echappe = True
            continue
        if guillemet:
            if caractere == guillemet:
                guillemet = None
            continue
        if caractere in '\'"':
            guillemet = caractere
        elif caractere == '(':
            profondeur += 1
        elif caractere == ')':
            profondeur -= 1
        elif caractere == '[':
            crochets += 1
        elif caractere == ']':
            crochets -= 1
        elif caractere == ',' and profondeur == 0 and crochets == 0:
            resultat.append(texte[debut:i].strip())
            debut = i + 1
    resultat.append(texte[debut:].strip())
    return [selecteur for selecteur in resultat if selecteur]
