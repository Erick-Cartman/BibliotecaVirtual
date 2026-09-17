"""Export the authoritative inventory sheet to deterministic seed JSON.

The source workbook is opened read-only and is never modified.
"""

from __future__ import annotations

import hashlib
import json
import re
import unicodedata
from pathlib import Path

from openpyxl import load_workbook


ROOT = Path(__file__).resolve().parents[1]
SOURCE = next(ROOT.glob("ACERVO* (2).xlsx"))
TARGET = ROOT / "biblioteca-activa" / "database" / "seeders" / "data" / "books.json"


def text(value) -> str:
    if value is None:
        return ""
    return " ".join(str(value).replace("\u00a0", " ").split()).strip()


def ascii_search(value: str) -> str:
    normalized = unicodedata.normalize("NFKD", value)
    return "".join(char for char in normalized if not unicodedata.combining(char)).lower()


def isbn(value) -> str:
    candidate = text(value)
    if candidate.endswith(".0"):
        candidate = candidate[:-2]
    return re.sub(r"[^0-9Xx]", "", candidate).upper()


def positive_int(value) -> int:
    try:
        return max(1, int(float(value)))
    except (TypeError, ValueError):
        return 1


def publication_year(value):
    match = re.search(r"(?:18|19|20)\d{2}", text(value))
    return int(match.group()) if match else None


def program(value, learning_unit: str) -> str:
    raw = ascii_search(text(value) or learning_unit).upper()
    if raw in {"ING", "INGENIERIA", "INGENIERÍA", "IN GENIERIA"}:
        return "Ingeniería"
    if raw in {"ADMIN", "ADMN", "ADMINISTRACION", "ADMINISTRACIÓN"}:
        return "Administración"
    if raw in {"ABOGADO", "DERECHO"}:
        return "Derecho"
    if raw in {"LITERATURA", "LETRAS"}:
        return "Literatura"
    return text(value).title() or "General"


def main() -> None:
    workbook = load_workbook(SOURCE, read_only=True, data_only=True)
    sheet = workbook["COMPLETO"]
    books: dict[str, dict] = {}

    for row_number, row in enumerate(sheet.iter_rows(min_row=2, values_only=True), start=2):
        dewey, learning, copies, title, authors, publisher, year, location, raw_isbn, raw_program = row[:10]
        title = text(title)
        if not title:
            continue

        author = text(authors)
        clean_isbn = isbn(raw_isbn)
        identity = clean_isbn or "|".join(
            [ascii_search(title), ascii_search(author), str(publication_year(year) or "")]
        )
        source_key = "acervo-" + hashlib.sha1(identity.encode("utf-8")).hexdigest()[:16]
        copy_count = positive_int(copies)
        clean_location = text(location) or "Por asignar"

        if source_key not in books:
            learning_unit = text(learning)
            record = {
                "source_key": source_key,
                "title": title,
                "authors": [author] if author else ["Autor no registrado"],
                "isbn": clean_isbn or None,
                "publisher": text(publisher) or None,
                "publication_year": publication_year(year),
                "dewey_code": text(dewey) or None,
                "learning_unit": learning_unit or None,
                "program": program(raw_program, learning_unit),
                "total_copies": 0,
                "locations": [],
                "source": {
                    "workbook": SOURCE.name,
                    "sheet": "COMPLETO",
                    "rows": [],
                },
            }
            books[source_key] = record

        record = books[source_key]
        record["total_copies"] += copy_count
        record["locations"].extend([clean_location] * copy_count)
        record["source"]["rows"].append(row_number)

    result = sorted(books.values(), key=lambda item: ascii_search(item["title"]))
    for item in result:
        searchable = " ".join(
            filter(
                None,
                [
                    item["title"],
                    " ".join(item["authors"]),
                    item["isbn"],
                    item["publisher"],
                    item["dewey_code"],
                    item["learning_unit"],
                    item["program"],
                ],
            )
        )
        item["search_text"] = ascii_search(searchable)

    TARGET.parent.mkdir(parents=True, exist_ok=True)
    TARGET.write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding="utf-8")
    workbook.close()
    print(json.dumps({"books": len(result), "copies": sum(b["total_copies"] for b in result), "target": str(TARGET)}, ensure_ascii=False))


if __name__ == "__main__":
    main()
