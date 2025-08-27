#!/usr/bin/env php
<?php

/**
 * SentinentX TODO/FIXME/HACK Sweeper
 * Enforces consistent TODO comment formatting and generates compliance reports
 *
 * Required Format: // ALLOWTODO: <JIRA|ISSUE-ID> <YYYY-MM-DD> <single sentence reason>
 * Examples:
 *   // ALLOWTODO: SENTX-123 2025-02-15 Real position opening code needs implementation
 *   // ALLOWTODO: ISSUE-456 2025-01-30 Temporary workaround for API rate limiting
 */

declare(strict_types=1);

class TodoSweeper
{
    private const VERSION = '1.0.0';

    private const VALID_TODO_PATTERN = '/\/\/\s*ALLOWTODO:\s*([A-Z]+-\d+)\s+(\d{4}-\d{2}-\d{2})\s+(.{10,100})/';

    private const TODO_PATTERNS = [
        'TODO',
        'FIXME',
        'HACK',
        'XXX',
        'BUG',
        'NOTE',
        'REVIEW',
    ];

    private array $excludedPaths = [
        'vendor/',
        'node_modules/',
        '.git/',
        'storage/',
        'bootstrap/cache/',
        'public/build/',
        'coverage-html/',
        'deploy/ubuntu24/',
        'scripts/todo-sweeper.php',  // Don't scan self
    ];

    private array $includedExtensions = [
        'php', 'js', 'ts', 'vue', 'blade.php', 'css', 'scss', 'yaml', 'yml', 'json',
    ];

    private array $foundTodos = [];

    private array $violations = [];

    private int $totalFiles = 0;

    private int $scannedFiles = 0;

    public function __construct(
        private string $baseDir = '.',
        private string $reportDir = 'reports',
        private bool $strictMode = false,
        private bool $verbose = false
    ) {
        $this->baseDir = rtrim($baseDir, '/');
        $this->reportDir = rtrim($reportDir, '/');
    }

    /**
     * Main execution method
     */
    public function run(array $options = []): int
    {
        $this->printHeader();

        // Parse options
        $this->strictMode = $options['strict'] ?? false;
        $this->verbose = $options['verbose'] ?? false;
        $generateReport = $options['report'] ?? true;
        $exitOnViolation = $options['exit-on-violation'] ?? true;

        // Scan for TODOs
        $this->log('🔍 Scanning for TODO/FIXME/HACK comments...', 'info');
        $this->scanDirectory($this->baseDir);

        // Analyze results
        $this->analyzeResults();

        // Generate reports
        if ($generateReport) {
            $this->generateReports();
        }

        // Print summary
        $this->printSummary();

        // Determine exit code
        if ($exitOnViolation && count($this->violations) > 0) {
            $this->log('❌ TODO Sweeper FAILED: Found '.count($this->violations).' violations', 'error');

            return 1;
        }

        if (count($this->foundTodos) === 0) {
            $this->log('✅ TODO Sweeper PASSED: No TODO comments found', 'success');
        } else {
            $this->log('✅ TODO Sweeper PASSED: All '.count($this->foundTodos).' TODO comments are properly formatted', 'success');
        }

        return 0;
    }

