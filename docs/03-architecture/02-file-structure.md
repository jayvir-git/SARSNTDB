# File structure

This section lists the important files and directories in SARSNTDB. Omitted: third-party examples, duplicate/backup files (e.g. `* 2.php`), and OS/editor artifacts (e.g. `._*`, `.swp`).

## Root: PHP entry points and config

| File | Purpose |
|------|--------|
| **index.php** | Home page; welcome message and pie chart. Includes Navigation. |
| **GenomeSearch.php** | Main genome search form (Gene/Protein dropdown, Start/End or sequence). Includes Navigation, ProtienInfo. |
| **GenomeResult.php** | Genome search results: genes in range, domains, repeats. Handles sequence search via reference.fasta. Uses connection.php, queries Gene_1, cov_comp, repeatcoord. |
| **GenomeDetail.php** | Gene/protein detail view (e.g. “View Detail” from results). |
| **GenomeDetailData.php** | Data/HTML for detail (protein info, domains). Uses ProteinInfo.php, connection.php. |
| **GenomeComparison.php** | SARS-CoV vs SARS-CoV-2 domain comparison page. |
| **GenomeComparisonData.php** | AJAX endpoint: returns comparison data (e.g. JSON/HTML) for a given Protein. Uses GenomeComparisonInfo.php, connection.php. |
| **GenomeComparisonInfo.php** | Class definition for comparison data (gene, feature, sequences, dashRange, etc.). |
| **MutationsSearch.php** | Mutations search UI (tabs, filters). Includes Navigation. |
| **MutationsResult.php** | Mutations result listing. Uses connection.php. |
| **MutationsSummary.php** | Mutations summary view. Uses MutationsInfo.php, connection.php. |
| **MutationsDetail.php** | Mutations detail view. Uses connection.php. |
| **MutationsInfo.php** | Class definition for mutation data (mutationsByInstrument, mutationsByFrequency, shape scores, etc.). |
| **motifvisualizer.php** | Repeats / motif search page. User enters sequence; shows positions and genome viz. Includes Navigation. |
| **repeatData.php** | Data endpoint for repeats (query by sequence). Uses RepeatInfo.php, connection.php; queries repeats, Gene_1. |
| **RepeatInfo.php** | Class for repeat data (sequence, substrings, coordinates, proteins). |
| **reference.php** | Reference page; embeds thesis PDF from helpimages. Includes Navigation. |
| **help.php** | Help page; instructions and screenshots. Includes Navigation. |
| **Navigation.php** | Shared navbar HTML and dropdown (Search → Genome / Mutations / Repeats, Reference, Help). Included by most pages. |
| **connection.php** | Database connection: creates `$con` (mysqli) for host 127.0.0.1, user/db app_sarsntdb. **Must match MySQL user and DB.** Gitignored; copy from `connection.example.php`. |
| **ProteinInfo.php** | Class ProteinInfo (used by GenomeDetailData.php). |
| **ProtienInfo.php** | Same as ProteinInfo but filename typo; required by GenomeSearch.php and others. Keep for compatibility. |
| **header.php** | Minimal header; requires connection.php. |
| **footer.php** | Footer fragment. |
| **Domain_plot.php** | Domain plotting. |
| **visualIntragenome.php** | Intragenome visualization. Uses connection.php. |
| **hybrid.php** | Hybrid page; uses conn/connection.php and function.php (different layout). |
| **hybridprots.php** | Hybrid proteins; includes Navigation. |
| **info.php** | Info page. |
| **Repeats.php** | Repeats page (Navigation/ProtienInfo commented out in repo). |

## Root: assets and config

| File | Purpose |
|------|--------|
| **bootstrap.css** | Bootstrap 3 styles (local copy). |
| **style.css** | Project-specific styles. |
| **styles.css** | Alternate/duplicate styles; some pages link style.css, others styles.css (see Conventions). |
| **genomedetail.css** | Styles for genome detail view. |
| **sortable.js** | Sortable table/chart behavior (e.g. mutations). |
| **my.cnf** | Optional MySQL client config. |

## Root: SQL and data

| File | Purpose |
|------|--------|
| **SARS.sql** | Main schema and data: Gene_1, Gene_2, Protein_1 (DB name in dump may be SARS; use app_sarsntdb when importing). |
| **gene_1_25.sql** | Extended gene/protein schema (e.g. gene_1 with Domain, Motif, Function, RNA_sequence, matchedcols, protSeq). May be for a different DB name (e.g. web_app). |
| **cov_comp_25.sql** | cov_comp table: SARS-CoV vs SARS-CoV-2 domain comparison (gene, feature, cov2Start, cov2End, identities, etc.). |

## Directories

### JS/

| File | Purpose |
|------|--------|
| **main.js** | Main front-end logic (e.g. genome search form, interactions). |
| **mutClick.js** | Mutation row click / expand behavior. |
| **domainPlotter5.js** | Domain plot visualization. |
| **colorArraysc.js** | Color arrays (e.g. for charts or domains). |
| **hybridprotjs.js** | Hybrid protein page logic. |
| **jstesttooltips.js** | Tooltip tests. |
| **tst.js** | Test script. |
| **hybridprots.py**, **oneline.py** | Python helpers (not required for core web UI). |

### fastas/

| File | Purpose |
|------|--------|
| **reference.fasta** | **Reference genome** (SARS-CoV-2). Required for DNA sequence search in GenomeResult.php. |
| **reference.php** | PHP that may output or process reference data. |
| **reference.js** | JS for reference view. |
| **fastasequences.py** | FASTA sequence processing. |
| **genestend.csv** | Gene start/end or similar data. |

### img/

- **S Gene.png** (and variants) – Images for S gene or UI.

### helpimages/

- **weclomepage.png**, **welcomedropdown.png**, **genedropdown.png**, **sgenedetail.png**, **sgenecomp.png**, **mutationpage1.png**, **mutationpage2.png**, **repeatspage.png** – Screenshots for help.php.
- **SARSNTDB-JO-thesis.pdf** – Thesis PDF embedded in reference.php.
- **fig1_8_25.***, **fig2_8_25.*** – Figures.

### canvasjs-non-commercial-3.6.6/

- CanvasJS library (canvasjs.min.js, jquery.canvasjs.min.js, examples, license). Used for charts.

### phpGrid_Lite/

- Local third-party PHP grid (not used by the main genome/mutations/repeats pages). Gitignored; not on GitHub.

### .cursor/rules/

- **sarsntdb-context.mdc** – Cursor IDE rule file: project context, setup, run, debug, deploy.

### test/

- **index.html**, **main.js**, **test.css** – Local testing assets.

## Summary

- **Entry points:** index.php, GenomeSearch.php, GenomeResult.php, MutationsSearch.php, motifvisualizer.php, reference.php, help.php.
- **Shared:** Navigation.php, connection.php, ProtienInfo.php (and ProteinInfo.php).
- **Data endpoints:** GenomeComparisonData.php, GenomeDetailData.php, repeatData.php; MutationsSummary/Detail/Result for mutations.
- **Critical external file:** fastas/reference.fasta for sequence search.
- **DB:** connection.php → app_sarsntdb; tables Gene_1, Protein_1, Gene_2, cov_comp, repeats, repeatcoord (and mutation tables as in schema).
