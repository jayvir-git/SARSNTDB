"""Parse checks for Jim's pad-bwa VCF SNV rules."""
from pathlib import Path
import sys
import zipfile

sys.path.insert(0, str(Path(__file__).resolve().parent))

from import_vcf_snv_groups import (
    MERGE_GROUPS,
    collect_group_sources,
    group_code_from_stem,
    group_label,
    load_list_file,
    parse_vcf_lines,
    sample_allowed,
    sample_name_from_filename,
)

MEETING_VCF = """##fileformat=VCFv4.2
##source=GROM, Version 1.0.3
##FORMAT=<ID=GT,Number=1,Type=String,Description="Genotype">
##FORMAT=<ID=AF,Number=1,Type=Float,Description="Allele frequency (high mapping quality reads)">
#CHROM	POS	ID	REF	ALT	QUAL	FILTER	INFO	FORMAT	SRR28345689_pad-bwa-sorted
MN908947.3	6021	.	C	T	80	.	.	GT:PR:AF:A:C:G:T	1/1:1.00e-08:1.00e+00:0:0:0:4
MN908947.3	6241	.	C	T	9999	.	.	GT:PR:AF:A:C:G:T	1/1:0.00e+00:9.91e-01:0:1:0:109
MN908947.3	6355	.	C	T	9999	.	.	GT:PR:AF:A:C:G:T	1/0:0.00e+00:2.08e-01:0:190:0:50
MN908947.3	100	.	A	G	9999	.	.	GT:PR:AF:A:C:G:T	1/1:0.00e+00:1.00e+00:0:0:0:4
MN908947.3	7000	.	ATG	A	9999	.	.	GT:PR:AF:A:C:G:T	1/1:0.00e+00:1.00e+00:0:0:0:4
"""


def test_meeting_examples():
    rows = list(parse_vcf_lines(MEETING_VCF.splitlines()))
    assert rows[0] == (21, "C", "T", 1.0)
    assert rows[1][0] == 241
    assert rows[1][1] == "C"
    assert rows[1][2] == "T"
    assert abs(rows[1][3] - 0.991) < 1e-9
    assert rows[2] == (355, "C", "T", 0.208)
    coords = [r[0] for r in rows]
    assert 100 - 6000 not in coords
    assert all(len(r[1]) == 1 and len(r[2]) == 1 for r in rows)


def test_sample_name_and_list():
    assert sample_name_from_filename("LA_all/SRR28345689_pad-bwa.SNV.vcf") == "SRR28345689"
    assert sample_allowed("ERR6594119", {"ERR6594119", "ERR6594122"}) is True
    assert sample_allowed("SRR1", {"ERR6594119"}) is False
    assert sample_allowed("SRR1", None) is True


def test_nj_merge_and_labels():
    assert group_code_from_stem("NJ-PRJNA708324-bwa-pad") == "NJ-PRJNA708324"
    assert group_code_from_stem("NJ-PRJNA708324-5-bwa-pad") == "NJ-PRJNA708324"
    assert group_code_from_stem("LA-PRJNA815364-bwa-pad") == "LA-PRJNA815364"
    assert group_code_from_stem("S_Afr-PRJNA636748") == "S_Afr-PRJNA636748"
    assert group_code_from_stem("S_Afr-PRJNA636748-1023-1024-bwa-pad") == "S_Afr-PRJNA636748"
    assert group_code_from_stem("Port-bwa-pad-0-8") == "Port-PRJEB47340"
    assert group_code_from_stem("Port_8-10-bwa-pad") == "Port-PRJEB47340"
    assert group_code_from_stem("Port_11-12-bwa-pad") == "Port-PRJEB47340"
    assert group_label("NJ-PRJNA708324") == "USA NJ PRJNA708324"
    assert group_label("PRJNA622837-Broad_Inst") == "USA NE, NJ PRJNA622837"
    assert group_label("LA-PRJNA815364") == "USA LA PRJNA815364"
    assert group_label("PRJEB46220-Argentina") == "Argentina PRJEB46220"
    assert group_label("S_Afr-PRJNA636748") == "South Africa MiSeq"
    assert group_label("Port-PRJEB47340") == "Portugal NextSeq 550"
    assert group_code_from_stem("India-6000-bwa-pad") == "India-6000"
    assert group_code_from_stem("India-6000-new-bwa-pad") == "India-6000"
    assert group_code_from_stem("PRJEB46220-Argentina-bwa-pad") == "PRJEB46220-Argentina"
    assert group_code_from_stem("PRJEB46220-Argentina-new-bwa-pad") == "PRJEB46220-Argentina"
    assert MERGE_GROUPS["India-6000"][-1] == "India-6000-new-bwa-pad"
    assert MERGE_GROUPS["PRJEB46220-Argentina"][-1] == "PRJEB46220-Argentina-new-bwa-pad"


