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
expect(parse_coord_name("Ang_miseq_50_11565_coord.csv") is None, "extra start coordinate skipped")
expect(parse_coord_name("Ang_miseq_894_coord1.csv") is None, "coord1 skipped")
expect(parse_coord_name("LA_all_102_coord.csv") == ("LA-PRJNA815364", 102, ""), "LA simple file")
import import_vcf_nj_reads as njimp
njimp.ALLOWED = {(2766, ""), (2766, "1883"), (118, "29686"), (118, "29687")}
expect(parse_coord_name("LA_all_2766_coord.csv") == ("LA-PRJNA815364", 2766, ""), "simple 2766 file")
expect(parse_coord_name("LA_all_2766_1883_coord.csv") == ("LA-PRJNA815364", 2766, "1883"), "row 69 uses 2766_1883")
expect(parse_coord_name("LA_all_118_29686_coord.csv") == ("LA-PRJNA815364", 118, "29686"), "duplicate 118 uses its start")
expect(parse_coord_name("LA_all_50_11565_coord.csv") is None, "untagged extra number stays out")
njimp.ALLOWED = set()

if failed:
    print(failed, "failed")
    sys.exit(1)
print("all passed")
