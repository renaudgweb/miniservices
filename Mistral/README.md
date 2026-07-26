# 🧠 3614 MISTRAL

<p align="center">
  <img src="Mistral.png" alt="Mistral logo" width="600"/>
</p>

**3614 MISTRAL** est une application innovante qui exploite l'API Mistral AI pour offrir des interactions intelligentes et des réponses basées sur l'intelligence artificielle directement sur votre Minitel. Grâce à la passerelle MiniPavi, cette application modernise l'expérience Minitel en intégrant des capacités d'IA avancées.

## 🎯 Fonctionnalités

- **Interactions basées sur l'IA** : Utilisez l'intelligence artificielle pour obtenir des réponses précises et contextuelles à vos questions.
- **Réponses intelligentes** : Profitez de réponses générées par l'IA pour une expérience utilisateur enrichie.
- **Intégration avec l'API Mistral AI** : Exploitez les capacités avancées de l'API Mistral AI pour des fonctionnalités intelligentes.
- **Navigation intuitive** : Utilisez les touches de votre Minitel pour naviguer facilement dans l'application.
- **Livre d'or** : Consultez l'historique des échanges des utilisateurs via une page web au design rétro Minitel (`livre.php`).

## 📖 Livre d'or (`livre.php`)

Page web de recensement des interactions des utilisateurs, dans un style Minitel (fond noir, texte blanc laiteux, scanlines, curseur clignotant). Aucune base de données : lecture directe des fichiers log.

- **Style rétro** : rendu façon terminal Minitel, responsive.
- **Pagination** : 50 échanges par page (`?p=2`), du plus récent au plus ancien.
- **Métadonnées** : durée de réponse, nombre de tokens, modèle utilisé, identifiant de session anonymisé.
- **Sécurité** : tout le contenu est échappé (`htmlspecialchars`) pour éviter les injections.

### Format des logs

Les échanges sont écrits dans `mistral.log` au format **JSON Lines** (un objet JSON par ligne) :

```json
{"date":"2026-07-26T14:30:00+02:00","model":"mistral-medium-latest","user":"...","mistral":"...","tokens":128,"duration_ms":842,"session":"a3f19c2b4d5e"}
```

- **Rotation automatique** : au-delà de 2 Mo, `mistral.log` est archivé en `mistral_AAAAMMJJ-HHMMSS.log` ; `livre.php` lit le log courant **et** toutes les archives.
- **Compatibilité** : `livre.php` gère aussi l'ancien format texte (`--------` séparateur), même mélangé au JSONL dans un même fichier.
- **Session anonymisée** : l'identifiant MiniPavi est haché (SHA-256 tronqué) avec un sel. Personnaliser `MISTRAL_LOG_SALT` dans `MiniMistral.php` **une seule fois** avant la mise en production (le changer plus tard fait diverger les hachages).

## 🚀 Utilisation de la Passerelle MiniPavi

**3614 MISTRAL** utilise la passerelle MiniPavi pour communiquer avec les services Minitel. MiniPavi permet de moderniser les services Minitel en utilisant des technologies web comme les websockets et HTTP.

- **Avantages de MiniPavi** :
  - Compatibilité avec les émulateurs Minitel modernes.
  - Facilité de développement grâce à l'utilisation de langages web.
  - Support pour le contenu multimédia via l'interface WebMedia.

## 📬 Contact

Pour toute question ou suggestion, n'hésitez pas à ouvrir une issue ou à me contacter directement.

Merci d'utiliser 3614 MISTRAL ! 😊