def test_collect_merges_extra_zips(tmp_path: Path):
    for stem in (
        "S_Afr-PRJNA636748",
        "S_Afr-PRJNA636748-1023-1024-bwa-pad",
        "Port-bwa-pad-0-8",
        "Port_8-10-bwa-pad",
        "India-6000-bwa-pad",
        "India-6000-new-bwa-pad",
        "PRJEB46220-Argentina-bwa-pad",
        "PRJEB46220-Argentina-new-bwa-pad",
        "LA-PRJNA815364-bwa-pad",
    ):
        (tmp_path / (stem + ".zip")).write_bytes(b"PK\x05\x06" + b"\x00" * 18)
    grouped = collect_group_sources(tmp_path)
    sa = grouped["S_Afr-PRJNA636748"]
    assert [p.stem for p in sa] == [
        "S_Afr-PRJNA636748",
        "S_Afr-PRJNA636748-1023-1024-bwa-pad",
    ]
    port = grouped["Port-PRJEB47340"]
    assert [p.stem for p in port] == ["Port-bwa-pad-0-8", "Port_8-10-bwa-pad"]
    assert [p.stem for p in grouped["LA-PRJNA815364"]] == ["LA-PRJNA815364-bwa-pad"]
    assert [p.stem for p in grouped["India-6000"]] == [
        "India-6000-bwa-pad",
        "India-6000-new-bwa-pad",
    ]
    assert [p.stem for p in grouped["PRJEB46220-Argentina"]] == [
        "PRJEB46220-Argentina-bwa-pad",
        "PRJEB46220-Argentina-new-bwa-pad",
    ]


def test_keep_first_duplicate(tmp_path: Path):
    from import_vcf_snv_groups import import_groups

    first = MEETING_VCF
    second = MEETING_VCF.replace("\tC\tT\t80", "\tA\tG\t80", 1)
    stems = MERGE_GROUPS["S_Afr-PRJNA636748"]
    for i, stem in enumerate(stems):
        zip_path = tmp_path / (stem + ".zip")
        inner = stem + "/SAMP1_pad-bwa.SNV.vcf"
        with zipfile.ZipFile(zip_path, "w") as zf:
            zf.writestr(inner, first if i == 0 else second)
    stats = import_groups(tmp_path, only_group="S_Afr-PRJNA636748", apply=False)
    assert stats["S_Afr-PRJNA636748"]["duplicate_samples"] == 1
    assert stats["S_Afr-PRJNA636748"]["samples"] == 1
    assert stats["S_Afr-PRJNA636748"]["zips"] == 2


def test_list_file(tmp_path: Path):
    path = tmp_path / "list.txt"
    path.write_text("# comment\nERR1\n\nERR2\n", encoding="utf-8")
    assert load_list_file(path) == {"ERR1", "ERR2"}


def test_zip_roundtrip(tmp_path: Path):
    zip_path = tmp_path / "LA-PRJNA815364-bwa-pad.zip"
    inner = "LA-PRJNA815364-bwa-pad/SRR28345689_pad-bwa.SNV.vcf"
    with zipfile.ZipFile(zip_path, "w") as zf:
        zf.writestr(inner, MEETING_VCF)
    from import_vcf_snv_groups import iter_vcf_from_zip

    name, lines = next(iter_vcf_from_zip(zip_path))
    assert sample_name_from_filename(name) == "SRR28345689"
    rows = list(parse_vcf_lines(lines))
    assert rows[0][0] == 21


if __name__ == "__main__":
    testdir = Path(__file__).resolve().parent / "_tmp_vcf_test"
    testdir.mkdir(exist_ok=True)
    merge_dir = testdir / "merge_src"
    merge_dir.mkdir(exist_ok=True)
    test_meeting_examples()
    test_sample_name_and_list()
    test_nj_merge_and_labels()
    test_collect_merges_extra_zips(merge_dir)
    test_keep_first_duplicate(merge_dir)
    test_list_file(testdir)
    test_zip_roundtrip(testdir)
    print("import_vcf_snv_groups parse assertions passed")
