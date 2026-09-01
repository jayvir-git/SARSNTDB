---
name: file-jim-request
description: Files a Jim Kelley or Dr. Grigoriev email into _incoming/jim-kelley as a dated packet with REQUEST.md. Use when the user pastes an email or meeting notes and has dropped attachments in _incoming/jim-kelley, or asks to file a lab request.
---

# File a Jim / lab request

## Inbox

`_incoming/jim-kelley/` (spelling: **kelley**). New attachments are dropped flat. Do not commit data files.

## Steps

1. List files in `_incoming/jim-kelley/` (not in dated subfolders). Compare to the map in `_incoming/jim-kelley/README.md`.
2. Treat as **new** only files that are not on that “already imported” list (or that the user said just arrived).
3. Create `_incoming/jim-kelley/YYYY-MM-DD_short-slug/` with `REQUEST.md` and `files/`.
4. Move **only the new files** into `files/`. Leave importer source files flat.
5. Write `REQUEST.md` from the paste:

```markdown
# <short title>

- **Who:** Jim Kelley | Dr. Grigoriev
- **Goal:** one sentence
- **Files:** list under files/
- **Page:** which PHP page should change
- **Screenshot they want:** URL + filters if known
- **Missing:** anything not in the email or folder
```

6. Stop. List gaps. Do not implement until the user says to, unless the request is unambiguous and they asked to implement.

Do not parse xlsx in PHP. New tables go through `scripts/` → `sql/` + a drop script.
