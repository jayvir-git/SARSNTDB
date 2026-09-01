# Glossary

Terms used in the SARSNTDB app and documentation.

---

## A

**Accession** – A unique identifier for a sequence or protein in a public database (e.g. NCBI). Stored in Gene_1, Protein_1 (e.g. YP_009724390.1).

**Amino acid (aa)** – Building block of proteins. Proteins are chains of amino acids. Positions along a protein are “amino acid positions” (Start_aa, End_aa in Protein_1).

**AJAX** – Technique where the browser requests data from the server (e.g. a PHP endpoint) without reloading the whole page. Used for GenomeComparisonData.php, repeatData.php.

---

## C

**codon** – Group of three nucleotides that codes for one amino acid (or a stop signal) during translation.

**coordinates** – Positions along the genome (1-based: first nucleotide = 1). Start and End in the app are nucleotide coordinates.

**coronavirus** – Family of viruses that include SARS-CoV and SARS-CoV-2; named after the crown-like spike protein.

**cov_comp** – Database table storing SARS-CoV vs SARS-CoV-2 domain comparison (identities, positives, genome ranges for both viruses).

**COVID-19** – Disease caused by SARS-CoV-2.

---

## D

**domain** – A distinct structural or functional region within a protein (e.g. “Receptor Binding Domain” in the Spike protein). cov_comp stores domain names and ranges for SARS-CoV and SARS-CoV-2.

**DNA** – Deoxyribonucleic acid; uses bases A, T, G, C. SARS-CoV-2 has an RNA genome; “DNA sequence” in the app often means a string of A/T/G/C used to search the reference (which may be stored like DNA for simplicity).

---

## E

**endpoint** – A URL that returns data (JSON or HTML fragment) rather than a full page; e.g. GenomeComparisonData.php, repeatData.php.

**ORF (open reading frame)** – A stretch of genome that can be translated into protein. Genes like ORF1ab, ORF3a, ORF6, etc. are open reading frames.

---

## F

**FASTA** – Standard text format for sequences: a header line starting with `>`, then lines of letters (nucleotides or amino acids). reference.fasta holds the SARS-CoV-2 reference genome.

**frameshift** – Shifting how the sequence is read (e.g. -1 frameshift in ORF1ab), producing a longer or different protein.

---

## G

**gene** – A segment of the genome that encodes a product (usually a protein). In the app, each gene has a name (e.g. S Gene), Start and End on the genome, and often an associated Protein.

**Gene_1** – Main table for genes: name, Start, End, description, function, nucleotide sequence, and (in extended schema) Protein, Function, matchedcols.

**genome** – The complete genetic material of an organism or virus. SARS-CoV-2 genome is ~30,000 nucleotides of RNA.

**genome search** – Feature to search by coordinates (Start/End) or by DNA sequence; results show genes, domains, and repeats in range.

---

## M

**motif** – A short, recurring sequence pattern. In the app, “motif visualizer” (repeats) finds where a short nucleotide sequence appears in the genome.

**mutation** – A change in the genetic sequence compared to a reference (e.g. one base replaced by another). The Mutations feature shows mutation likelihood, frequency, and structure data.

**mysqli** – PHP extension used to connect to MySQL and run queries. connection.php creates a mysqli connection `$con`.

---

## N

**nucleotide** – Building block of DNA/RNA. The four bases in DNA are A, T, G, C; in RNA, T is replaced by U.

**Nsp (Nonstructural protein)** – Proteins (Nsp1–Nsp16) produced from the large ORF1ab gene; involved in viral replication and processing.

---

## P

**Protein_1** – Table storing proteins: name, gene, amino acid range (Start_aa, End_aa), description, function.

**protein** – A chain of amino acids; the product of a gene (after translation and often cleavage). Examples: Spike (S), Nucleocapsid (N), Nsp1–Nsp16.

**ProtienInfo.php** – Filename with intentional typo (“Protien”); required by GenomeSearch.php and others. Do not rename.

---

## R

**reference (genome)** – A standard sequence (e.g. Wuhan SARS-CoV-2) used as the baseline. Positions and “mutations” are often given relative to it. In the app, fastas/reference.fasta is the reference for sequence search.

**repeat** – A short nucleotide sequence that appears multiple times in the genome. The repeats feature (motif visualizer) and repeats table store sequences and their positions (coord).

**repeats (table)** – Database table: sequence, coord, SUPrepeats. Used by repeatData.php.

**repeatcoord** – Table used by GenomeResult.php for repeat data in a coordinate range.

**RNA** – Ribonucleic acid; uses A, U, G, C. SARS-CoV-2 has an RNA genome.

---

## S

**SARS-CoV** – Virus that caused the 2002–2004 SARS outbreak (also called SARS-CoV-1).

**SARS-CoV-2** – Virus that causes COVID-19; the main subject of SARSNTDB.

**sequence search** – Entering a DNA sequence (e.g. ACGAAC) in Start or End; the app finds all positions in reference.fasta and uses the first match for the gene/domain/repeat queries.

**Spike (S)** – Surface glycoprotein of SARS-CoV-2; binds to host cells and is a major vaccine and drug target.

---

## T

**translation** – Process by which the nucleotide sequence of a gene is read in codons and converted into an amino acid sequence (protein).

**1-based** – Coordinate system where the first nucleotide of the genome is position 1 (not 0). The app uses 1-based positions.

---

## V

**variant** – A virus lineage that differs from the reference by one or more mutations. Mutation data in the app can inform understanding of variants.

**viroporin** – Viral protein that forms channels in membranes (e.g. ORF3a, E protein).
