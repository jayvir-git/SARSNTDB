"""Classification checks for Jim's pango designation-marker rules."""
from import_pango_snv_markers import indel_kind, qualifies_indel_row, qualifies_snv_row


def test_snv_rule():
    assert qualifies_snv_row("G", "A", 0.19) is True
    assert qualifies_snv_row("G", "A", 0.2) is False
    assert qualifies_snv_row("GTG", "G", 0.0) is False


def test_indel_kind():
    assert indel_kind("AAAGTCATTT", "A") == "deletion"
    assert indel_kind("G", "GAACA") == "insertion"
    assert indel_kind("G", "A") is None
    assert indel_kind("AT", "GC") is None


def test_indel_ratio():
    assert qualifies_indel_row("AGTT", "A", 0.01) is True
    assert qualifies_indel_row("AGTT", "A", 0.2) is False
    assert qualifies_indel_row("G", "A", 0.0) is False


if __name__ == "__main__":
    test_snv_rule()
    test_indel_kind()
    test_indel_ratio()
    print("import_pango_snv_markers classification assertions passed")
