"""Eligibility decision and ID-matching checks. Does not write to MySQL."""
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parent))

from import_vcf_early_samples import EARLY_BY_CODE, ids_in_text
from import_vcf_sample_metadata import GROUP_PROJECT, ISOLATED_GROUPS, SKIP_PROJECTS, build_rows, decide_eligibility


def test_decide():
    assert decide_eligibility("PASS", True) == (True, None)
    assert decide_eligibility("FAIL_QC", True) == (False, "fail_qc")
    assert decide_eligibility(None, True) == (False, "unmatched")
    assert decide_eligibility("PASS", False) == (False, "no_vcf")
    assert decide_eligibility("", True) == (False, "unmatched")


def test_skip_isolated():
    assert "PRJNA622837" not in SKIP_PROJECTS
    assert "PRJEB47340" not in SKIP_PROJECTS
    assert ISOLATED_GROUPS == ()
    assert GROUP_PROJECT["PRJNA622837-Broad_Inst"] == "PRJNA622837"
    assert GROUP_PROJECT["PRJEB46220-Argentina"] == "PRJEB46220"
    assert GROUP_PROJECT["Port-PRJEB47340"] == "PRJEB47340"
    assert GROUP_PROJECT["S_Afr-PRJNA636748"] == "PRJNA636748"
    assert GROUP_PROJECT["Angola_miseq"] == "PRJNA717113"
    assert GROUP_PROJECT["Botswana"] == "PRJNA782796"
    assert GROUP_PROJECT["Slovakia_miseq"] == "PRJEB45305"


def test_build_rows_match_by_id_not_project():
    vcf = {
        "SRR1": [(10, 1, "PAK_iseq")],
        "SRR2": [(11, 1, "PAK_iseq")],
        "SRR3": [(12, 1, "PAK_iseq")],
        "FOREIGN": [(13, 1, "PAK_iseq")],
    }
    meta = {
        "SRR1": {
            "project": "PRJNA764553",
            "variant": "Delta",
            "primer": "COVID-ARTIC-V3",
            "qc": "PASS",
            "source": "run_metadata.v05-PRJNA764553.csv",
        },
        "SRR2": {
            "project": "PRJNA764553",
            "variant": ".",
            "primer": "COVID-ARTIC-V3",
            "qc": "FAIL_QC",
            "source": "run_metadata.v05-PRJNA764553.csv",
        },
        "SRR3": {
            "project": "PRJNA622837",
            "variant": "Delta",
            "primer": "COVID-ARTIC-V3",
            "qc": "PASS",
            "source": "run_metadata.v05-PRJNA622837.csv",
        },
    }
    _meta_sql, elig_sql, stats = build_rows(vcf, meta)
    s = stats["PAK_iseq"]
    assert s["vcf"] == 4
    assert s["in_meta"] == 2
    assert s["eligible"] == 1
    assert s["fail_qc"] == 1
    assert s["unmatched"] == 2
    assert any(",1," in row and "'PASS'" in row for row in elig_sql)


def test_early_lists():
    assert ids_in_text("SRR1\n\n# note\nSRR1\nSRR2\n") == ["SRR1", "SRR2"]
    assert EARLY_BY_CODE["Angola_miseq"] == "Angola_miseq-2020-Jul21.txt"
    assert EARLY_BY_CODE["Slovakia_miseq"] == "Slovakia_2020-2021.txt"
    assert "LA-PRJNA815364" not in EARLY_BY_CODE
    assert EARLY_BY_CODE["LA-PRJNA815364_nextseq500"] == "LA_nextseq500-2020-Jul21.txt"
    assert "Botswana" not in EARLY_BY_CODE
    assert EARLY_BY_CODE["NM"] == "NM-2020-Jul21.txt"
    assert EARLY_BY_CODE["VA"] == "VA-2020-Jul21.txt"
    assert EARLY_BY_CODE["Russia682735"] == "Russia682735_hiseq_2500.txt"


if __name__ == "__main__":
    test_decide()
    test_skip_isolated()
    test_build_rows_match_by_id_not_project()
    test_early_lists()
    print("import_vcf_sample_metadata assertions passed")
