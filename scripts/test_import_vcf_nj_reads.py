"""Filename and sample-list rules for the 22 Sep NJ import."""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from import_vcf_nj_reads import parse_coord_name, sample_id_from_list_line

failed = 0


def expect(cond, msg):
    global failed
    if not cond:
        print("FAIL", msg)
        failed += 1
    else:
        print("ok  ", msg)


expect(
    sample_id_from_list_line(
        "SRR17013322-bbmap-sorted-D-0-sorted-D_min_0-unique.csv"
    )
    == "SRR17013322",
    "strip sample-list suffix",
)
expect(
    parse_coord_name("SAfr_miseq_0325_17943_coord.csv") == ("S_Afr-PRJNA636748", 17943, ""),
    "simple coord name",
)
expect(
    parse_coord_name("India_6000_all_28190_coord.csv") == ("India-6000", 28190, ""),
    "Drive India underscore base",
)
expect(parse_coord_name("India-6000_all_28190_coord.csv") == ("India-6000", 28190, ""), "sheet hyphen also maps")
expect(parse_coord_name("Ang_miseq_40_1001_coord.csv") is None, "extra start coordinate skipped")
expect(parse_coord_name("Ang_miseq_894_coord1.csv") is None, "coord1 skipped")
expect(parse_coord_name("PRJNA656534-NM_894_coord.csv") == ("NM", 894, ""), "New Mexico simple file")
expect(parse_coord_name("PRJNA625551-VA_894_coord.csv") == ("VA", 894, ""), "Virginia simple file")
expect(parse_coord_name("Austr_nextseq500_894_coord.csv") == ("Austr-PRJNA613958_nextseq500", 894, ""), "Australia 500 simple file")
expect(parse_coord_name("Austr_nextseq550_894_coord.csv") == ("Austr-PRJNA613958_nextseq550", 894, ""), "Australia 550 simple file")
expect(parse_coord_name("LA_all_102_coord.csv") == ("LA-PRJNA815364", 102, ""), "LA simple file")
expect(parse_coord_name("Broad_all_uniq_1012_894_coord.csv") == ("PRJNA622837-Broad_Inst", 894, ""), "12 Oct Broad base")
expect(parse_coord_name("Denmark_nextseq_550_894_coord.csv") == ("Denmark-nextseq550", 894, ""), "Denmark 550 base")
expect(parse_coord_name("PRJEB72496-Denm-6000_894_coord.csv") == ("PRJEB72496-Denmark-6000", 894, ""), "Denmark 6000 base")
expect(parse_coord_name("PRJNA746690-CA_894_coord.csv") == ("PRJNA746690-CA", 894, ""), "California base")
expect(parse_coord_name("PRJNA744530-RI_894_coord.csv") == ("PRJNA744530-RI", 894, ""), "Rhode Island base")
expect(parse_coord_name("Broad_6000_894_coord.csv") == ("PRJNA622837-Broad_Inst", 894, ""), "old Broad base still maps")
import import_vcf_nj_reads as njimp
njimp.ALLOWED = {(0, ""), (0, "0"), (0, "0"), (0, "0")}
expect(parse_coord_name("LA_all_2766_coord.csv") == ("LA-PRJNA815364", 2766, ""), "simple 2766 file")
expect(parse_coord_name("LA_all_0_0_coord.csv") == ("LA-PRJNA815364", 0, "0"), "row 69 uses 0_0")
expect(parse_coord_name("LA_all_0_0_coord.csv") == ("LA-PRJNA815364", 0, "0"), "duplicate 0 uses its start")
expect(parse_coord_name("LA_all_40_1001_coord.csv") is None, "untagged extra number stays out")
# 21 Oct: small deletions and canonical junctions come from Jim's lists in the packet (D-80).
# Made-up numbers here: junction coordinates stay out of this public repo.
import tempfile
with tempfile.TemporaryDirectory() as tmp:
    small_csv = Path(tmp) / "small.csv"
    canonical_csv = Path(tmp) / "canonical.csv"
    small_csv.write_text("40,1001,1040\n41,2001,2041\n", encoding="utf-8")
    canonical_csv.write_text("\ufeff5001,60,5060\n", encoding="utf-8")
    rows = njimp.small_canonical_rows(small_csv, canonical_csv)
expect(rows[0] == (40, 1001, 1040, "1001", "nj"), "small deletion keyed by size, tagged by start")
expect(rows[2] == (5001, 5001, 5060, "", "cj"), "canonical junction keyed by start")
njimp.ALLOWED = {(r[0], r[3]) for r in rows}
expect(parse_coord_name("NJ_all_40_1001_coord.csv") == ("NJ-PRJNA708324", 40, "1001"), "small deletion file loads")
expect(parse_coord_name("UT_6000_5001_coord.csv") == ("UT_6000", 5001, ""), "Utah canonical file loads")
njimp.ALLOWED = set()
# 21 Oct Zoom: TRS-L dependent junctions get an L tag; some of Jim's names carry a BOM (D-81).
expect(njimp.parse_trsl_name("NJ_all_﻿61_coord.csv") == ("NJ_all_", 61, "L"), "TRS-L name with BOM")
expect(njimp.parse_trsl_name("Argentina-uniq-all_0_0_coord.csv") == ("Argentina-uniq-all_", 0, "L0"), "TRS-L second number in the tag")
expect(njimp.parse_trsl_name("LA_500_miseq_635_coord.csv") == ("LA_500_miseq_", 635, "L"), "TRS-L LA base")
expect(njimp.parse_trsl_name("NJ_all_635_coord1.csv") is None, "TRS-L coord1 skipped")
import tempfile
with tempfile.TemporaryDirectory() as tmp:
    coord_csv = Path(tmp) / "trsl.csv"
    # Made-up numbers: junction coordinates stay out of this public repo.
    coord_csv.write_text("\ufeff40,11,50\n300,21,320\n300,22,321\n", encoding="utf-8")
    coords = njimp.trsl_coords([coord_csv])
expect(coords[(40, "L")] == (11, 50), "TRS-L size read with BOM")
expect(coords[(300, "L21")] == (21, 320) and coords[(300, "L22")] == (22, 321), "TRS-L repeated size keyed by start")

if failed:
    print(failed, "failed")
    sys.exit(1)
print("all passed")
