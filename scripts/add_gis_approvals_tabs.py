#!/usr/bin/env python3
"""Add Overview/Approvals Tabs to all GIS pages, matching Stations/Thresholds pattern.

Idempotent: skips files that already contain `value="approvals"`.
"""
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
GIS_DIR = ROOT / "resources" / "js" / "Pages" / "Gis"
FILES = ["FlowLevels.tsx", "DamLevels.tsx", "Groundwater.tsx", "Rainfall.tsx", "WaterQuality.tsx"]

HEADER_FLEX_OPEN = '                <Flex justify="space-between" align="center" mb="xl">'
HEADER_FLEX_CLOSE = '                </Flex>'
PENDING_OPEN = '                {canManage && pendingQueue.length > 0 && ('
PENDING_CLOSE = '                )}'
CONTAINER_CLOSE = '            </Container>'

TABS_OPEN = '''                <Tabs defaultValue="overview" keepMounted={false} variant="outline" radius="md">
                    <Tabs.List mb="xl">
                        <Tabs.Tab value="overview" leftSection={<IconList size={18} />}>
                            {tApprovals('tabs.overview')}
                        </Tabs.Tab>
                        {canManage && (
                            <Tabs.Tab
                                value="approvals"
                                leftSection={<IconClipboardList size={18} />}
                                rightSection={
                                    pendingQueue.length > 0 ? (
                                        <Badge size="xs" color="red" variant="filled" circle>
                                            {pendingQueue.length}
                                        </Badge>
                                    ) : undefined
                                }
                            >
                                {tApprovals('tabs.approvals')}
                            </Tabs.Tab>
                        )}
                    </Tabs.List>

                    <Tabs.Panel value="overview">'''

APPROVALS_PANEL_HEAD = '''                    </Tabs.Panel>

                    <Tabs.Panel value="approvals">'''

APPROVALS_EMPTY = '''                        {pendingQueue.length === 0 && (
                            <Card withBorder radius="md" p="xl">
                                <Text c="dimmed" ta="center">{tApprovals('queue.noPending')}</Text>
                            </Card>
                        )}'''

TABS_CLOSE = '''                    </Tabs.Panel>
                </Tabs>'''


def find_line(lines, target):
    for i, line in enumerate(lines):
        if line == target:
            return i
    return -1


def find_pending_block_end(lines, start_idx):
    """From the `{canManage && pendingQueue.length > 0 && (` line, find the matching `)}` at 16-space indent."""
    for i in range(start_idx + 1, len(lines)):
        if lines[i] == PENDING_CLOSE:
            return i
    return -1


def add_to_import(content, module, names_to_add):
    """Add names to a named import statement for the given module."""
    # Match `import { ... } from 'module';` possibly multi-line
    pattern = re.compile(
        r"import\s*\{([^}]*)\}\s*from\s*['\"]" + re.escape(module) + r"['\"];",
        re.MULTILINE,
    )
    m = pattern.search(content)
    if not m:
        print(f"  WARN: could not find import from {module}")
        return content
    existing_raw = m.group(1)
    existing_names = {n.strip() for n in existing_raw.split(",") if n.strip()}
    to_add = [n for n in names_to_add if n not in existing_names]
    if not to_add:
        return content
    new_names = sorted(existing_names | set(names_to_add))
    new_block = "import {\n    " + ",\n    ".join(new_names) + ",\n} from '" + module + "';"
    return content[:m.start()] + new_block + content[m.end():]


