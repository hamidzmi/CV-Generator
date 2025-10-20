<div align="center">

![System Diagram](diagram.png)

</div>

# CV Generator

Capture everyday contributions, organize them with rich context, and make them ready for tailored CVs.  
This project keeps a record of what you worked on, why it mattered, and who you collaborated with so future CVs can be generated from actual history instead of guesswork. The **Indexing** module is live today and a first-pass **CV generation** workflow lets you draft the work experience section straight from your logs.

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
make up

# 2. Sync the Weaviate schema (creates the work log collection)
make schema-sync
```

The `make up` helper ensures the Ollama embedding and CV models defined in `backend/.env` are available before the app boots.

Visit the form UI at [http://localhost:8000/work-log/new](http://localhost:8000/work-log/new) to log an entry.

---

## Makefile Shortcuts

| Command        | Description                                              |
|----------------|----------------------------------------------------------|
| `make up`      | Start services and pull Ollama models                     |
| `make down`    | Stop and remove containers                               |
| `make logs`    | Tail `symfony`, `weaviate`, and `ollama` logs             |
| `make schema-sync` | Ensure the Weaviate `WorkLogEntry` class exists      |
| `make test`    | Run the Symfony test suite inside the `symfony` service  |

---

## Usage

### 1. Index work logs

- URL: `http://localhost:8000/work-log/new`  
- Fields: description, technologies, project, work type, role, collaborators, impact.  
- The controller automatically:
  - Generates a unique entry id  
  - Timestamps the log (`loggedAt`)  
  - Summarises embeddings (semantic “fingerprints”) in the background  
  - Stores everything for later retrieval  
  - Flashes success/error feedback

### 2. Generate a Work Experience Section

- Visit [http://localhost:8000/cv/work-experience](http://localhost:8000/cv/work-experience) to:
  - Regenerate the section with a different entry limit.
  - See contributions grouped by project, including the most recent year we have on record.
  - Copy the rendered bullets straight into your resume tooling.

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
 │       ├─ Indexing/           # Capture + vectorise raw work logs
 │       └─ CVGeneration/       # Turn indexed logs into CV-ready sections
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
- Aggregate work logs per project to derive date ranges for each experience heading.
- Build an editable work-experience review UI that lets users adjust/generated content, persist overrides, and optionally trigger regeneration when new logs arrive.

---

## Troubleshooting

- **Embedding vector cannot be empty**  
  Ensure the embedding model is pulled and reachable (`ollama pull ...`).
- **Vector dimension mismatch**  
  Delete and recreate the Weaviate class (see above).
- **Weaviate 404 on schema sync**  
  Run `make schema-sync` inside Docker (`docker compose exec symfony ...`).
