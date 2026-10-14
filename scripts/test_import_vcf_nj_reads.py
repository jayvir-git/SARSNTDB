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