def process(path: Path):
    print(f"Processing {path.name}")
    text = path.read_text()
    if 'value="approvals"' in text:
        print("  skip: already has approvals tab")
        return False

    # 1. Add imports
    text = add_to_import(text, "@mantine/core", ["Tabs", "Badge", "Text"])
    text = add_to_import(text, "@tabler/icons-react", ["IconClipboardList", "IconList"])

    # 2. Add tApprovals translation hook after the existing useTranslation('gis') line
    text = re.sub(
        r"(\s+const \{ t \} = useTranslation\('gis'\);\n)",
        r"\1    const { t: tApprovals } = useTranslation('approvals');\n",
        text,
        count=1,
    )

    # Now line-level edits
    lines = text.split("\n")

    # 3. Find header Flex close (first occurrence at 16-space indent after the open)
    flex_open_idx = find_line(lines, HEADER_FLEX_OPEN)
    if flex_open_idx < 0:
        print("  ERR: header Flex open not found")
        return False
    flex_close_idx = -1
    for i in range(flex_open_idx + 1, len(lines)):
        if lines[i] == HEADER_FLEX_CLOSE:
            flex_close_idx = i
            break
    if flex_close_idx < 0:
        print("  ERR: header Flex close not found")
        return False

    # 4. Find pending queue block
    pending_open_idx = find_line(lines, PENDING_OPEN)
    if pending_open_idx < 0:
        print("  ERR: pending queue open not found")
        return False
    pending_close_idx = find_pending_block_end(lines, pending_open_idx)
    if pending_close_idx < 0:
        print("  ERR: pending queue close not found")
        return False

    # Extract pending block inner content (lines between PENDING_OPEN and PENDING_CLOSE exclusive)
    pending_inner = lines[pending_open_idx + 1 : pending_close_idx]

    # 5. Find Container close
    container_close_idx = -1
    for i in range(len(lines) - 1, -1, -1):
        if lines[i] == CONTAINER_CLOSE:
            container_close_idx = i
            break
    if container_close_idx < 0:
        print("  ERR: Container close not found")
        return False

    # Sanity: order
    if not (flex_close_idx < pending_open_idx < pending_close_idx < container_close_idx):
        print("  ERR: lines not in expected order")
        return False

    # 6. Build new lines
    new_lines = []
    # everything up to and including header </Flex>
    new_lines.extend(lines[: flex_close_idx + 1])
    # blank line + Tabs opener
    new_lines.append("")
    new_lines.append(TABS_OPEN)
    # original content from after </Flex> up to (but not including) pending block
    # skipping the comment line that may precede the pending block (the `{/* VERIFICATION INBOX */}`)
    middle_start = flex_close_idx + 1
    middle_end = pending_open_idx  # exclude pending block start

    # Optionally strip a trailing comment immediately before pending block
    # The line at pending_open_idx - 1 may be a comment about verification inbox.
    # We'll skip blank+comment lines immediately preceding pending block from the middle.
    while middle_end > middle_start and (
        lines[middle_end - 1].strip() == ""
        or lines[middle_end - 1].lstrip().startswith("{/*")
    ):
        middle_end -= 1

    new_lines.extend(lines[middle_start:middle_end])

    # close overview panel and open approvals panel
    new_lines.append(APPROVALS_PANEL_HEAD)
    new_lines.append(APPROVALS_EMPTY)
    # Approvals content: wrap the pending block with canManage gating but no length>0 check
    # because we show empty state separately. The original block was `{canManage && pendingQueue.length > 0 && (...)}`.
    # Re-emit as `{canManage && pendingQueue.length > 0 && ( ... )}` to keep the same Card UI.
    new_lines.append(PENDING_OPEN)
    new_lines.extend(pending_inner)
    new_lines.append(PENDING_CLOSE)

    # close approvals panel and Tabs
    new_lines.append(TABS_CLOSE)

    # content from after pending block to Container close (exclusive)
    after_pending_start = pending_close_idx + 1
    new_lines.extend(lines[after_pending_start:container_close_idx])

    # NOTE: above appends content that was originally AFTER the pending block (e.g., historical table)
    # but those should go INSIDE the overview panel, BEFORE the </Tabs.Panel>. We need to fix ordering.

    # ---- restart with a cleaner approach ----
    new_lines = []
    new_lines.extend(lines[: flex_close_idx + 1])
    new_lines.append("")
    new_lines.append(TABS_OPEN)

    # overview content = middle (lines between </Flex>+1 and pending_open_idx) MINUS trailing comment
    middle_end = pending_open_idx
    while middle_end > flex_close_idx + 1 and (
        lines[middle_end - 1].strip() == ""
        or lines[middle_end - 1].lstrip().startswith("{/*")
    ):
        middle_end -= 1
    new_lines.extend(lines[flex_close_idx + 1 : middle_end])

    # plus content AFTER pending block up to Container close
    # but skip the comment line that introduces the historical table? Keep as-is.
    new_lines.extend(lines[pending_close_idx + 1 : container_close_idx])

    # close overview, open approvals
    new_lines.append(APPROVALS_PANEL_HEAD)
    new_lines.append(APPROVALS_EMPTY)
    new_lines.append(PENDING_OPEN)
    new_lines.extend(pending_inner)
    new_lines.append(PENDING_CLOSE)
    new_lines.append(TABS_CLOSE)

    # Container close + rest
    new_lines.extend(lines[container_close_idx:])

    path.write_text("\n".join(new_lines))
    print("  OK")
    return True


def main():
    changed = 0
    for name in FILES:
        if process(GIS_DIR / name):
            changed += 1
    print(f"\nDone. Changed {changed}/{len(FILES)} files.")


if __name__ == "__main__":
    main()
