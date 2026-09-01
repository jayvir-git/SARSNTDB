# Biology primer (no background assumed)

This document explains the biological concepts you need to use and understand SARSNTDB. No prior biology knowledge is assumed.

---

## 1. What is a virus?

A **virus** is a tiny infectious agent that can only multiply inside the cells of another organism (the **host**). Unlike bacteria, viruses do not have their own cell; they carry genetic instructions (DNA or RNA) and use the host’s machinery to make more copies of themselves. **SARS-CoV-2** is the virus that causes the disease COVID-19.

---

## 2. DNA and RNA: the “letters” of life

### Nucleotides and bases

- **DNA** (deoxyribonucleic acid) and **RNA** (ribonucleic acid) are molecules that store genetic information.
- They are long chains made of building blocks called **nucleotides**. Each nucleotide has a **base**.
- In **DNA**, the four bases are: **A** (adenine), **T** (thymine), **G** (guanine), **C** (cytosine). We often write DNA as a string of these letters, e.g. `ATGCGA`.
- In **RNA**, **T** is replaced by **U** (uracil). So RNA uses **A, U, G, C**.
- SARS-CoV-2 has an **RNA genome** (its genetic material is RNA, not DNA). In the database and in many tools, viral RNA is still sometimes written with **T** instead of **U** when referring to “nucleotide” sequence; the important part is the order of the bases.

When we say **“nucleotide sequence”** or **“DNA/RNA sequence”**, we mean this string of letters (e.g. `ACGAACTT`). When we say **“nucleotide coordinates”** or **“positions”**, we mean the position of a base or a stretch of sequence along the genome (e.g. position 1, 2, 3, … from the start of the genome).

---

## 3. Genome and genes

### Genome

- The **genome** is the complete set of genetic instructions of an organism (or virus). For SARS-CoV-2, it is one long RNA molecule of about **30,000 nucleotides** (often written as ~30 kb).
- Think of the genome as a long string of letters (A, T, G, C or A, U, G, C). Each position (1, 2, 3, … 29903) is a **coordinate** along that string.

### Gene

- A **gene** is a segment of the genome that carries the information to make a **product** (usually a protein, sometimes a functional RNA). So a gene has a **start** and an **end** position on the genome.
- Example: the **S gene** (Spike gene) might run from position **21563** to **25384** on the SARS-CoV-2 genome. So when the app says “Start: 21563, End: 25384”, it is referring to this stretch of the genome.
- SARS-CoV-2 has many genes: **ORF1ab**, **S**, **ORF3a**, **E**, **M**, **N**, **ORF6**, **ORF7a**, **ORF7b**, **ORF8**, **ORF10**, etc. **ORF** means “open reading frame” (a region that can be read and translated into protein). The app lists them in the Gene/Protein dropdown.

---

## 4. From gene to protein: translation

### How a gene becomes a protein

- The sequence of a **gene** (nucleotides) is **translated** into a sequence of **amino acids**, which then fold into a **protein**.
- **Translation** reads the gene in groups of three nucleotides (each group is a **codon**). Each codon corresponds to one amino acid (or a “stop” signal). So the **nucleotide sequence** of a gene determines the **amino acid sequence** of the protein.
- **Proteins** are the workhorses of the cell (and of the virus): they form structures (e.g. the Spike on the virus surface) and carry out functions (e.g. copying the viral RNA).

### Amino acids and protein sequence

- There are 20 standard **amino acids**. They are often written as one-letter codes (e.g. M, V, L, K) or three-letter codes (Met, Val, Leu, Lys). A **protein sequence** is a string of these letters.
- When the app or database refers to **“amino acid position”**, **“aa”**, or **“Start_aa / End_aa”**, it means the position along the **protein** sequence (e.g. residue 1, 2, 3 …), not the genome.
- A **domain** is a part of a protein that has a distinct structure or function (e.g. “Receptor Binding Domain” in the Spike protein).

So in SARSNTDB:

- **Gene** = a region on the **genome** (nucleotide coordinates: Start, End).
- **Protein** = the product of a gene (amino acid sequence; often with Start_aa, End_aa in the database).
- One gene usually gives one or more proteins (e.g. ORF1ab gives many “Nsp” proteins after cleavage).

---

## 5. SARS-CoV-2 in simple terms

- **SARS-CoV-2** = Severe Acute Respiratory Syndrome Coronavirus 2. It is a **coronavirus** (named after the crown-like “spike” on its surface).
- Its genome is a single strand of **RNA** (~30,000 nucleotides). The virus enters human cells and uses the cell’s machinery to copy this RNA and to make viral proteins; those proteins and the new RNA are assembled into new virus particles.
- Important viral **proteins** you will see in the app:
  - **Spike (S)** – on the surface; binds to human cells and helps the virus enter.
  - **Nucleocapsid (N)** – wraps and protects the viral RNA.
  - **Membrane (M)** and **Envelope (E)** – structural proteins.
  - **Nsp1–Nsp16** – non-structural proteins from the large ORF1ab gene; involved in replication, cutting the viral polyprotein, etc.
  - **ORF3a, ORF6, ORF7a, ORF7b, ORF8, ORF10** – accessory proteins with various roles (e.g. affecting immune response).

