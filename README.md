# Novyra Graphis — Visualisateur d'ontologies OWL/RDF

Application web PHP 8 MVC pour visualiser des ontologies OWL/RDF avec D3.js.

## Fonctionnalités

- **5 visualisations D3.js** : Arbre, Radial, Force, Coupe (Neighborhood), Sunburst + vue Combinée
- Chargement de fichiers OWL/RDF/TTL/N3/JSON-LD
- Visualisation de la hiérarchie de classes depuis un concept C
- Visualisation des propriétés d'un concept
- Hiérarchie de propriété
- Vue combinée : héritage + propriétés + chaîne de propriété
- **Persistance d'état** entre les visualisations (concept courant, nœuds ouverts, profondeur)
- Wiki intégré (Manuel Technique + Manuel de Réalisation)

## Stack

- PHP 8.2 + MVC maison
- EasyRdf ^1.1 (parsing OWL/RDF)
- D3.js 7.8.5
- Apache 2 (Docker)
- Railway (hébergement)

## Installation locale

```bash
git clone <repo>
cd novyra-graphis
composer install
mkdir -p storage/uploads && chmod 777 storage/uploads
php -S localhost:8080 -t public/
```

Accéder à `http://localhost:8080`

## Déploiement Railway

1. Fork/push sur GitHub
2. Nouveau projet Railway → "Deploy from GitHub repo"
3. Railway détecte le Dockerfile automatiquement
4. L'application est disponible sur l'URL publique Railway

## Structure

```
public/          ← DocumentRoot (index.php, css, js)
app/
  Core/          ← Router, Request, Controller
  Controllers/   ← Home, Api, Wiki
  Models/        ← OntologyModel (EasyRdf), OntologySession
  Views/         ← Layouts + pages
config/routes.php
docker/apache.conf
Dockerfile
composer.json
```

## Formats supportés

`.owl` `.rdf` `.xml` `.ttl` `.n3` `.nt` `.jsonld`

## Wiki

Disponible à `/wiki` dans l'application :
- Manuel Technique
- Manuel de Réalisation
- Documentation API
- Guide des Visualisations

## Générer l'archive

```bash
tar --exclude='vendor' --exclude='storage/uploads/*' \
    -czf novyra-graphis.tar.gz novyra-graphis/
```
