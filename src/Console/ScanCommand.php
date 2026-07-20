<?php

namespace OGEagleEye\Laravel\Console;

use Illuminate\Console\Command;
use OGEagleEye\Monitor\OGEagleEye;

class ScanCommand extends Command
{
    protected $signature = 'ogeagleeye:scan
                            {paths?* : Relative paths to scan (default: config OGEagleEye.scan.paths)}
                            {--reset-baseline : Rewrite the integrity baseline as if first run}
                            {--no-heuristics : Skip PHP heuristic checks (integrity diff only)}';

    protected $description = 'Run a OGEagleEye file-integrity / heuristic scan and report findings';

    public function handle(): int
    {
        if (! config('ogeagleeye.enabled', true) || OGEagleEye::getClient() === null) {
            $this->warn('OGEagleEye is not configured (set OGEAGLEEYE_KEY and OGEAGLEEYE_ENDPOINT).');

            return self::FAILURE;
        }

        $paths = $this->argument('paths');
        if (! is_array($paths) || $paths === []) {
            $configured = config('ogeagleeye.scan.paths', ['.']);
            $paths = is_array($configured) ? $configured : ['.'];
        }

        $root = config('ogeagleeye.scan.root');
        $manifest = config('ogeagleeye.scan.manifest_path');

        $options = [
            'root' => is_string($root) && $root !== '' ? $root : base_path(),
            'manifest_path' => is_string($manifest) && $manifest !== ''
                ? $manifest
                : storage_path('app/.ogeagleeye-manifest.json'),
            'heuristics' => ! $this->option('no-heuristics') && (bool) config('ogeagleeye.scan.heuristics', true),
            'reset_baseline' => (bool) $this->option('reset-baseline'),
            'core_mtime_window' => (int) config('ogeagleeye.scan.core_mtime_window', 604800),
        ];

        $exclude = config('ogeagleeye.scan.exclude');
        if (is_array($exclude)) {
            $options['exclude'] = $exclude;
        }

        $this->info('Scanning '.implode(', ', $paths).' …');

        $result = OGEagleEye::scan($paths, $options);
        OGEagleEye::flush();

        if ($result === null) {
            $this->error('Scan failed (see debug logs if OGEAGLEEYE_DEBUG=true).');

            return self::FAILURE;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Baseline', ! empty($result['is_baseline']) ? 'yes (written)' : 'no'],
                ['Files scanned', (string) ($result['files_scanned'] ?? 0)],
                ['Integrity changes', (string) ($result['integrity_changes_count'] ?? 0)],
                ['Findings', (string) ($result['findings_count'] ?? 0)],
                ['Severity', (string) ($result['severity'] ?? 'none')],
            ],
        );

        if (! empty($result['findings']) && is_array($result['findings'])) {
            $rows = [];
            foreach ($result['findings'] as $finding) {
                if (! is_array($finding)) {
                    continue;
                }
                $rows[] = [
                    (string) ($finding['severity'] ?? ''),
                    (string) ($finding['rule_id'] ?? ''),
                    (string) ($finding['path'] ?? ''),
                    (string) ($finding['excerpt'] ?? ''),
                ];
            }
            if ($rows !== []) {
                $this->newLine();
                $this->table(['Severity', 'Rule', 'Path', 'Excerpt'], $rows);
            }
        }

        $this->info('scan_result event sent.');

        return self::SUCCESS;
    }
}
