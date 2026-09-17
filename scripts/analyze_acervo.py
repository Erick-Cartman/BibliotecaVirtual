"""Inspect the provided inventory workbooks without modifying them."""

from __future__ import annotations

import json
from pathlib import Path

from openpyxl import load_workbook


ROOT = Path(__file__).resolve().parents[1]


def clean(value):
    if value is None:
        return None
    if isinstance(value, str):
        return " ".join(value.split())
    return value


def inspect_workbook(path: Path) -> dict:
    workbook = load_workbook(path, read_only=True, data_only=False)
    sheets = []

    for sheet in workbook.worksheets:
        nonempty_rows = []
        nonempty_cells = 0
        max_used_column = 0
        formula_count = 0

        for row_number, row in enumerate(sheet.iter_rows(), start=1):
            values = [clean(cell.value) for cell in row]
            if any(value not in (None, "") for value in values):
                last_column = max(
                    index
                    for index, value in enumerate(values, start=1)
                    if value not in (None, "")
                )
                nonempty_cells += sum(value not in (None, "") for value in values)
                formula_count += sum(
                    isinstance(value, str) and value.startswith("=") for value in values
                )
                max_used_column = max(max_used_column, last_column)
                if len(nonempty_rows) < 15:
                    nonempty_rows.append(
                        {
                            "row": row_number,
                            "values": values[:last_column],
                        }
                    )

        sheets.append(
            {
                "name": sheet.title,
                "reported_dimension": sheet.calculate_dimension(),
                "nonempty_cells": nonempty_cells,
                "max_used_column": max_used_column,
                "formula_count": formula_count,
                "sample_rows": nonempty_rows,
            }
        )

    workbook.close()
    return {
        "file": path.name,
        "bytes": path.stat().st_size,
        "modified": path.stat().st_mtime,
        "sheets": sheets,
    }


def main() -> None:
    files = sorted(ROOT.glob("ACERVO*.xlsx"))
    result = [inspect_workbook(path) for path in files]
    print(json.dumps(result, ensure_ascii=False, indent=2, default=str))


if __name__ == "__main__":
    main()

