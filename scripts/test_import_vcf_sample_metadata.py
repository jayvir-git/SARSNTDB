"""Eligibility decision and ID-matching checks. Does not write to MySQL."""
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parent))

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
    assert "Angola_miseq" not in GROUP_PROJECT


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


if __name__ == "__main__":
    test_decide()
    test_skip_isolated()
    test_build_rows_match_by_id_not_project()
    print("import_vcf_sample_metadata assertions passed")
