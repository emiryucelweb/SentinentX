#!/usr/bin/env python3
"""
SentinentX TODO/FIXME/HACK Sweeper (Python Version)
Robust scanner that eliminates false positives and focuses on actual violations
"""

import re
import sys
import argparse
from pathlib import Path
from typing import List, Tuple

# Project root directory
ROOT = Path(__file__).resolve().parents[1]

# Directories to exclude from scanning
EXCLUDE_DIRS = {
    '.git', 'vendor', 'node_modules', 'storage', 'reports', 'release', 
    'public/build', 'bootstrap/cache', '.phpunit.cache', 'coverage-html',
    '.github', 'docker', 'docs/build'
}

# File extensions to skip (binary/compiled)
SKIP_EXTENSIONS = {
    '.png', '.jpg', '.jpeg', '.gif', '.bmp', '.ico', '.svg',
    '.pdf', '.zip', '.tar', '.gz', '.7z', '.rar',
    '.exe', '.dll', '.so', '.dylib',
    '.ttf', '.woff', '.woff2', '.eot',
    '.mp3', '.mp4', '.avi', '.mov', '.webm',
    '.class', '.jar', '.war'
}

# Regex patterns
TODO_PATTERN = re.compile(r'\b(TODO|FIXME|HACK)\b', re.IGNORECASE)
ALLOWTODO_PATTERN = re.compile(r'\bALLOWTODO:\s*([A-Z]+-\d+)\s+(\d{4}-\d{2}-\d{2})\s+(.{10,100})', re.IGNORECASE)

def should_skip_file(file_path: Path) -> bool:
    """Check if file should be skipped based on path or extension."""
    # Check if any part of the path contains excluded directories
    path_parts = set(file_path.parts)
    if EXCLUDE_DIRS & path_parts:
        return True
    
    # Check file extension
    if file_path.suffix.lower() in SKIP_EXTENSIONS:
        return True
    
    # Skip hidden files except specific ones
    if file_path.name.startswith('.') and file_path.name not in {'.env.example', '.gitignore', '.htaccess'}:
        return True
    
    # Skip the old PHP todo sweeper (this script replaces it)
    if file_path.name == 'todo-sweeper.php':
        return True
        
    return False

def scan_file(file_path: Path) -> List[Tuple[int, str, str]]:
    """
    Scan a single file for TODO violations.
    Returns list of (line_number, violation_type, line_content)
    """
    violations = []
    
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            for line_num, line in enumerate(f, 1):
                line_stripped = line.strip()
                
                # Skip empty lines and pure comments
                if not line_stripped or line_stripped.startswith('#'):
                    continue
                
                # Look for TODO/FIXME/HACK patterns
                todo_match = TODO_PATTERN.search(line)
                if todo_match:
                    # Check if it's a valid ALLOWTODO format
                    if ALLOWTODO_PATTERN.search(line):
                        continue  # Valid ALLOWTODO, skip
                    
                    todo_type = todo_match.group(1).upper()
                    
                    # Skip false positives
                    line_lower = line.lower()
                    
                    # Skip if it's metadata/documentation about TODO system
                    if any(pattern in line_lower for pattern in [
                        'todo sweeper', 'todo comment', 'todo format', 'todo violation',
                        'todo-sweeper', 'allowtodo:', 'todo pattern', 'foundtodos',
                        'complianttodos', '"todo"', "'todo'", 'scan.*todo'
                    ]):
                        continue
                    
                    # Skip if "hack" is in security context
                    if todo_type == 'HACK' and any(pattern in line_lower for pattern in [
                        'security', 'breach', 'vulnerability', 'compromise', 'attack'
                    ]):
                        continue
                    
                    # Only count if it's actually a TODO comment (starts with comment markers)
                    if any(marker in line for marker in ['//', '#', '/*', '*', '--']):
                        violations.append((line_num, todo_type, line_stripped))
                    
    except (UnicodeDecodeError, PermissionError, IsADirectoryError):
        # Skip files that can't be read
        pass
    
    return violations

def scan_directory() -> List[Tuple[Path, int, str, str]]:
    """
    Scan entire project directory for TODO violations.
    Returns list of (file_path, line_number, violation_type, line_content)
    """
    all_violations = []
    files_scanned = 0
    
    for file_path in ROOT.rglob('*'):
        if not file_path.is_file():
            continue
            
        if should_skip_file(file_path):
            continue
            
        files_scanned += 1
        violations = scan_file(file_path)
        
        for line_num, violation_type, line_content in violations:
            # Make path relative to project root for cleaner output
            rel_path = file_path.relative_to(ROOT)
            all_violations.append((rel_path, line_num, violation_type, line_content))
    
    print(f"📊 Files scanned: {files_scanned}")
    return all_violations

def generate_report(violations: List[Tuple[Path, int, str, str]]) -> None:
    """Generate detailed violation report."""
    reports_dir = ROOT / 'reports'
    reports_dir.mkdir(exist_ok=True)
    
    report_file = reports_dir / 'todo_violations.txt'
    
    with open(report_file, 'w', encoding='utf-8') as f:
        f.write(f"TODO/FIXME/HACK Violations Report\n")
        f.write(f"Generated: {Path(__file__).name}\n")
        f.write(f"Total violations: {len(violations)}\n")
        f.write(f"="*50 + "\n\n")
        
        for file_path, line_num, violation_type, line_content in violations:
            f.write(f"{file_path}:{line_num}:{violation_type} - {line_content}\n")
    
    print(f"📋 Report generated: {report_file}")

def main():
    parser = argparse.ArgumentParser(description='SentinentX TODO Sweeper')
    parser.add_argument('--count-only', action='store_true', help='Only output violation count')
    parser.add_argument('--verbose', action='store_true', help='Verbose output')
    parser.add_argument('--fail-on-violations', action='store_true', help='Exit with code 1 if violations found')
    
    args = parser.parse_args()
    
    if not args.count_only:
        print("🧹 SentinentX TODO/FIXME/HACK Sweeper (Python) v2.0")
        print("=" * 55)
        print("🔍 Scanning for TODO/FIXME/HACK violations...")
        print("")
    
    violations = scan_directory()
    
    if args.count_only:
        print(len(violations))
    else:
        print(f"📊 Analysis Results:")
        print(f"   • Violations found: {len(violations)}")
        
        if violations:
            print(f"\n🚨 Violations:")
            
            # Group by violation type
            by_type = {}
            for file_path, line_num, violation_type, line_content in violations:
                if violation_type not in by_type:
                    by_type[violation_type] = []
                by_type[violation_type].append((file_path, line_num, line_content))
            
            for violation_type, items in by_type.items():
                print(f"\n{violation_type} ({len(items)} items):")
                for file_path, line_num, line_content in items[:5]:  # Show first 5
                    print(f"   • {file_path}:{line_num} - {line_content[:80]}...")
                if len(items) > 5:
                    print(f"   • ... and {len(items) - 5} more")
            
            generate_report(violations)
            print(f"\n❌ TODO Sweeper FAILED: Found {len(violations)} violations")
            print("\nRequired format:")
            print("// ALLOWTODO: JIRA-123 2025-08-27 Single sentence reason")
        else:
            print("\n✅ TODO Sweeper PASSED: No violations found")
    
    # Exit with appropriate code
    if violations and args.fail_on_violations:
        sys.exit(1)
    elif violations:
        sys.exit(1)  # Default behavior: fail on violations
    else:
        sys.exit(0)

if __name__ == '__main__':
    main()