---

## 6. Mutations and variants

### What is a mutation?

- A **mutation** is a change in the genetic sequence compared to a **reference** (e.g. the original Wuhan reference genome). For example, at position 23403 an **A** might be replaced by **G** in some virus samples.
- Mutations can be:
  - **Single nucleotide change** (e.g. A→G): often written like **23403A>G** or “position 23403, A to G”.
  - **Insertion** (extra nucleotides inserted).
  - **Deletion** (nucleotides removed).

### Why mutations matter

- Mutations can change the **amino acid** in a protein (if the codon changes) and thus change how the protein works. They can make the virus more transmissible, less sensitive to some treatments, or change how the immune system recognizes it.
- In SARSNTDB, the **Mutations** section shows data about mutation likelihood, frequency, and RNA structure (e.g. from experiments or predictions) for regions of the genome or for specific genes/proteins.

---

## 7. Coordinates: 1-based and ranges

- **1-based coordinates** mean the first nucleotide of the genome is position **1**, not 0. SARSNTDB and many biology tools use 1-based positions.
- A **range** is given as **Start** and **End**. For example, Start=21563, End=25384 means “from nucleotide 21563 to nucleotide 25384, inclusive”.
- When you search by **coordinates** in the app, you enter Start and/or End (numbers). When you search by **sequence**, you enter a string of letters (e.g. `ACGAAC`); the app finds where that sequence appears in the reference genome and uses those positions for further queries.

---

## 8. FASTA format

- **FASTA** is a standard text format for storing nucleotide or amino acid sequences.
  - A line starting with **`>`** is the **header** (e.g. name or ID of the sequence).
  - The following lines are the **sequence** (letters only; line breaks are ignored when reading the sequence).
- Example:
  ```
  >SARS-CoV-2_reference
  ATGGAGAGCCTTGTCCCTGGTTTCAACGAGAAAACACACGTCCAACTCAGTTTGCCTGTTTTACAGGTTC
  GCGACGTGCTCGTACGTGGCTTTGGAGACTCCGTGGAGGAGGTCTTATCAGAGGCACGTCAACATCTTAA
  ```
- The app uses **`fastas/reference.fasta`** as the reference genome for **sequence search**: it looks for your input sequence (e.g. `ACGAAC`) inside this file and returns the matching positions.

---

## 9. Repeats and motifs

- A **repeat** (or **motif**) here is a short nucleotide sequence that appears **more than once** in the genome (e.g. `ACGAAC`).
- Finding repeats helps researchers study:
  - Repeated patterns that might affect structure or regulation.
  - Where certain short sequences sit relative to genes.
- In SARSNTDB, the **Repeats** (motif visualizer) feature lets you type a short sequence and see **where** it occurs (coordinates) and in **which genes** (if the position falls inside a gene’s Start–End range).

---

## 10. SARS-CoV vs SARS-CoV-2 (comparison in the app)

- **SARS-CoV** (SARS-CoV-1) caused the 2002–2004 SARS outbreak. **SARS-CoV-2** is the virus that causes COVID-19. They are related but different.
- The app’s **Genome comparison** feature compares **structural domains** and **sequences** between SARS-CoV and SARS-CoV-2 (e.g. the same protein domain in both viruses, with identity/positive percentages). This helps understand what is conserved and what changed between the two viruses.

---

## 11. Glossary (quick lookup)

- **Accession** – A unique identifier for a sequence or protein in a database (e.g. NCBI accession).
- **Codon** – A group of three nucleotides that codes for one amino acid (or stop).
- **Domain** – A distinct structural/functional region within a protein.
- **Gene** – A segment of the genome that encodes a product (e.g. protein).
- **Genome** – The full genetic material (here, the ~30 kb RNA of SARS-CoV-2).
- **Nucleotide** – The building block of DNA/RNA (A, T, G, C in DNA; A, U, G, C in RNA).
- **ORF** – Open reading frame; a region that can be translated into protein.
- **Protein** – A chain of amino acids; the product of a gene (after translation and often cleavage).
- **Reference (genome)** – A standard sequence used for comparison (e.g. Wuhan SARS-CoV-2); positions and “mutations” are often given relative to it.
- **Translation** – The process of reading mRNA (or viral RNA) and building a protein from amino acids.

You can also use the [Reference: Glossary](../07-reference/02-glossary.md) for more terms used in the app and docs.
