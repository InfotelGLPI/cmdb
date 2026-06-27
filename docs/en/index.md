# CMDB Plugin — Documentation

## Overview

The **CMDB** plugin for GLPI extends the native **impact analysis** of GLPI 11 with two key features: **custom icons per object type** and a **configurable information panel** displayed in the tooltip of each node in the impact graph. It also provides **custom CI types** (configuration item types defined by administrators) and an **Operation Processes** module.

- **License**: GPLv3+
- **Authors**: Xavier CAILLAUD, Infotel

> **Deprecated features**: Custom CI objects (`CIType`, `Ci`) are maintained for backward compatibility but are largely superseded by the **native custom objects in GLPI 11**. Their use is discouraged for new installations.

---

## Features

### Active
- **Custom impact icons**: assign an image to a GLPI object type (with an optional sub-type criterion, e.g. computer type = Server)
- **Impact information**: configure the fields displayed in the tooltip of an impact graph node (native GLPI fields, Fields plugin fields, CI fields)
- **Criticality**: attach a criticality level to GLPI objects via a dedicated tab
- **Operation Processes**: manage business processes linked to GLPI objects (tickets, changes, problems)
- **CI Types**: define configuration item types with custom fields (text, textarea, date, number, yes/no)
- Compatibility with the **Fields** plugin for additional fields in impact tooltips
- Compatibility with the **Resources** plugin for asset types

### Deprecated (kept for backward compatibility)
- Custom CI object management with dynamic fields
- CI ↔ ticket association (`Cmdb_Ticket`)
- Documents attached to CI types (`CIType_Document`)

---

## Installation

1. Download the archive from [GitHub Releases](https://github.com/InfotelGLPI/cmdb/releases).
2. Extract it into the `plugins/` directory of your GLPI installation.
3. Log in to GLPI as an administrator.
4. Go to **Configuration → Plugins** and activate **CMDB**.

---

## Configuration

### Profile rights

In **Administration → Profiles**, a **CMDB** tab appears on each profile.

| Right | Scope |
|---|---|
| `plugin_cmdb_cis` | Configuration items (CI) and criticality |
| `plugin_cmdb_citypes` | CI types |
| `plugin_cmdb_impacticons` | Impact icons |
| `plugin_cmdb_impactinfos` | Impact information (tooltips) |
| `plugin_cmdb_operationprocesses` | Operation processes |

Standard GLPI rights apply: `READ`, `CREATE`, `UPDATE`, `DELETE`, `PURGE`.

---

## Usage

### Custom impact icons

Replaces the default icon of a GLPI object type in the impact graph with a custom image.

**Access**: **Configuration → CMDB - Icons**

1. Click **Add**.
2. Select the **object type** (computer, network equipment, appliance, etc.).
3. *(Optional)* Select a **criterion** to narrow down (e.g. computer type = Server).
4. Upload an image file (PNG, JPG…).
5. Save.

**Display priority**:
1. Object photo (if set in GLPI)
2. Object model photo (if set)
3. Icon defined by criterion in CMDB
4. Default icon defined in CMDB

Icons are cached (key `cmdb_cache_*`) to avoid repeated database queries.

### Impact information (tooltip)

Displays business fields in the **tooltip** that appears when hovering over a node in the impact graph.

**Access**: **Configuration → CMDB - Information**

1. Click **Add**.
2. Select the **object type**.
3. Choose the **fields to display** from:
   - Native GLPI fields (search options)
   - **Fields** plugin fields (if installed and active)
   - Custom fields from CMDB CI types
4. Set the **display order** for each field.
5. Save.

The tooltip appears in the impact analysis when clicking on a node.

### Criticality

Criticality can be assigned to GLPI objects. It appears as a tab on level-1 objects (root entity).

**Access**: on a GLPI object form → **Criticalities** tab.

### CI Types and Configuration Items

> Deprecated feature — prefer native custom objects in GLPI 11.

**Access**: **Plugins → CMDB**

A CI type defines an **object template** with:
- A name (which becomes a dynamic GLPI object type)
- Custom fields (text, textarea, date, number, yes/no)
- An icon (image) displayed in the CMDB menu
- An `is_imported` flag to link to an existing native GLPI type

Once a type is created, **configuration items** (CIs) are instances of that type, with custom field values filled in.

### Operation Processes

An **operation process** represents a business process that can be linked to GLPI objects (tickets, changes, problems). It has:
- A **state** (configurable dropdown)
- **Actors** (users and groups)
- **Linked objects** (tickets, changes, problems)
- Notes and history tabs

**Access**: **Plugins → CMDB → Operation Processes**

---

## Database schema

| Table | Description |
|---|---|
| `glpi_plugin_cmdb_citypes` | CI types (custom dropdown) |
| `glpi_plugin_cmdb_cis` | Configuration items |
| `glpi_plugin_cmdb_cifields` | Custom field definitions for a CI type |
| `glpi_plugin_cmdb_civalues` | Custom field values per CI |
| `glpi_plugin_cmdb_citypes_documents` | CI type ↔ document (icon) association |
| `glpi_plugin_cmdb_ci_cis` | Relations between CIs |
| `glpi_plugin_cmdb_impacticons` | Impact icons per object type |
| `glpi_plugin_cmdb_impactinfos` | Impact tooltip configuration |
| `glpi_plugin_cmdb_impactinfofields` | Fields shown in a tooltip |
| `glpi_plugin_cmdb_criticities` | Criticality levels |
| `glpi_plugin_cmdb_criticity_items` | Criticality ↔ objects association |
| `glpi_plugin_cmdb_operationprocesses` | Operation processes |
| `glpi_plugin_cmdb_operationprocesses_items` | Objects linked to a process |
| `glpi_plugin_cmdb_operationprocessstates` | Operation process states |
| `glpi_plugin_cmdb_cmbds_tickets` | CMDB ↔ ticket association (deprecated) |

---

## Integrations

### Fields plugin

When the **Fields** plugin is installed and active, its additional fields are available in the **impact information** (tooltip) configuration. They appear in the "fields" section of the field selector.

### Resources plugin

Asset types from the **Resources** plugin are recognized and can be included in GLPI's impact analysis with custom icons.

---

## Uninstallation

In **Configuration → Plugins**, deactivate then uninstall **CMDB**. All `glpi_plugin_cmdb_*` tables are dropped, along with the associated profile rights.

---

## Useful links

- [GitHub repository](https://github.com/InfotelGLPI/cmdb)
- [Report a bug](https://github.com/InfotelGLPI/cmdb/issues)
- [Contribute translations](https://explore.transifex.com/infotelGLPI/GLPI_cmdb/)
- [Infotel GLPI blog](https://blogglpi.infotel.com)
