# Reference and help

Two simple but important pages: **Reference** (thesis and background) and **Help** (how to use the database).

## Reference (reference.php)

- **URL:** `reference.php`
- **Navigation:** Reference

### Content

- **Embedded PDF** – The thesis document is shown in an iframe: `helpimages/SARSNTDB-JO-thesis.pdf`. So the file **helpimages/SARSNTDB-JO-thesis.pdf** must exist for the reference page to show the thesis.
- **Purpose** – Gives users a single place to read the full thesis and learn more about the database design and biology.

### Related files

- **reference.php** – Page that includes Navigation and the iframe.
- **helpimages/SARSNTDB-JO-thesis.pdf** – PDF file (must be present).

---

## Help (help.php)

- **URL:** `help.php`
- **Navigation:** Help

### Content

- **Overview** – Short description of the database (SARS-CoV-2 proteins, structural domains, comparison with SARS-CoV, function, coordinates, RNA/AA sequences).
- **Step-by-step instructions** with screenshots:
  - Home and Search dropdown (weclomepage.png, welcomedropdown.png).
  - Gene/Protein dropdown (genedropdown.png).
  - View Detail and gene/protein detail page (sgenedetail.png).
  - Compare domains in SARS-CoV and SARS-CoV-2 (sgenecomp.png).
  - Mutations page (mutationpage1.png, mutationpage2.png).
  - Repeats page (repeatspage.png).

Images are in **helpimages/** and referenced with paths like `./helpimages/weclomepage.png`. Typo “weclome” is in the filename; keep it if that’s what’s in the repo so the image loads.

### Related files

- **help.php** – HTML and text; includes Navigation.
- **helpimages/** – weclomepage.png, welcomedropdown.png, genedropdown.png, sgenedetail.png, sgenecomp.png, mutationpage1.png, mutationpage2.png, repeatspage.png, SARSNTDB-JO-thesis.pdf, and any fig*.png/jpeg/tiff.

---

## Summary

| Page | Purpose |
|------|--------|
| reference.php | Display thesis PDF (helpimages/SARSNTDB-JO-thesis.pdf) |
| help.php | User instructions and screenshots for Search (Genome, Mutations, Repeats), Detail, Comparison, and Mutations/Repeats pages |

Both pages include **Navigation.php** so the user can jump to Search, Reference, or Help from anywhere.
