#!/usr/bin/env python3
"""
Convert a Sejda-exported certificate PDF into an FPDI-readable file.

Sejda saves PDFs with compressed cross-reference streams that FPDI's free
parser cannot read ("compression technique which is not supported"). This
rewrites the file with a classic xref table while keeping the page and the
`name` field intact.

Usage:
    pip install pikepdf
    python3 prepare_template.py  new-export.pdf  ../template/certificate.pdf
"""
import sys
import pikepdf

def main():
    if len(sys.argv) != 3:
        print("usage: python3 prepare_template.py INPUT.pdf OUTPUT.pdf")
        sys.exit(1)
    src, dst = sys.argv[1], sys.argv[2]
    pdf = pikepdf.open(src)
    pdf.save(dst,
             object_stream_mode=pikepdf.ObjectStreamMode.disable,
             linearize=True)
    print(f"Wrote FPDI-ready template: {dst}")

if __name__ == "__main__":
    main()
