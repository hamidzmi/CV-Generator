<div align="center">

![System Diagram](images/diagram.png)

</div>

# CV Generator

Capture everyday contributions, organize them with rich context, and make them ready for tailored CVs.  
This project keeps a record of what you worked on, why it mattered, and who you collaborated with so future CVs can be generated from actual history instead of guesswork. The **Indexing** module is live today; generation and storytelling will build on top of it next.

---

## Why it exists

- **Remember what happened**: log daily progress, impact, and collaborators in a structured way.
- **Build a career narrative**: turn raw work entries into story-ready snippets for resumes, portfolios, or customer-facing summaries.
- **Stay modular**: everything runs in one deployable app today, but pieces can be extracted into independent services when needed.

---

## Prerequisites

- Docker & Docker Compose
- `make` (for convenience targets)
- (Optional) PHP ≥ 8.2 if running the Symfony console/tests outside Docker

---

## Getting Started

```bash
# 1. Start the stack (Symfony API + Weaviate + Ollama)
docker compose up -d

# 2. Pull the embedding model once (inside the Ollama container)
docker compose exec ollama ollama pull nomic-embed-text:latest

# 3. Sync the Weaviate schema (creates the work log collection)
make schema-sync
```

Visit the form UI at [http://localhost:8000/work-log/new](http://localhost:8000/work-log/new) to log an entry.

---

## Makefile Shortcuts

| Command        | Description                                              |
|----------------|----------------------------------------------------------|
| `make up`      | Start services in the background                         |
| `make down`    | Stop and remove containers                               |
| `make logs`    | Tail `symfony`, `weaviate`, and `ollama` logs             |
| `make schema-sync` | Ensure the Weaviate `WorkLogEntry` class exists      |
| `make test`    | Run the Symfony test suite inside the `symfony` service  |

---

## Usage

### 1. Web Form

- URL: `http://localhost:8000/work-log/new`  
- Fields: description, technologies, project, work type, role, collaborators, impact.  
- The controller automatically:
  - Generates a unique entry id  
  - Timestamps the log (`loggedAt`)  
  - Summarises embeddings (semantic “fingerprints”) in the background  
  - Stores everything for later retrieval  
  - Flashes success/error feedback

### 2. JSON API (existing)

POST `http://localhost:8000/index/work-log-entry`

```json
{
  "entryId": "manual-123",
  "text": "Implemented work log indexing.",
  "technologies": ["PHP", "Symfony"],
  "projectName": "CV Generator",
  "workType": "feature",
  "businessImpact": "Improved traceability for CV generation.",
  "role": "Backend Engineer",
  "collaborators": ["Alice"],
  "loggedAt": "2025-10-14T12:30:00Z",
  "embedding": [0.1, 0.2, 0.3] // optional when form is used (auto-generated)
}
```

### 3. Inspecting Indexed Data

```bash
# List objects
curl http://127.0.0.1:8080/v1/objects?class=WorkLogEntry

# GraphQL query with metadata + vector
curl http://127.0.0.1:8080/v1/graphql \
  -H 'Content-Type: application/json' \
  -d '{
        "query": "{ Get { WorkLogEntry { sourceEntryId projectName workType technologies businessImpact role collaborators loggedAt content _additional { id vector } } } }"
      }'
```

If you need to reset the schema (e.g. vector dimension mismatch):

```bash
curl -X DELETE http://127.0.0.1:8080/v1/schema/WorkLogEntry
make schema-sync
```

---

## Tests

```bash
# Host machine
cd backend && ./vendor/bin/simple-phpunit

# Or via Docker
make test
```

Tests cover:
- Domain value objects & handler behavior
- REST controller validation
- Twig form submission with fake embedding/vector store adapters

---

## Code Layout

```
backend/
 ├─ src/
 │   └─ Modules/
 │       └─ Indexing/
 │            ├─ Domain/        # Entities, value objects, repositories
 │            ├─ Application/   # Commands + handlers, embedding contracts
 │            ├─ Infrastructure/# Weaviate & Ollama adapters
 │            └─ Presentation/  # HTTP controllers + console commands
 ├─ templates/                   # Twig views
 ├─ config/                      # Service wiring + environment-specific config
 └─ tests/                       # PHPUnit suites with test doubles
```

---

## Next Steps

- Craft the CV generation module that pulls the most relevant work stories and assembles tailored CV sections.
- Introduce asynchronous pipelines so embeddings/indexing can happen off the main request.
- Add retrieval endpoints for dashboards or reporting tools.
- Layer authentication/authorization once more than one user starts logging entries.

---

## Troubleshooting

- **Embedding vector cannot be empty**  
  Ensure the embedding model is pulled and reachable (`ollama pull ...`).
- **Vector dimension mismatch**  
  Delete and recreate the Weaviate class (see above).
- **Weaviate 404 on schema sync**  
  Run `make schema-sync` inside Docker (`docker compose exec symfony ...`).

Happy indexing!
