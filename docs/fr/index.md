# Plugin CMDB — Documentation

## Présentation

Le plugin **CMDB** pour GLPI enrichit l'**analyse d'impact** native de GLPI 11 avec deux fonctionnalités clés : des **icônes personnalisées par type d'objet** et une **zone d'informations configurable** affichée dans le tooltip de chaque nœud du graphe d'impact. Il propose également la gestion d'**objets CI personnalisés** (types d'éléments de configuration définis par l'administrateur) et un module **Processus opérationnels**.

- **Licence** : GPLv3+
- **Auteurs** : Xavier CAILLAUD, Infotel

> **Fonctionnalités dépréciées** : Les objets CI personnalisés (`CIType`, `Ci`) sont maintenus pour compatibilité ascendante mais sont largement couverts par les **objets personnalisés natifs de GLPI 11**. Leur usage est déconseillé pour les nouvelles installations.

---

## Fonctionnalités

### Actives
- **Icônes d'impact personnalisées** : attribuer une image à un type d'objet GLPI (avec critère optionnel par sous-type, ex. : type d'ordinateur)
- **Informations d'impact** : configurer les champs affichés dans le tooltip d'un nœud du graphe d'impact (champs GLPI natifs, champs du plugin Fields, champs CI)
- **Criticité** : associer un niveau de criticité aux objets GLPI via un onglet dédié
- **Processus opérationnels** : gérer des processus métier liés aux objets GLPI (tickets, changements, problèmes)
- **Types de CI** : définir des types d'éléments de configuration avec des champs personnalisés (texte, zone de texte, date, nombre, oui/non)
- Compatibilité avec le plugin **Fields** pour les champs additionnels dans les tooltips d'impact
- Compatibilité avec le plugin **Resources** pour les types d'actifs

### Dépréciées (maintenues pour compatibilité)
- Gestion d'objets CI personnalisés avec champs dynamiques
- Association CI ↔ tickets (`Cmdb_Ticket`)
- Documents attachés aux types de CI (`CIType_Document`)

---

## Installation

1. Télécharger l'archive depuis [GitHub Releases](https://github.com/InfotelGLPI/cmdb/releases).
2. Décompresser dans le répertoire `plugins/` de GLPI.
3. Se connecter à GLPI en tant qu'administrateur.
4. Aller dans **Configuration → Plugins** et activer **CMDB**.

---

## Configuration

### Droits par profil

Dans **Administration → Profils**, un onglet **CMDB** apparaît sur chaque profil.

| Droit | Objet concerné |
|---|---|
| `plugin_cmdb_cis` | Éléments de configuration (CI) et criticité |
| `plugin_cmdb_citypes` | Types de CI |
| `plugin_cmdb_impacticons` | Icônes d'impact |
| `plugin_cmdb_impactinfos` | Informations d'impact |
| `plugin_cmdb_operationprocesses` | Processus opérationnels |

Les droits standards GLPI s'appliquent : `READ`, `CREATE`, `UPDATE`, `DELETE`, `PURGE`.

---

## Utilisation

### Icônes d'impact personnalisées

Permet de remplacer l'icône par défaut d'un type d'objet dans le graphe d'impact GLPI par une image personnalisée.

**Accès** : **Configuration → CMDB - Icônes**

1. Cliquer sur **Ajouter**.
2. Sélectionner le **type d'objet** (ordinateur, équipement réseau, application, etc.).
3. *(Optionnel)* Sélectionner un **critère** pour affiner (ex. : type d'ordinateur = Serveur).
4. Téléverser un fichier image (PNG, JPG…).
5. Enregistrer.

**Priorité d'affichage** :
1. Photo de l'objet (si disponible dans GLPI)
2. Photo du modèle de l'objet (si disponible)
3. Icône définie par critère dans CMDB
4. Icône par défaut définie dans CMDB

Les icônes sont mises en cache (clé `cmdb_cache_*`) pour éviter des requêtes répétées en base.

### Informations d'impact (tooltip)

Permet d'afficher des champs métier dans le **tooltip** qui apparaît au survol d'un nœud dans le graphe d'impact.

**Accès** : **Configuration → CMDB - Informations**

1. Cliquer sur **Ajouter**.
2. Sélectionner le **type d'objet**.
3. Choisir les **champs à afficher** parmi :
   - Champs natifs GLPI (options de recherche)
   - Champs du plugin **Fields** (si installé et actif)
   - Champs personnalisés des types CI CMDB
4. Définir l'**ordre d'affichage** de chaque champ.
5. Enregistrer.

Le tooltip s'affiche dans l'analyse d'impact en cliquant sur un nœud.

### Criticité

La criticité peut être attribuée aux objets GLPI. Elle s'affiche en onglet sur les objets de niveau 1 (entité racine).

**Accès** : sur la fiche d'un objet GLPI → onglet **Criticités**.

### Types de CI et Éléments de configuration

> Fonctionnalité dépréciée — préférer les objets personnalisés natifs de GLPI 11.

**Accès** : **Plugins → CMDB**

Un type de CI définit un **modèle d'objet** avec :
- Un nom (qui devient un type d'objet GLPI dynamique)
- Des champs personnalisés (texte, zone de texte, date, nombre, oui/non)
- Une icône (image) affichée dans le menu CMDB
- Un indicateur `is_imported` pour lier à un type GLPI natif existant

Une fois le type créé, les **éléments de configuration** (CI) sont des instances de ce type, avec les valeurs des champs personnalisés renseignées.

### Processus opérationnels

Un **processus opérationnel** représente un processus métier pouvant être lié à des objets GLPI (tickets, changements, problèmes). Il dispose de :
- Un **état** (liste déroulante configurable)
- Des **acteurs** (utilisateurs et groupes)
- Des **objets liés** (tickets, changements, problèmes)
- Des onglets de notes et d'historique

**Accès** : **Plugins → CMDB → Processus opérationnels**

---

## Structure des tables

| Table | Description |
|---|---|
| `glpi_plugin_cmdb_citypes` | Types de CI (dropdown personnalisé) |
| `glpi_plugin_cmdb_cis` | Éléments de configuration |
| `glpi_plugin_cmdb_cifields` | Définition des champs personnalisés d'un type de CI |
| `glpi_plugin_cmdb_civalues` | Valeurs des champs personnalisés par CI |
| `glpi_plugin_cmdb_citypes_documents` | Association type de CI ↔ document (icône) |
| `glpi_plugin_cmdb_ci_cis` | Relations entre CI |
| `glpi_plugin_cmdb_impacticons` | Icônes d'impact par type d'objet |
| `glpi_plugin_cmdb_impactinfos` | Configuration des tooltips d'impact |
| `glpi_plugin_cmdb_impactinfofields` | Champs affichés dans un tooltip |
| `glpi_plugin_cmdb_criticities` | Niveaux de criticité |
| `glpi_plugin_cmdb_criticity_items` | Association criticité ↔ objets |
| `glpi_plugin_cmdb_operationprocesses` | Processus opérationnels |
| `glpi_plugin_cmdb_operationprocesses_items` | Objets liés à un processus |
| `glpi_plugin_cmdb_operationprocessstates` | États des processus opérationnels |
| `glpi_plugin_cmdb_cmbds_tickets` | Association CMDB ↔ tickets (déprécié) |

---

## Intégrations

### Plugin Fields

Si le plugin **Fields** est installé et actif, ses champs additionnels sont disponibles dans la configuration des **informations d'impact** (tooltip). Ils apparaissent dans la section « fields » du sélecteur de champs.

### Plugin Resources

Les types d'actifs du plugin **Resources** sont reconnus et peuvent être inclus dans l'analyse d'impact GLPI avec des icônes personnalisées.

---

## Désinstallation

Dans **Configuration → Plugins**, désactiver puis désinstaller **CMDB**. Toutes les tables `glpi_plugin_cmdb_*` sont supprimées, ainsi que les droits de profil associés.

---

## Liens utiles

- [Dépôt GitHub](https://github.com/InfotelGLPI/cmdb)
- [Signaler un bug](https://github.com/InfotelGLPI/cmdb/issues)
- [Contribuer à la traduction](https://explore.transifex.com/infotelGLPI/GLPI_cmdb/)
- [Blog Infotel GLPI](https://blogglpi.infotel.com)