    /**
     * Scan directory recursively for TODO comments
     */
    private function scanDirectory(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relativePath = str_replace($this->baseDir.'/', '', $file->getPathname());

            // Skip excluded paths
            if ($this->isExcludedPath($relativePath)) {
                continue;
            }

            // Check file extension
            if (! $this->hasIncludedExtension($file->getPathname())) {
                continue;
            }

            $this->totalFiles++;
            $this->scanFile($file->getPathname(), $relativePath);
        }
    }

    /**
     * Check if path should be excluded
     */
    private function isExcludedPath(string $path): bool
    {
        foreach ($this->excludedPaths as $excluded) {
            if (str_starts_with($path, $excluded)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if file has included extension
     */
    private function hasIncludedExtension(string $filename): bool
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return in_array($extension, $this->includedExtensions) ||
               str_ends_with($filename, '.blade.php');
    }

    /**
     * Scan individual file for TODO comments
     */
    private function scanFile(string $filePath, string $relativePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $lines = explode("\n", $content);
        $this->scannedFiles++;

        foreach ($lines as $lineNumber => $line) {
            $this->scanLine($line, $relativePath, $lineNumber + 1);
        }

        if ($this->verbose) {
            $this->log("📄 Scanned: $relativePath", 'debug');
        }
    }

    /**
     * Scan individual line for TODO patterns
     */
    private function scanLine(string $line, string $file, int $lineNumber): void
    {
        $trimmedLine = trim($line);

        // Check for any TODO pattern
        foreach (self::TODO_PATTERNS as $pattern) {
            if (stripos($trimmedLine, $pattern) !== false) {
                $this->processTodoLine($trimmedLine, $file, $lineNumber, $pattern);
                break; // Only process first match per line
            }
        }
    }

    /**
     * Process found TODO line
     */
    private function processTodoLine(string $line, string $file, int $lineNumber, string $pattern): void
    {
        // Check if it's a properly formatted ALLOWTODO
        if (preg_match(self::VALID_TODO_PATTERN, $line, $matches)) {
            $issueId = $matches[1];
            $date = $matches[2];
            $reason = trim($matches[3]);

            // Validate date
            $dateValid = $this->validateDate($date);
            $dateExpired = $this->isDateExpired($date);

            $todo = [
                'file' => $file,
                'line' => $lineNumber,
                'content' => $line,
                'pattern' => $pattern,
                'issue_id' => $issueId,
                'date' => $date,
                'reason' => $reason,
                'date_valid' => $dateValid,
                'date_expired' => $dateExpired,
                'compliant' => true,
            ];

            $this->foundTodos[] = $todo;

            // Add violations for date issues
            if (! $dateValid) {
                $this->violations[] = [
                    'type' => 'invalid_date',
                    'file' => $file,
                    'line' => $lineNumber,
                    'content' => $line,
                    'message' => "Invalid date format: $date",
                ];
            }

            if ($dateExpired) {
                $this->violations[] = [
                    'type' => 'expired_todo',
                    'file' => $file,
                    'line' => $lineNumber,
                    'content' => $line,
                    'message' => "TODO expired on $date",
                ];
            }

        } else {
            // Non-compliant TODO
            $violation = [
                'type' => 'non_compliant',
                'file' => $file,
                'line' => $lineNumber,
                'content' => $line,
                'pattern' => $pattern,
                'message' => 'TODO comment not in required format',
            ];

            $this->violations[] = $violation;
        }
    }

    /**
     * Validate date format (YYYY-MM-DD)
     */
    private function validateDate(string $date): bool
    {
        $dateTime = DateTime::createFromFormat('Y-m-d', $date);

        return $dateTime && $dateTime->format('Y-m-d') === $date;
    }

    /**
     * Check if date is expired (past current date)
     */
    private function isDateExpired(string $date): bool
    {
        try {
            $todoDate = new DateTime($date);
            $currentDate = new DateTime;

            return $todoDate < $currentDate;
        } catch (Exception $e) {
            return false; // Invalid dates handled separately
        }
    }

    /**
     * Analyze scanning results
     */
    private function analyzeResults(): void
    {
        $this->log('📊 Analysis Results:', 'info');
        $this->log("   • Files scanned: {$this->scannedFiles}/{$this->totalFiles}", 'info');
        $this->log('   • TODO comments found: '.count($this->foundTodos), 'info');
        $this->log('   • Violations found: '.count($this->violations), 'info');

        if ($this->verbose && count($this->violations) > 0) {
            $this->log("\n🚨 Violations:", 'warn');
            foreach ($this->violations as $violation) {
                $this->log("   • {$violation['file']}:{$violation['line']} - {$violation['message']}", 'warn');
            }
        }
    }

    /**
     * Generate comprehensive reports
     */
    private function generateReports(): void
    {
        if (! is_dir($this->reportDir)) {
            mkdir($this->reportDir, 0755, true);
        }

        $this->generateTodoRegister();
        $this->generateViolationReport();
        $this->generateSummaryReport();

        $this->log("📋 Reports generated in: {$this->reportDir}/", 'info');
    }

    /**
     * Generate TODO register markdown report
     */
    private function generateTodoRegister(): void
    {
        $reportPath = "{$this->reportDir}/todo_register.md";

        $content = "# TODO/FIXME/HACK Register\n\n";
        $content .= '**Generated**: '.date('Y-m-d H:i:s')."\n";
        $content .= '**Scanner Version**: '.self::VERSION."\n";
        $content .= "**Total Files Scanned**: {$this->scannedFiles}\n";
        $content .= '**TODO Comments Found**: '.count($this->foundTodos)."\n";
        $content .= '**Violations Found**: '.count($this->violations)."\n\n";

        $content .= "---\n\n";

        // Compliant TODOs
        $compliantTodos = array_filter($this->foundTodos, fn ($todo) => $todo['compliant']);

        if (count($compliantTodos) > 0) {
            $content .= "## ✅ Compliant TODO Comments\n\n";
            $content .= "| File | Line | Issue ID | Due Date | Status | Reason |\n";
            $content .= "|------|------|----------|----------|--------|--------|\n";

            foreach ($compliantTodos as $todo) {
                $status = $todo['date_expired'] ? '🔴 EXPIRED' : '🟢 ACTIVE';
                $content .= "| `{$todo['file']}` | {$todo['line']} | {$todo['issue_id']} | {$todo['date']} | $status | {$todo['reason']} |\n";
            }
            $content .= "\n";
        }

        // Violations
        if (count($this->violations) > 0) {
            $content .= "## ❌ TODO Violations\n\n";
            $content .= "| File | Line | Type | Content | Message |\n";
            $content .= "|------|------|------|---------|----------|\n";

            foreach ($this->violations as $violation) {
                $content .= "| `{$violation['file']}` | {$violation['line']} | {$violation['type']} | `".substr($violation['content'], 0, 50)."...` | {$violation['message']} |\n";
            }
            $content .= "\n";
        }

        // Required format documentation
        $content .= "## 📋 Required TODO Format\n\n";
        $content .= "All TODO/FIXME/HACK comments must follow this format:\n\n";
        $content .= "```\n";
        $content .= "// ALLOWTODO: <JIRA|ISSUE-ID> <YYYY-MM-DD> <single sentence reason>\n";
        $content .= "```\n\n";
        $content .= "**Examples:**\n";
        $content .= "```php\n";
        $content .= "// ALLOWTODO: SENTX-123 2025-02-15 Real position opening code needs implementation\n";
        $content .= "// ALLOWTODO: ISSUE-456 2025-01-30 Temporary workaround for API rate limiting\n";
        $content .= "// ALLOWTODO: TECH-789 2025-03-01 Performance optimization pending team review\n";
        $content .= "```\n\n";

        $content .= "## 🔧 Fixing Violations\n\n";
        $content .= "1. **Non-compliant TODOs**: Update to use ALLOWTODO format\n";
        $content .= "2. **Expired TODOs**: Update date or resolve the issue\n";
        $content .= "3. **Invalid dates**: Use proper YYYY-MM-DD format\n\n";

        $content .= "## ⚙️ Scanner Configuration\n\n";
        $content .= '- **Patterns**: '.implode(', ', self::TODO_PATTERNS)."\n";
        $content .= '- **Extensions**: '.implode(', ', $this->includedExtensions)."\n";
        $content .= '- **Excluded paths**: '.implode(', ', $this->excludedPaths)."\n";

        file_put_contents($reportPath, $content);
    }

    /**
     * Generate violation report for CI/CD
     */
    private function generateViolationReport(): void
    {
        $reportPath = "{$this->reportDir}/todo_violations.json";

        $report = [
            'metadata' => [
                'generated_at' => date('c'),
                'scanner_version' => self::VERSION,
                'files_scanned' => $this->scannedFiles,
                'total_files' => $this->totalFiles,
            ],
            'summary' => [
                'total_todos' => count($this->foundTodos),
                'total_violations' => count($this->violations),
                'compliant_todos' => count($this->foundTodos) - count($this->violations),
                'expired_todos' => count(array_filter($this->foundTodos, fn ($todo) => $todo['date_expired'] ?? false)),
            ],
            'violations' => $this->violations,
            'todos' => $this->foundTodos,
        ];

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
    }

    /**
     * Generate summary report
     */
    private function generateSummaryReport(): void
    {
        $reportPath = "{$this->reportDir}/todo_summary.txt";

        $content = "SentinentX TODO Sweeper Summary\n";
        $content .= "================================\n\n";
        $content .= 'Generated: '.date('Y-m-d H:i:s')."\n";
        $content .= 'Scanner Version: '.self::VERSION."\n\n";

        $content .= "Statistics:\n";
        $content .= "-----------\n";
        $content .= "Files Scanned: {$this->scannedFiles}/{$this->totalFiles}\n";
        $content .= 'TODO Comments: '.count($this->foundTodos)."\n";
        $content .= 'Violations: '.count($this->violations)."\n";
        $content .= 'Compliance Rate: '.$this->getComplianceRate()."%\n\n";

        if (count($this->violations) > 0) {
            $content .= "Violation Summary:\n";
            $content .= "------------------\n";
            $violationTypes = array_count_values(array_column($this->violations, 'type'));
            foreach ($violationTypes as $type => $count) {
                $content .= '- '.ucfirst(str_replace('_', ' ', $type)).": $count\n";
            }
            $content .= "\n";
        }

        $content .= 'Status: '.(count($this->violations) > 0 ? 'FAILED' : 'PASSED')."\n";

        file_put_contents($reportPath, $content);
    }

    /**
     * Calculate compliance rate
     */
    private function getComplianceRate(): float
    {
        $total = count($this->foundTodos);
        if ($total === 0) {
            return 100.0;
        }

        $violations = count($this->violations);

        return round((($total - $violations) / $total) * 100, 1);
    }

    /**
     * Print header
     */
    private function printHeader(): void
    {
        $this->log('🧹 SentinentX TODO/FIXME/HACK Sweeper v'.self::VERSION, 'info');
        $this->log('================================================', 'info');
    }

    /**
     * Print summary
     */
    private function printSummary(): void
    {
        $this->log("\n📊 Summary:", 'info');
        $this->log('==========', 'info');
        $this->log("Files scanned: {$this->scannedFiles}/{$this->totalFiles}", 'info');
        $this->log('TODO comments: '.count($this->foundTodos), 'info');
        $this->log('Violations: '.count($this->violations), 'info');
        $this->log('Compliance rate: '.$this->getComplianceRate().'%', 'info');
    }

    /**
     * Log message with color coding
     */
    private function log(string $message, string $level = 'info'): void
    {
        $colors = [
            'info' => "\033[0;36m",   // Cyan
            'success' => "\033[0;32m", // Green
            'warn' => "\033[0;33m",    // Yellow
            'error' => "\033[0;31m",   // Red
            'debug' => "\033[0;37m",    // Gray
        ];

        $reset = "\033[0m";
        $color = $colors[$level] ?? $colors['info'];

        echo $color.$message.$reset.PHP_EOL;
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $options = getopt('', [
        'help',
        'version',
        'strict',
        'verbose',
        'no-report',
        'no-exit-on-violation',
        'base-dir:',
        'report-dir:',
    ]);

    if (isset($options['help'])) {
        echo 'SentinentX TODO Sweeper v'.TodoSweeper::VERSION."\n\n";
        echo "Usage: php todo-sweeper.php [options]\n\n";
        echo "Options:\n";
        echo "  --help                    Show this help message\n";
        echo "  --version                 Show version information\n";
        echo "  --strict                  Enable strict mode\n";
        echo "  --verbose                 Enable verbose output\n";
        echo "  --no-report               Skip report generation\n";
        echo "  --no-exit-on-violation    Don't exit with error code on violations\n";
        echo "  --base-dir=PATH           Base directory to scan (default: .)\n";
        echo "  --report-dir=PATH         Report output directory (default: reports)\n\n";
        echo "Examples:\n";
        echo "  php todo-sweeper.php\n";
        echo "  php todo-sweeper.php --strict --verbose\n";
        echo "  php todo-sweeper.php --base-dir=/var/www/app --report-dir=/tmp/reports\n\n";
        exit(0);
    }

    if (isset($options['version'])) {
        echo 'SentinentX TODO Sweeper v'.TodoSweeper::VERSION."\n";
        exit(0);
    }

    $sweeper = new TodoSweeper(
        baseDir: $options['base-dir'] ?? '.',
        reportDir: $options['report-dir'] ?? 'reports',
        strictMode: isset($options['strict']),
        verbose: isset($options['verbose'])
    );

    $runOptions = [
        'strict' => isset($options['strict']),
        'verbose' => isset($options['verbose']),
        'report' => ! isset($options['no-report']),
        'exit-on-violation' => ! isset($options['no-exit-on-violation']),
    ];

    exit($sweeper->run($runOptions));
}
