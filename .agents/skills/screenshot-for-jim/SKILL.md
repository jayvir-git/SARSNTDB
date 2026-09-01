---
name: screenshot-for-jim
description: Builds the exact local URL, filters, and capture list for a screenshot to send Jim Kelley or Dr. Grigoriev. Use when the user needs a screenshot for an email, acceptance check, or a packet under _incoming/jim-kelley.
---

# Screenshot for Jim

## Steps

1. Read the packet `REQUEST.md` if one exists; otherwise use the page the user named.
2. Give a **single localhost URL** with query string (junction id, size 17943, scheme, etc.).
3. List clicks/checkboxes in order (primer scheme, groups, Show).
4. Say what must be visible in the shot (junction size, arrows, chart, table row).
5. Prefer the existing capture script if it already covers the page: `scripts/capture_nj_screenshots.mjs`.
6. After capture, if a packet exists, note the file under that packet’s `sent/` (create `sent/` only when there is a file to put there).

Base: `http://localhost/SARSNTDB/`. Do not invent production URLs unless the user asked for the lab site.
