# TODO/FIXME/HACK Register

**Generated**: 2025-08-27 12:28:24
**Scanner Version**: 1.0.0
**Total Files Scanned**: 419
**TODO Comments Found**: 1
**Violations Found**: 2228

---

## ✅ Compliant TODO Comments

| File | Line | Issue ID | Due Date | Status | Reason |
|------|------|----------|----------|--------|--------|
| `app/Console/Commands/OpenSpecificCommand.php` | 124 | SENTX-001 | 2025-08-27 | 🔴 EXPIRED | Gerçek pozisyon açma kodu exchange entegrasyonu tamamlandıktan sonra implement edilecek |

## ❌ TODO Violations

| File | Line | Type | Content | Message |
|------|------|------|---------|----------|
| `config/logging.php` | 65 | non_compliant | `'level' => env('LOG_LEVEL', 'debug'),...` | TODO comment not in required format |
| `config/logging.php` | 72 | non_compliant | `'level' => env('LOG_LEVEL', 'debug'),...` | TODO comment not in required format |
| `config/logging.php` | 93 | non_compliant | `'level' => env('LOG_LEVEL', 'debug'),...` | TODO comment not in required format |
| `config/logging.php` | 101 | non_compliant | `'level' => env('LOG_LEVEL', 'debug'),...` | TODO comment not in required format |
| `config/logging.php` | 113 | non_compliant | `'level' => env('LOG_LEVEL', 'debug'),...` | TODO comment not in required format |
| `config/logging.php` | 186 | non_compliant | `'level' => env('LOG_LEVEL', 'debug'),...` | TODO comment not in required format |
| `config/logging.php` | 198 | non_compliant | `'level' => env('LOG_LEVEL', 'debug'),...` | TODO comment not in required format |
| `config/logging.php` | 209 | non_compliant | `'level' => env('LOG_LEVEL', 'debug'),...` | TODO comment not in required format |
| `config/logging.php` | 216 | non_compliant | `'level' => env('LOG_LEVEL', 'debug'),...` | TODO comment not in required format |
| `config/app.php` | 33 | non_compliant | `| Application Debug Mode...` | TODO comment not in required format |
| `config/app.php` | 36 | non_compliant | `| When your application is in debug mode, detailed...` | TODO comment not in required format |
| `config/app.php` | 42 | non_compliant | `'debug' => (bool) env('APP_DEBUG', false),...` | TODO comment not in required format |
| `comprehensive_telegram_test.php` | 95 | non_compliant | `$this->assertNotEmpty($response, "Command $command...` | TODO comment not in required format |
| `comprehensive_telegram_test.php` | 484 | non_compliant | `private function assertNotEmpty($value, string $me...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 64 | non_compliant | `# Phase 2: TODO/FIXME/HACK Sweeper (CRITICAL GATE)...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 65 | non_compliant | `todo-sweeper:...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 66 | non_compliant | `name: TODO Sweeper (CRITICAL=0 Required)...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 82 | non_compliant | `- name: Run TODO Sweeper (STRICT)...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 84 | non_compliant | `php scripts/todo-sweeper.php --verbose --fail-on-v...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 85 | non_compliant | `VIOLATIONS=$(php scripts/todo-sweeper.php --count-...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 86 | non_compliant | `echo "TODO_VIOLATIONS=$VIOLATIONS" >> $GITHUB_ENV...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 88 | non_compliant | `echo "❌ TODO Sweeper FAILED: $VIOLATIONS violati...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 91 | non_compliant | `echo "✅ TODO Sweeper PASSED: 0 violations"...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 97 | non_compliant | `needs: [preflight, todo-sweeper]...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 148 | non_compliant | `needs: [preflight, todo-sweeper, static-analysis]...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 185 | non_compliant | `coverage: xdebug...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 339 | non_compliant | `needs: [preflight, todo-sweeper, static-analysis, ...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 350 | non_compliant | `echo "✅ TODO Sweeper=0: PASS"...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 396 | non_compliant | `PATCH=$(git log $LAST_TAG..HEAD --oneline | grep -...` | TODO comment not in required format |
| `.github/workflows/comprehensive-ci.yml` | 438 | non_compliant | `- ✅ TODO Sweeper: 0 violations...` | TODO comment not in required format |
| `.github/workflows/tests.yml` | 68 | non_compliant | `coverage: xdebug...` | TODO comment not in required format |
| `.github/workflows/paranoia-suite.yml` | 49 | non_compliant | `coverage: xdebug...` | TODO comment not in required format |
| `tests/Feature/CycleRunnerE2ETest.php` | 57 | non_compliant | `// Debug: Mock objesini kontrol et...` | TODO comment not in required format |
| `tests/Feature/Notifier/AlertDeduplicationTest.php` | 122 | non_compliant | `$this->assertNotEquals($result1['dedup_key'], $res...` | TODO comment not in required format |
| `tests/Feature/ConsensusServiceTest.php` | 451 | non_compliant | `$this->assertNotEmpty($out);...` | TODO comment not in required format |
| `tests/Feature/FailureScenarioTest.php` | 300 | non_compliant | `$this->assertNotEmpty($strategy);...` | TODO comment not in required format |
| `tests/Feature/OpenNowCommandTest.php` | 104 | non_compliant | `]; // Note: deliberately missing timestamp & symbo...` | TODO comment not in required format |
| `tests/Feature/OpenNowCommandTest.php` | 153 | non_compliant | `// Note: AI consensus may succeed or fail in test ...` | TODO comment not in required format |
| `tests/Feature/OpenNowCommandTest.php` | 189 | non_compliant | `// Note: AI consensus may succeed or fail in test ...` | TODO comment not in required format |
| `tests/Feature/OpenNowCommandTest.php` | 227 | non_compliant | `// Note: AI consensus may succeed or fail in test ...` | TODO comment not in required format |
| `tests/Feature/OpenNowCommandTest.php` | 260 | non_compliant | `]); // Note: AI consensus may succeed or fail in t...` | TODO comment not in required format |
| `tests/Feature/OpenNowCommandTest.php` | 333 | non_compliant | `->expectsOutput('Symbols: BTC, ETH, SOL, XRP'); //...` | TODO comment not in required format |
| `tests/Feature/OpenNowCommandTest.php` | 368 | non_compliant | `->expectsOutput('Symbols: BTC, ETH, SOL'); // Note...` | TODO comment not in required format |
| `tests/Feature/E2E/MultiTenantDataIsolationTest.php` | 159 | non_compliant | `// Note: This test may need RLS policies to work p...` | TODO comment not in required format |
| `tests/Feature/E2E/HmacSecurityTest.php` | 71 | non_compliant | `$this->markTestSkipped('HMAC signature calculation...` | TODO comment not in required format |
| `tests/Feature/AppKeyTest.php` | 13 | non_compliant | `$this->assertNotEmpty(config('app.key'), 'APP_KEY ...` | TODO comment not in required format |
| `tests/Feature/TradingWorkflowIntegrationTest.php` | 156 | non_compliant | `// Note: DB persistence may vary in test environme...` | TODO comment not in required format |
| `tests/Feature/TradingWorkflowIntegrationTest.php` | 299 | non_compliant | `// Note: correlation logic may vary in test enviro...` | TODO comment not in required format |
| `tests/RealWorld/Agnostic/NetworkPartitionTest.php` | 316 | non_compliant | `$this->assertNotEmpty($command['command']);...` | TODO comment not in required format |
| `tests/TestCase.php` | 85 | non_compliant | `* Helper to assert HTTP calls with detailed debugg...` | TODO comment not in required format |
| `tests/Performance/LoadTestingSuite.php` | 29 | non_compliant | `$this->assertNotEmpty($result);...` | TODO comment not in required format |
| `tests/Unit/TelegramNotifierTest.php` | 131 | non_compliant | `public function test_telegram_notifier_web_page_pr...` | TODO comment not in required format |
| `tests/Unit/CycleRunnerTest.php` | 102 | non_compliant | `Log::shouldReceive('debug')->andReturn(true);...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceTest.php` | 55 | non_compliant | `$this->assertNotEmpty($out); // Basic structure ch...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceTest.php` | 110 | non_compliant | `$this->assertNotEmpty($out['reason']);...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceTest.php` | 393 | non_compliant | `$this->assertNotEmpty($out['reason']);...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceTest.php` | 424 | non_compliant | `$this->assertNotEmpty($out['reason']);...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceTest.php` | 531 | non_compliant | `$this->assertNotEmpty($out['reason']);...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceTest.php` | 1430 | non_compliant | `$this->assertNotEmpty($result['details']);...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceTest.php` | 1554 | non_compliant | `$this->assertNotEmpty($result['details']);...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceTest.php` | 1631 | non_compliant | `$this->assertNotEmpty($result['details']);...` | TODO comment not in required format |
| `tests/Unit/TelegramNotifierExtendedTest.php` | 48 | non_compliant | `$request['disable_web_page_preview'] === true;...` | TODO comment not in required format |
| `tests/Unit/ComprehensiveMathTest.php` | 54 | non_compliant | `$this->assertNotEquals(0.3, $floatSum, 'Float shou...` | TODO comment not in required format |
| `tests/Unit/TenantResourceManagerTest.php` | 300 | non_compliant | `$this->assertNotEmpty($report['warnings']);...` | TODO comment not in required format |
| `tests/Unit/TenantResourceManagerTest.php` | 329 | non_compliant | `$this->assertNotEmpty($report['warnings']);...` | TODO comment not in required format |
| `tests/Unit/Services/WebSocket/RestBackfillTest.php` | 60 | non_compliant | `// Note: Actual implementation may process data di...` | TODO comment not in required format |
| `tests/Unit/AiLogTest.php` | 170 | non_compliant | `public function test_ai_log_debugging_ready(): voi...` | TODO comment not in required format |
| `tests/Unit/AiLogTest.php` | 174 | non_compliant | `// Debugging essential fields...` | TODO comment not in required format |
| `tests/Unit/PromptSecurityGuardTest.php` | 237 | non_compliant | `public function test_prompt_security_guard_preview...` | TODO comment not in required format |
| `tests/Unit/PromptSecurityGuardTest.php` | 241 | non_compliant | `// Preview generation essential functionality...` | TODO comment not in required format |
| `tests/Unit/QueryOptimizerTest.php` | 38 | non_compliant | `$this->assertNotEmpty($activeTrades);...` | TODO comment not in required format |
| `tests/Unit/QueryOptimizerTest.php` | 59 | non_compliant | `$this->assertNotEquals($tenant1Symbols, $tenant2Sy...` | TODO comment not in required format |
| `tests/Unit/QueryOptimizerTest.php` | 106 | non_compliant | `$this->assertNotEmpty($exposure);...` | TODO comment not in required format |
| `tests/Unit/QueryOptimizerTest.php` | 149 | non_compliant | `$this->assertNotEmpty($decisions);...` | TODO comment not in required format |
| `tests/Unit/StructuredLogServiceTest.php` | 181 | non_compliant | `public function debug_logs_to_specified_channel_wi...` | TODO comment not in required format |
| `tests/Unit/StructuredLogServiceTest.php` | 183 | non_compliant | `$channel = 'debug_channel';...` | TODO comment not in required format |
| `tests/Unit/StructuredLogServiceTest.php` | 184 | non_compliant | `$message = 'Debug message';...` | TODO comment not in required format |
| `tests/Unit/StructuredLogServiceTest.php` | 185 | non_compliant | `$context = ['debug_level' => 'VERBOSE'];...` | TODO comment not in required format |
| `tests/Unit/StructuredLogServiceTest.php` | 190 | non_compliant | `->with('debug', $message, Mockery::any());...` | TODO comment not in required format |
| `tests/Unit/StructuredLogServiceTest.php` | 197 | non_compliant | `$this->logService->debug($channel, $message, $cont...` | TODO comment not in required format |
| `tests/Unit/BybitClientTest.php` | 32 | non_compliant | `$this->assertNotEquals($key1, $key3);...` | TODO comment not in required format |
| `tests/Unit/BybitClientTest.php` | 35 | non_compliant | `$this->assertNotEquals($key1, $key4);...` | TODO comment not in required format |
| `tests/Unit/BybitClientTest.php` | 58 | non_compliant | `$this->assertNotEquals($key1, $key3);...` | TODO comment not in required format |
| `tests/Unit/BybitClientTest.php` | 104 | non_compliant | `$this->assertNotEmpty($result['orderId']);...` | TODO comment not in required format |
| `tests/Unit/BybitClientTest.php` | 106 | non_compliant | `$this->assertNotEmpty($result['idempotencyKey']);...` | TODO comment not in required format |
| `tests/Unit/BybitClientTest.php` | 130 | non_compliant | `$this->assertNotEmpty($result['idempotencyKey']);...` | TODO comment not in required format |
| `tests/Unit/BybitClientTest.php` | 131 | non_compliant | `// Note: ok field and error_code may differ in tes...` | TODO comment not in required format |
| `tests/Unit/DataExportServiceTest.php` | 65 | non_compliant | `$this->assertNotEmpty($tradingHistory);...` | TODO comment not in required format |
| `tests/Unit/DataExportServiceTest.php` | 75 | non_compliant | `$this->assertNotEmpty($aiDecisions);...` | TODO comment not in required format |
| `tests/Unit/DataExportServiceTest.php` | 83 | non_compliant | `$this->assertNotEmpty($positions);...` | TODO comment not in required format |
| `tests/Unit/DataExportServiceTest.php` | 87 | non_compliant | `$this->assertNotEmpty($alerts);...` | TODO comment not in required format |
| `tests/Unit/DataExportServiceTest.php` | 104 | non_compliant | `$this->assertNotEquals($user2, $export1Data['user_...` | TODO comment not in required format |
| `tests/Unit/DataExportServiceTest.php` | 115 | non_compliant | `$this->assertNotEquals($user1Symbols, $user2Symbol...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceExtendedTest.php` | 132 | non_compliant | `$this->markTestSkipped('ConsensusService take prof...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceExtendedTest.php` | 152 | non_compliant | `$this->markTestSkipped('ConsensusService NONE veto...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceExtendedTest.php` | 172 | non_compliant | `$this->markTestSkipped('ConsensusService NONE veto...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceExtendedTest.php` | 178 | non_compliant | `$this->markTestSkipped('ConsensusService mixed act...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceExtendedTest.php` | 184 | non_compliant | `$this->markTestSkipped('ConsensusService timeframe...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceExtendedTest.php` | 190 | non_compliant | `$this->markTestSkipped('ConsensusService metadata ...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceExtendedTest.php` | 229 | non_compliant | `$this->markTestSkipped('ConsensusService invalid a...` | TODO comment not in required format |
| `tests/Unit/ConsensusServiceExtendedTest.php` | 235 | non_compliant | `$this->markTestSkipped('ConsensusService performan...` | TODO comment not in required format |
| `tests/Unit/BybitHelpersTest.php` | 144 | non_compliant | `$this->assertNotEquals($sig1, $sig2);...` | TODO comment not in required format |
| `tests/Chaos/ChaosTestSuite.php` | 228 | non_compliant | `Log::debug('Load test operation failed', [...` | TODO comment not in required format |
| `tests/Chaos/ChaosTestSuite.php` | 391 | non_compliant | `Log::debug("Recovery attempt {$attempt} failed", [...` | TODO comment not in required format |
| `docs/api/openapi.yaml` | 1300 | non_compliant | `description: Unique request identifier for debuggi...` | TODO comment not in required format |
| `reports/todo_violations.json` | 9 | non_compliant | `"total_todos": 0,...` | TODO comment not in required format |
| `reports/todo_violations.json` | 11 | non_compliant | `"compliant_todos": -556,...` | TODO comment not in required format |
| `reports/todo_violations.json` | 12 | non_compliant | `"expired_todos": 0...` | TODO comment not in required format |
| `reports/todo_violations.json` | 19 | non_compliant | `"content": "'level' => env('LOG_LEVEL', 'debug'),"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 20 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 21 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 27 | non_compliant | `"content": "'level' => env('LOG_LEVEL', 'debug'),"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 28 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 29 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 35 | non_compliant | `"content": "'level' => env('LOG_LEVEL', 'debug'),"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 36 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 37 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 43 | non_compliant | `"content": "'level' => env('LOG_LEVEL', 'debug'),"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 44 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 45 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 51 | non_compliant | `"content": "'level' => env('LOG_LEVEL', 'debug'),"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 52 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 53 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 59 | non_compliant | `"content": "'level' => env('LOG_LEVEL', 'debug'),"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 60 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 61 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 67 | non_compliant | `"content": "'level' => env('LOG_LEVEL', 'debug'),"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 68 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 69 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 75 | non_compliant | `"content": "'level' => env('LOG_LEVEL', 'debug'),"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 76 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 77 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 83 | non_compliant | `"content": "'level' => env('LOG_LEVEL', 'debug'),"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 84 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 85 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 91 | non_compliant | `"content": "| Application Debug Mode",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 92 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 93 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 99 | non_compliant | `"content": "| When your application is in debug mo...` | TODO comment not in required format |
| `reports/todo_violations.json` | 100 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 101 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 107 | non_compliant | `"content": "'debug' => (bool) env('APP_DEBUG', fal...` | TODO comment not in required format |
| `reports/todo_violations.json` | 108 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 109 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 115 | non_compliant | `"content": "$this->assertNotEmpty($response, \"Com...` | TODO comment not in required format |
| `reports/todo_violations.json` | 116 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 117 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 123 | non_compliant | `"content": "private function assertNotEmpty($value...` | TODO comment not in required format |
| `reports/todo_violations.json` | 124 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 125 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 131 | non_compliant | `"content": "# Phase 2: TODO\/FIXME\/HACK Sweeper (...` | TODO comment not in required format |
| `reports/todo_violations.json` | 132 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 133 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 139 | non_compliant | `"content": "todo-sweeper:",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 140 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 141 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 147 | non_compliant | `"content": "name: TODO Sweeper (CRITICAL=0 Require...` | TODO comment not in required format |
| `reports/todo_violations.json` | 148 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 149 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 155 | non_compliant | `"content": "- name: Run TODO Sweeper (STRICT)",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 156 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 157 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 163 | non_compliant | `"content": "php scripts\/todo-sweeper.php --verbos...` | TODO comment not in required format |
| `reports/todo_violations.json` | 164 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 165 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 171 | non_compliant | `"content": "VIOLATIONS=$(php scripts\/todo-sweeper...` | TODO comment not in required format |
| `reports/todo_violations.json` | 172 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 173 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 179 | non_compliant | `"content": "echo \"TODO_VIOLATIONS=$VIOLATIONS\" >...` | TODO comment not in required format |
| `reports/todo_violations.json` | 180 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 181 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 187 | non_compliant | `"content": "echo \"\u274c TODO Sweeper FAILED: $VI...` | TODO comment not in required format |
| `reports/todo_violations.json` | 188 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 189 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 195 | non_compliant | `"content": "echo \"\u2705 TODO Sweeper PASSED: 0 v...` | TODO comment not in required format |
| `reports/todo_violations.json` | 196 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 197 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 203 | non_compliant | `"content": "needs: [preflight, todo-sweeper]",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 204 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 205 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 211 | non_compliant | `"content": "needs: [preflight, todo-sweeper, stati...` | TODO comment not in required format |
| `reports/todo_violations.json` | 212 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 213 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 219 | non_compliant | `"content": "coverage: xdebug",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 220 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 221 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 227 | non_compliant | `"content": "needs: [preflight, todo-sweeper, stati...` | TODO comment not in required format |
| `reports/todo_violations.json` | 228 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 229 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 235 | non_compliant | `"content": "echo \"\u2705 TODO Sweeper=0: PASS\"",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 236 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 237 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 243 | non_compliant | `"content": "PATCH=$(git log $LAST_TAG..HEAD --onel...` | TODO comment not in required format |
| `reports/todo_violations.json` | 244 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 245 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 251 | non_compliant | `"content": "- \u2705 TODO Sweeper: 0 violations",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 252 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 253 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 259 | non_compliant | `"content": "coverage: xdebug",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 260 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 261 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 267 | non_compliant | `"content": "coverage: xdebug",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 268 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 269 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 275 | non_compliant | `"content": "\/\/ Debug: Mock objesini kontrol et",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 276 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 277 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 283 | non_compliant | `"content": "$this->assertNotEquals($result1['dedup...` | TODO comment not in required format |
| `reports/todo_violations.json` | 284 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 285 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 291 | non_compliant | `"content": "$this->assertNotEmpty($out);",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 292 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 293 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 299 | non_compliant | `"content": "$this->assertNotEmpty($strategy);",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 300 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 301 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 307 | non_compliant | `"content": "]; \/\/ Note: deliberately missing tim...` | TODO comment not in required format |
| `reports/todo_violations.json` | 308 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 309 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 315 | non_compliant | `"content": "\/\/ Note: AI consensus may succeed or...` | TODO comment not in required format |
| `reports/todo_violations.json` | 316 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 317 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 323 | non_compliant | `"content": "\/\/ Note: AI consensus may succeed or...` | TODO comment not in required format |
| `reports/todo_violations.json` | 324 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 325 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 331 | non_compliant | `"content": "\/\/ Note: AI consensus may succeed or...` | TODO comment not in required format |
| `reports/todo_violations.json` | 332 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 333 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 339 | non_compliant | `"content": "]); \/\/ Note: AI consensus may succee...` | TODO comment not in required format |
| `reports/todo_violations.json` | 340 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 341 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 347 | non_compliant | `"content": "->expectsOutput('Symbols: BTC, ETH, SO...` | TODO comment not in required format |
| `reports/todo_violations.json` | 348 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 349 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 355 | non_compliant | `"content": "->expectsOutput('Symbols: BTC, ETH, SO...` | TODO comment not in required format |
| `reports/todo_violations.json` | 356 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 357 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 363 | non_compliant | `"content": "\/\/ Note: This test may need RLS poli...` | TODO comment not in required format |
| `reports/todo_violations.json` | 364 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 365 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 371 | non_compliant | `"content": "$this->markTestSkipped('HMAC signature...` | TODO comment not in required format |
| `reports/todo_violations.json` | 372 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 373 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 379 | non_compliant | `"content": "$this->assertNotEmpty(config('app.key'...` | TODO comment not in required format |
| `reports/todo_violations.json` | 380 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 381 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 387 | non_compliant | `"content": "\/\/ Note: DB persistence may vary in ...` | TODO comment not in required format |
| `reports/todo_violations.json` | 388 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 389 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 395 | non_compliant | `"content": "\/\/ Note: correlation logic may vary ...` | TODO comment not in required format |
| `reports/todo_violations.json` | 396 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 397 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 403 | non_compliant | `"content": "$this->assertNotEmpty($command['comman...` | TODO comment not in required format |
| `reports/todo_violations.json` | 404 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 405 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 411 | non_compliant | `"content": "* Helper to assert HTTP calls with det...` | TODO comment not in required format |
| `reports/todo_violations.json` | 412 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 413 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 419 | non_compliant | `"content": "$this->assertNotEmpty($result);",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 420 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 421 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 427 | non_compliant | `"content": "public function test_telegram_notifier...` | TODO comment not in required format |
| `reports/todo_violations.json` | 428 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 429 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 435 | non_compliant | `"content": "Log::shouldReceive('debug')->andReturn...` | TODO comment not in required format |
| `reports/todo_violations.json` | 436 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 437 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 443 | non_compliant | `"content": "$this->assertNotEmpty($out); \/\/ Basi...` | TODO comment not in required format |
| `reports/todo_violations.json` | 444 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 445 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 451 | non_compliant | `"content": "$this->assertNotEmpty($out['reason']);...` | TODO comment not in required format |
| `reports/todo_violations.json` | 452 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 453 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 459 | non_compliant | `"content": "$this->assertNotEmpty($out['reason']);...` | TODO comment not in required format |
| `reports/todo_violations.json` | 460 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 461 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 467 | non_compliant | `"content": "$this->assertNotEmpty($out['reason']);...` | TODO comment not in required format |
| `reports/todo_violations.json` | 468 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 469 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 475 | non_compliant | `"content": "$this->assertNotEmpty($out['reason']);...` | TODO comment not in required format |
| `reports/todo_violations.json` | 476 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 477 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 483 | non_compliant | `"content": "$this->assertNotEmpty($result['details...` | TODO comment not in required format |
| `reports/todo_violations.json` | 484 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 485 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 491 | non_compliant | `"content": "$this->assertNotEmpty($result['details...` | TODO comment not in required format |
| `reports/todo_violations.json` | 492 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 493 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 499 | non_compliant | `"content": "$this->assertNotEmpty($result['details...` | TODO comment not in required format |
| `reports/todo_violations.json` | 500 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 501 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 507 | non_compliant | `"content": "$request['disable_web_page_preview'] =...` | TODO comment not in required format |
| `reports/todo_violations.json` | 508 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 509 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 515 | non_compliant | `"content": "$this->assertNotEquals(0.3, $floatSum,...` | TODO comment not in required format |
| `reports/todo_violations.json` | 516 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 517 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 523 | non_compliant | `"content": "$this->assertNotEmpty($report['warning...` | TODO comment not in required format |
| `reports/todo_violations.json` | 524 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 525 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 531 | non_compliant | `"content": "$this->assertNotEmpty($report['warning...` | TODO comment not in required format |
| `reports/todo_violations.json` | 532 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 533 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 539 | non_compliant | `"content": "\/\/ Note: Actual implementation may p...` | TODO comment not in required format |
| `reports/todo_violations.json` | 540 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 541 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 547 | non_compliant | `"content": "public function test_ai_log_debugging_...` | TODO comment not in required format |
| `reports/todo_violations.json` | 548 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 549 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 555 | non_compliant | `"content": "\/\/ Debugging essential fields",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 556 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 557 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 563 | non_compliant | `"content": "public function test_prompt_security_g...` | TODO comment not in required format |
| `reports/todo_violations.json` | 564 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 565 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 571 | non_compliant | `"content": "\/\/ Preview generation essential func...` | TODO comment not in required format |
| `reports/todo_violations.json` | 572 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 573 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 579 | non_compliant | `"content": "$this->assertNotEmpty($activeTrades);"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 580 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 581 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 587 | non_compliant | `"content": "$this->assertNotEquals($tenant1Symbols...` | TODO comment not in required format |
| `reports/todo_violations.json` | 588 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 589 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 595 | non_compliant | `"content": "$this->assertNotEmpty($exposure);",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 596 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 597 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 603 | non_compliant | `"content": "$this->assertNotEmpty($decisions);",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 604 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 605 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 611 | non_compliant | `"content": "public function debug_logs_to_specifie...` | TODO comment not in required format |
| `reports/todo_violations.json` | 612 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 613 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 619 | non_compliant | `"content": "$channel = 'debug_channel';",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 620 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 621 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 627 | non_compliant | `"content": "$message = 'Debug message';",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 628 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 629 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 635 | non_compliant | `"content": "$context = ['debug_level' => 'VERBOSE'...` | TODO comment not in required format |
| `reports/todo_violations.json` | 636 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 637 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 643 | non_compliant | `"content": "->with('debug', $message, Mockery::any...` | TODO comment not in required format |
| `reports/todo_violations.json` | 644 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 645 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 651 | non_compliant | `"content": "$this->logService->debug($channel, $me...` | TODO comment not in required format |
| `reports/todo_violations.json` | 652 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 653 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 659 | non_compliant | `"content": "$this->assertNotEquals($key1, $key3);"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 660 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 661 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 667 | non_compliant | `"content": "$this->assertNotEquals($key1, $key4);"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 668 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 669 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 675 | non_compliant | `"content": "$this->assertNotEquals($key1, $key3);"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 676 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 677 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 683 | non_compliant | `"content": "$this->assertNotEmpty($result['orderId...` | TODO comment not in required format |
| `reports/todo_violations.json` | 684 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 685 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 691 | non_compliant | `"content": "$this->assertNotEmpty($result['idempot...` | TODO comment not in required format |
| `reports/todo_violations.json` | 692 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 693 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 699 | non_compliant | `"content": "$this->assertNotEmpty($result['idempot...` | TODO comment not in required format |
| `reports/todo_violations.json` | 700 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 701 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 707 | non_compliant | `"content": "\/\/ Note: ok field and error_code may...` | TODO comment not in required format |
| `reports/todo_violations.json` | 708 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 709 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 715 | non_compliant | `"content": "$this->assertNotEmpty($tradingHistory)...` | TODO comment not in required format |
| `reports/todo_violations.json` | 716 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 717 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 723 | non_compliant | `"content": "$this->assertNotEmpty($aiDecisions);",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 724 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 725 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 731 | non_compliant | `"content": "$this->assertNotEmpty($positions);",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 732 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 733 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 739 | non_compliant | `"content": "$this->assertNotEmpty($alerts);",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 740 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 741 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 747 | non_compliant | `"content": "$this->assertNotEquals($user2, $export...` | TODO comment not in required format |
| `reports/todo_violations.json` | 748 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 749 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 755 | non_compliant | `"content": "$this->assertNotEquals($user1Symbols, ...` | TODO comment not in required format |
| `reports/todo_violations.json` | 756 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 757 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 763 | non_compliant | `"content": "$this->markTestSkipped('ConsensusServi...` | TODO comment not in required format |
| `reports/todo_violations.json` | 764 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 765 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 771 | non_compliant | `"content": "$this->markTestSkipped('ConsensusServi...` | TODO comment not in required format |
| `reports/todo_violations.json` | 772 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 773 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 779 | non_compliant | `"content": "$this->markTestSkipped('ConsensusServi...` | TODO comment not in required format |
| `reports/todo_violations.json` | 780 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 781 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 787 | non_compliant | `"content": "$this->markTestSkipped('ConsensusServi...` | TODO comment not in required format |
| `reports/todo_violations.json` | 788 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 789 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 795 | non_compliant | `"content": "$this->markTestSkipped('ConsensusServi...` | TODO comment not in required format |
| `reports/todo_violations.json` | 796 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 797 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 803 | non_compliant | `"content": "$this->markTestSkipped('ConsensusServi...` | TODO comment not in required format |
| `reports/todo_violations.json` | 804 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 805 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 811 | non_compliant | `"content": "$this->markTestSkipped('ConsensusServi...` | TODO comment not in required format |
| `reports/todo_violations.json` | 812 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 813 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 819 | non_compliant | `"content": "$this->markTestSkipped('ConsensusServi...` | TODO comment not in required format |
| `reports/todo_violations.json` | 820 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 821 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 827 | non_compliant | `"content": "$this->assertNotEquals($sig1, $sig2);"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 828 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 829 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 835 | non_compliant | `"content": "Log::debug('Load test operation failed...` | TODO comment not in required format |
| `reports/todo_violations.json` | 836 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 837 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 843 | non_compliant | `"content": "Log::debug(\"Recovery attempt {$attemp...` | TODO comment not in required format |
| `reports/todo_violations.json` | 844 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 845 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 851 | non_compliant | `"content": "description: Unique request identifier...` | TODO comment not in required format |
| `reports/todo_violations.json` | 852 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 853 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 857 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 859 | non_compliant | `"content": "\"total_todos\": 0,",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 860 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 861 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 865 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 867 | non_compliant | `"content": "\"compliant_todos\": -134,",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 868 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 869 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 873 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 875 | non_compliant | `"content": "\"expired_todos\": 0",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 876 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 877 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 881 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 883 | non_compliant | `"content": "\"content\": \"'level' => env('LOG_LEV...` | TODO comment not in required format |
| `reports/todo_violations.json` | 884 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 885 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 889 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 891 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 892 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 893 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 897 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 899 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 900 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 901 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 905 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 907 | non_compliant | `"content": "\"content\": \"'level' => env('LOG_LEV...` | TODO comment not in required format |
| `reports/todo_violations.json` | 908 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 909 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 913 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 915 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 916 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 917 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 921 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 923 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 924 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 925 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 929 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 931 | non_compliant | `"content": "\"content\": \"'level' => env('LOG_LEV...` | TODO comment not in required format |
| `reports/todo_violations.json` | 932 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 933 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 937 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 939 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 940 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 941 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 945 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 947 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 948 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 949 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 953 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 955 | non_compliant | `"content": "\"content\": \"'level' => env('LOG_LEV...` | TODO comment not in required format |
| `reports/todo_violations.json` | 956 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 957 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 961 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 963 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 964 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 965 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 969 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 971 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 972 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 973 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 977 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 979 | non_compliant | `"content": "\"content\": \"'level' => env('LOG_LEV...` | TODO comment not in required format |
| `reports/todo_violations.json` | 980 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 981 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 985 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 987 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 988 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 989 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 993 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 995 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 996 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 997 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1001 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1003 | non_compliant | `"content": "\"content\": \"'level' => env('LOG_LEV...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1004 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1005 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1009 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1011 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1012 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1013 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1017 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1019 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1020 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1021 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1025 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1027 | non_compliant | `"content": "\"content\": \"'level' => env('LOG_LEV...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1028 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1029 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1033 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1035 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1036 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1037 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1041 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1043 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1044 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1045 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1049 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1051 | non_compliant | `"content": "\"content\": \"'level' => env('LOG_LEV...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1052 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1053 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1057 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1059 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1060 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1061 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1065 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1067 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1068 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1069 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1073 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1075 | non_compliant | `"content": "\"content\": \"'level' => env('LOG_LEV...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1076 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1077 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1081 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1083 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1084 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1085 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1089 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1091 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1092 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1093 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1097 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1099 | non_compliant | `"content": "\"content\": \"| Application Debug Mod...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1100 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1101 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1105 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1107 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1108 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1109 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1113 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1115 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1116 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1117 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1121 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1123 | non_compliant | `"content": "\"content\": \"| When your application...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1124 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1125 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1129 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1131 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1132 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1133 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1137 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1139 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1140 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1141 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1145 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1147 | non_compliant | `"content": "\"content\": \"'debug' => (bool) env('...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1148 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1149 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1153 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1155 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1156 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1157 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1161 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1163 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1164 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1165 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1169 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1171 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1172 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1173 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1177 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1179 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1180 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1181 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1185 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1187 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1188 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1189 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1193 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1195 | non_compliant | `"content": "\"content\": \"private function assert...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1196 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1197 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1201 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1203 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1204 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1205 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1209 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1211 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1212 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1213 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1217 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1219 | non_compliant | `"content": "\"content\": \"coverage: xdebug\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1220 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1221 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1225 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1227 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1228 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1229 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1233 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1235 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1236 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1237 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1241 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1243 | non_compliant | `"content": "\"content\": \"coverage: xdebug\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1244 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1245 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1249 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1251 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1252 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1253 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1257 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1259 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1260 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1261 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1265 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1267 | non_compliant | `"content": "\"content\": \"\\\/\\\/ Debug: Mock ob...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1268 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1269 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1273 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1275 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1276 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1277 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1281 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1283 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1284 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1285 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1289 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1291 | non_compliant | `"content": "\"content\": \"$this->assertNotEquals(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1292 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1293 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1297 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1299 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1300 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1301 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1305 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1307 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1308 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1309 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1313 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1315 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1316 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1317 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1321 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1323 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1324 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1325 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1329 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1331 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1332 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1333 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1337 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1339 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1340 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1341 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1345 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1347 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1348 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1349 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1353 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1355 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1356 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1357 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1361 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1363 | non_compliant | `"content": "\"content\": \"]; \\\/\\\/ Note: delib...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1364 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1365 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1369 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1371 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1372 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1373 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1377 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1379 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1380 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1381 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1385 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1387 | non_compliant | `"content": "\"content\": \"\\\/\\\/ Note: AI conse...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1388 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1389 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1393 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1395 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1396 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1397 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1401 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1403 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1404 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1405 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1409 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1411 | non_compliant | `"content": "\"content\": \"\\\/\\\/ Note: AI conse...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1412 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1413 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1417 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1419 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1420 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1421 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1425 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1427 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1428 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1429 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1433 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1435 | non_compliant | `"content": "\"content\": \"\\\/\\\/ Note: AI conse...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1436 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1437 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1441 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1443 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1444 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1445 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1449 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1451 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1452 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1453 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1457 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1459 | non_compliant | `"content": "\"content\": \"]); \\\/\\\/ Note: AI c...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1460 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1461 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1465 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1467 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1468 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1469 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1473 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1475 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1476 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1477 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1481 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1483 | non_compliant | `"content": "\"content\": \"->expectsOutput('Symbol...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1484 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1485 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1489 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1491 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1492 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1493 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1497 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1499 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1500 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1501 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1505 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1507 | non_compliant | `"content": "\"content\": \"->expectsOutput('Symbol...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1508 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1509 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1513 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1515 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1516 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1517 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1521 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1523 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1524 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1525 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1529 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1531 | non_compliant | `"content": "\"content\": \"\\\/\\\/ Note: This tes...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1532 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1533 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1537 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1539 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1540 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1541 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1545 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1547 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1548 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1549 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1553 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1555 | non_compliant | `"content": "\"content\": \"$this->markTestSkipped(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1556 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1557 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1561 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1563 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1564 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1565 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1569 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1571 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1572 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1573 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1577 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1579 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty(c...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1580 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1581 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1585 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1587 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1588 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1589 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1593 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1595 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1596 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1597 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1601 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1603 | non_compliant | `"content": "\"content\": \"\\\/\\\/ Note: DB persi...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1604 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1605 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1609 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1611 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1612 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1613 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1617 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1619 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1620 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1621 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1625 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1627 | non_compliant | `"content": "\"content\": \"\\\/\\\/ Note: correlat...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1628 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1629 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1633 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1635 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1636 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1637 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1641 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1643 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1644 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1645 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1649 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1651 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1652 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1653 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1657 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1659 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1660 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1661 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1665 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1667 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1668 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1669 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1673 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1675 | non_compliant | `"content": "\"content\": \"* Helper to assert HTTP...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1676 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1677 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1681 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1683 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1684 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1685 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1689 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1691 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1692 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1693 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1697 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1699 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1700 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1701 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1705 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1707 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1708 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1709 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1713 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1715 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1716 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1717 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1721 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1723 | non_compliant | `"content": "\"content\": \"public function test_te...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1724 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1725 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1729 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1731 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1732 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1733 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1737 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1739 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1740 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1741 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1745 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1747 | non_compliant | `"content": "\"content\": \"Log::shouldReceive('deb...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1748 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1749 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1753 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1755 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1756 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1757 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1761 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1763 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1764 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1765 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1769 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1771 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1772 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1773 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1777 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1779 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1780 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1781 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1785 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1787 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1788 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1789 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1793 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1795 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1796 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1797 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1801 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1803 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1804 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1805 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1809 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1811 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1812 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1813 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1817 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1819 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1820 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1821 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1825 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1827 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1828 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1829 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1833 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1835 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1836 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1837 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1841 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1843 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1844 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1845 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1849 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1851 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1852 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1853 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1857 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1859 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1860 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1861 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1865 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1867 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1868 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1869 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1873 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1875 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1876 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1877 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1881 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1883 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1884 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1885 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1889 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1891 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1892 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1893 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1897 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1899 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1900 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1901 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1905 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1907 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1908 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1909 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1913 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1915 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1916 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1917 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1921 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1923 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1924 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1925 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1929 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1931 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1932 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1933 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1937 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1939 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1940 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1941 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1945 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1947 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1948 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1949 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1953 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1955 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1956 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1957 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1961 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1963 | non_compliant | `"content": "\"content\": \"$request['disable_web_p...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1964 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1965 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1969 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1971 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1972 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1973 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1977 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1979 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1980 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1981 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1985 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1987 | non_compliant | `"content": "\"content\": \"$this->assertNotEquals(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1988 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1989 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1993 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1995 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1996 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 1997 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2001 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2003 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2004 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2005 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2009 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2011 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2012 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2013 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2017 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2019 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2020 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2021 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2025 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2027 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2028 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2029 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2033 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2035 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2036 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2037 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2041 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2043 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2044 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2045 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2049 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2051 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2052 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2053 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2057 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2059 | non_compliant | `"content": "\"content\": \"\\\/\\\/ Note: Actual i...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2060 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2061 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2065 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2067 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2068 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2069 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2073 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2075 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2076 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2077 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2081 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2083 | non_compliant | `"content": "\"content\": \"public function test_ai...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2084 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2085 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2089 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2091 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2092 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2093 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2097 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2099 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2100 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2101 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2105 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2107 | non_compliant | `"content": "\"content\": \"\\\/\\\/ Debugging esse...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2108 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2109 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2113 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2115 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2116 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2117 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2121 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2123 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2124 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2125 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2129 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2131 | non_compliant | `"content": "\"content\": \"public function test_pr...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2132 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2133 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2137 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2139 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2140 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2141 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2145 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2147 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2148 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2149 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2153 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2155 | non_compliant | `"content": "\"content\": \"\\\/\\\/ Preview genera...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2156 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2157 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2161 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2163 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2164 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2165 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2169 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2171 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2172 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2173 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2177 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2179 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2180 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2181 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2185 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2187 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2188 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2189 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2193 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2195 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2196 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2197 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2201 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2203 | non_compliant | `"content": "\"content\": \"$this->assertNotEquals(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2204 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2205 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2209 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2211 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2212 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2213 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2217 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2219 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2220 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2221 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2225 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2227 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2228 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2229 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2233 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2235 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2236 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2237 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2241 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2243 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2244 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2245 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2249 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2251 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2252 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2253 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2257 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2259 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2260 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2261 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2265 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2267 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2268 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2269 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2273 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2275 | non_compliant | `"content": "\"content\": \"public function debug_l...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2276 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2277 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2281 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2283 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2284 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2285 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2289 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2291 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2292 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2293 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2297 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2299 | non_compliant | `"content": "\"content\": \"$channel = 'debug_chann...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2300 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2301 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2305 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2307 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2308 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2309 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2313 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2315 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2316 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2317 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2321 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2323 | non_compliant | `"content": "\"content\": \"$message = 'Debug messa...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2324 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2325 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2329 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2331 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2332 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2333 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2337 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2339 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2340 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2341 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2345 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2347 | non_compliant | `"content": "\"content\": \"$context = ['debug_leve...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2348 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2349 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2353 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2355 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2356 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2357 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2361 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2363 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2364 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2365 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2369 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2371 | non_compliant | `"content": "\"content\": \"->with('debug', $messag...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2372 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2373 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2377 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2379 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2380 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2381 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2385 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2387 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2388 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2389 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2393 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2395 | non_compliant | `"content": "\"content\": \"$this->logService->debu...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2396 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2397 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2401 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2403 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2404 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2405 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2409 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2411 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2412 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2413 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2417 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2419 | non_compliant | `"content": "\"content\": \"$this->assertNotEquals(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2420 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2421 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2425 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2427 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2428 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2429 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2433 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2435 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2436 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2437 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2441 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2443 | non_compliant | `"content": "\"content\": \"$this->assertNotEquals(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2444 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2445 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2449 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2451 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2452 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2453 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2457 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2459 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2460 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2461 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2465 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2467 | non_compliant | `"content": "\"content\": \"$this->assertNotEquals(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2468 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2469 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2473 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2475 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2476 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2477 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2481 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2483 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2484 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2485 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2489 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2491 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2492 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2493 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2497 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2499 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2500 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2501 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2505 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2507 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2508 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2509 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2513 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2515 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2516 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2517 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2521 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2523 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2524 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2525 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2529 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2531 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2532 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2533 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2537 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2539 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2540 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2541 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2545 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2547 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2548 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2549 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2553 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2555 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2556 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2557 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2561 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2563 | non_compliant | `"content": "\"content\": \"\\\/\\\/ Note: ok field...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2564 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2565 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2569 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2571 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2572 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2573 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2577 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2579 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2580 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2581 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2585 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2587 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2588 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2589 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2593 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2595 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2596 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2597 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2601 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2603 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2604 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2605 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2609 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2611 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2612 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2613 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2617 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2619 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2620 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2621 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2625 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2627 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2628 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2629 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2633 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2635 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2636 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2637 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2641 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2643 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2644 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2645 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2649 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2651 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2652 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2653 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2657 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2659 | non_compliant | `"content": "\"content\": \"$this->assertNotEmpty($...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2660 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2661 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2665 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2667 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2668 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2669 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2673 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2675 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2676 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2677 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2681 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2683 | non_compliant | `"content": "\"content\": \"$this->assertNotEquals(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2684 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2685 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2689 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2691 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2692 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2693 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2697 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2699 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2700 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2701 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2705 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2707 | non_compliant | `"content": "\"content\": \"$this->assertNotEquals(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2708 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2709 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2713 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2715 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2716 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2717 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2721 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2723 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2724 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2725 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2729 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2731 | non_compliant | `"content": "\"content\": \"$this->markTestSkipped(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2732 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2733 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2737 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2739 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2740 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2741 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2745 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2747 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2748 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2749 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2753 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2755 | non_compliant | `"content": "\"content\": \"$this->markTestSkipped(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2756 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2757 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2761 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2763 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2764 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2765 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2769 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2771 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2772 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2773 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2777 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2779 | non_compliant | `"content": "\"content\": \"$this->markTestSkipped(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2780 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2781 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2785 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2787 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2788 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2789 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2793 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2795 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2796 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2797 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2801 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2803 | non_compliant | `"content": "\"content\": \"$this->markTestSkipped(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2804 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2805 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2809 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2811 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2812 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2813 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2817 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2819 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2820 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2821 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2825 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2827 | non_compliant | `"content": "\"content\": \"$this->markTestSkipped(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2828 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2829 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2833 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2835 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2836 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2837 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2841 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2843 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2844 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2845 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2849 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2851 | non_compliant | `"content": "\"content\": \"$this->markTestSkipped(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2852 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2853 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2857 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2859 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2860 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2861 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2865 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2867 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2868 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2869 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2873 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2875 | non_compliant | `"content": "\"content\": \"$this->markTestSkipped(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2876 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2877 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2881 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2883 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2884 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2885 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2889 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2891 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2892 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2893 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2897 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2899 | non_compliant | `"content": "\"content\": \"$this->markTestSkipped(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2900 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2901 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2905 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2907 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2908 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2909 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2913 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2915 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2916 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2917 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2921 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2923 | non_compliant | `"content": "\"content\": \"$this->assertNotEquals(...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2924 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2925 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2929 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2931 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2932 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2933 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2937 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2939 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2940 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2941 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2945 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2947 | non_compliant | `"content": "\"content\": \"Log::debug('Load test o...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2948 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2949 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2953 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2955 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2956 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2957 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2961 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2963 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2964 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2965 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2969 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2971 | non_compliant | `"content": "\"content\": \"Log::debug(\\\"Recovery...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2972 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2973 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2977 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2979 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2980 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2981 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2985 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2987 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2988 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2989 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2993 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2995 | non_compliant | `"content": "\"content\": \"description: Unique req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2996 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 2997 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3001 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3003 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3004 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3005 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3009 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3011 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3012 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3013 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3017 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3019 | non_compliant | `"content": "\"content\": \"$this->info('--- DEBUG ...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3020 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3021 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3025 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3027 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3028 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3029 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3033 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3035 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3036 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3037 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3041 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3043 | non_compliant | `"content": "\"content\": \"\\\/\\\/ Log slow queri...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3044 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3045 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3049 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3051 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3052 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3053 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3057 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3059 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3060 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3061 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3065 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3067 | non_compliant | `"content": "\"content\": \"Log::debug('PostgreSQL ...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3068 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3069 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3073 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3075 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3076 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3077 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3081 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3083 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3084 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3085 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3089 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3091 | non_compliant | `"content": "\"content\": \"'note' => 'Queue not av...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3092 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3093 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3097 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3099 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3100 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3101 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3105 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3107 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3108 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3109 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3113 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3115 | non_compliant | `"content": "\"content\": \"['Debug Mode', config('...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3116 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3117 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3121 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3123 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3124 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3125 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3129 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3131 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3132 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3133 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3137 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3139 | non_compliant | `"content": "\"content\": \"\\\/\\\/ TODO: Ger\\u00...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3140 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3141 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3145 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3147 | non_compliant | `"content": "\"pattern\": \"TODO\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3148 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3149 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3153 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3155 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3156 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3157 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3161 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3163 | non_compliant | `"content": "\"content\": \"'notes' => [\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3164 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3165 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3169 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3171 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3172 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3173 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3177 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3179 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3180 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3181 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3185 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3187 | non_compliant | `"content": "\"content\": \"'notes' => [\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3188 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3189 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3193 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3195 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3196 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3197 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3201 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3203 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3204 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3205 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3209 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3211 | non_compliant | `"content": "\"content\": \"if (isset($help['notes'...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3212 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3213 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3217 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3219 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3220 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3221 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3225 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3227 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3228 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3229 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3233 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3235 | non_compliant | `"content": "\"content\": \"$this->info('Notes:');\...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3236 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3237 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3241 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3243 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3244 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3245 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3249 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3251 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3252 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3253 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3257 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3259 | non_compliant | `"content": "\"content\": \"foreach ($help['notes']...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3260 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3261 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3265 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3267 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3268 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3269 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3273 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3275 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3276 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3277 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3281 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3283 | non_compliant | `"content": "\"content\": \"$this->line(\\\"  \\u20...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3284 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3285 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3289 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3291 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3292 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3293 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3297 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3299 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3300 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3301 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3305 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3307 | non_compliant | `"content": "\"content\": \"'disable_web_page_previ...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3308 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3309 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3313 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3315 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3316 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3317 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3321 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3323 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3324 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3325 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3329 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3331 | non_compliant | `"content": "\"content\": \"$levels = ['debug' => 1...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3332 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3333 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3337 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3339 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3340 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3341 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3345 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3347 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3348 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3349 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3353 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3355 | non_compliant | `"content": "\"content\": \"'stack_trace' => debug_...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3356 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3357 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3361 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3363 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3364 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3365 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3369 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3371 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3372 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3373 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3377 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3379 | non_compliant | `"content": "\"content\": \"'note' => 'Login histor...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3380 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3381 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3385 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3387 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3388 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3389 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3393 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3395 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3396 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3397 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3401 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3403 | non_compliant | `"content": "\"content\": \"'note' => 'API usage st...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3404 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3405 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3409 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3411 | non_compliant | `"content": "\"pattern\": \"NOTE\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3412 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3413 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3417 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3419 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3420 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3421 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3425 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3427 | non_compliant | `"content": "\"content\": \"Log::debug('Usage track...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3428 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3429 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3433 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3435 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3436 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3437 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3441 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3443 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3444 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3445 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3449 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3451 | non_compliant | `"content": "\"content\": \"'last_retention_review'...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3452 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3453 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3457 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3459 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3460 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3461 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3465 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3467 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3468 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3469 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3473 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3475 | non_compliant | `"content": "\"content\": \"\\\/\\\/ Debug: Hangi p...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3476 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3477 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3481 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3483 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3484 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3485 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3489 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3491 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3492 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3493 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3497 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3499 | non_compliant | `"content": "\"content\": \"\\\/\\\/ Debug bilgisi ...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3500 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3501 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3505 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3507 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3508 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3509 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3513 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3515 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3516 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3517 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3521 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3523 | non_compliant | `"content": "\"content\": \"Log::debug('CYCLE_RUNNE...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3524 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3525 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3529 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3531 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3532 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3533 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3537 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3539 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3540 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3541 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3545 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3547 | non_compliant | `"content": "\"content\": \"Log::debug('CYCLE_RUNNE...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3548 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3549 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3553 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3555 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3556 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3557 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3561 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3563 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3564 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3565 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3569 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3571 | non_compliant | `"content": "\"content\": \"Log::debug('RISK_GATE_C...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3572 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3573 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3577 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3579 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3580 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3581 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3585 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3587 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3588 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3589 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3593 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3595 | non_compliant | `"content": "\"content\": \"Log::debug('TRADE_MANAG...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3596 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3597 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3601 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3603 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3604 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3605 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3609 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3611 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3612 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3613 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3617 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3619 | non_compliant | `"content": "\"content\": \"$recommendations[] = 'R...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3620 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3621 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3625 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3627 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3628 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3629 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3633 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3635 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3636 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3637 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3641 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3643 | non_compliant | `"content": "\"content\": \"$recommendations[] = 'R...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3644 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3645 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3649 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3651 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3652 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3653 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3657 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3659 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3660 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3661 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3665 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3667 | non_compliant | `"content": "\"content\": \"$recommendations[] = 'R...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3668 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3669 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3673 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3675 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3676 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3677 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3681 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3683 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3684 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3685 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3689 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3691 | non_compliant | `"content": "\"content\": \"$recommendations[] = 'R...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3692 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3693 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3697 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3699 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3700 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3701 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3705 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3707 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3708 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3709 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3713 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3715 | non_compliant | `"content": "\"content\": \"'security', 'breach', '...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3716 | non_compliant | `"pattern": "HACK",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3717 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3721 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3723 | non_compliant | `"content": "\"pattern\": \"HACK\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3724 | non_compliant | `"pattern": "HACK",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3725 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3729 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3731 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3732 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3733 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3737 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3739 | non_compliant | `"content": "\"content\": \"'security', 'breach', '...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3740 | non_compliant | `"pattern": "HACK",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3741 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3745 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3747 | non_compliant | `"content": "\"pattern\": \"HACK\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3748 | non_compliant | `"pattern": "HACK",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3749 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3753 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3755 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3756 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3757 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3761 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3763 | non_compliant | `"content": "\"content\": \"'Critical announcements...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3764 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3765 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3769 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3771 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3772 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3773 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3777 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3779 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3780 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3781 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3785 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3787 | non_compliant | `"content": "\"content\": \"Log::debug('Telegram me...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3788 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3789 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3793 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3795 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3796 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3797 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3801 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3803 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3804 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3805 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3809 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3811 | non_compliant | `"content": "\"content\": \"Log::debug('Circuit bre...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3812 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3813 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3817 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3819 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3820 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3821 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3825 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3827 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3828 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3829 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3833 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3835 | non_compliant | `"content": "\"content\": \"'prompt_preview' => sub...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3836 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3837 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3841 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3843 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3844 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3845 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3849 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3851 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3852 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3853 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3857 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3859 | non_compliant | `"content": "\"content\": \"Review the initial deci...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3860 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3861 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3865 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3867 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3868 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3869 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3873 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3875 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3876 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3877 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3881 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3883 | non_compliant | `"content": "\"content\": \"'LOW' => 'debug',\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3884 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3885 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3889 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3891 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3892 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3893 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3897 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3899 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3900 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3901 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3905 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3907 | non_compliant | `"content": "\"content\": \"\\\"## Manual Review Re...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3908 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3909 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3913 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3915 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3916 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3917 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3921 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3923 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3924 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3925 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3929 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3931 | non_compliant | `"content": "\"content\": \"* Audit log for complia...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3932 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3933 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3937 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3939 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3940 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3941 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3945 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3947 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3948 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3949 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3953 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3955 | non_compliant | `"content": "\"content\": \"'preview' => substr($re...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3956 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3957 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3961 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3963 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3964 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3965 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3969 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3971 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3972 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3973 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3977 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3979 | non_compliant | `"content": "\"content\": \"default => 'Review trad...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3980 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3981 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3985 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3987 | non_compliant | `"content": "\"pattern\": \"REVIEW\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3988 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3989 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3993 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3995 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3996 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 3997 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4001 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4003 | non_compliant | `"content": "\"content\": \"* Log with debug level\...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4004 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4005 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4009 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4011 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4012 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4013 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4017 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4019 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4020 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4021 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4025 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4027 | non_compliant | `"content": "\"content\": \"public function debug(s...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4028 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4029 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4033 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4035 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4036 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4037 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4041 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4043 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4044 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4045 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4049 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4051 | non_compliant | `"content": "\"content\": \"$this->log($channel, 'd...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4052 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4053 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4057 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4059 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4060 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4061 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4065 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4067 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4068 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4069 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4073 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4075 | non_compliant | `"content": "\"content\": \"\\\"Bug\\u00fcn ne yap\...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4076 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4077 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4081 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4083 | non_compliant | `"content": "\"pattern\": \"BUG\",",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4084 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4085 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4089 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4091 | non_compliant | `"content": "\"message\": \"TODO comment not in req...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4092 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4093 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4097 | non_compliant | `"file": "reports\/todo_violations.json",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4099 | non_compliant | `"content": "\"todos\": []",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4100 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4101 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4107 | non_compliant | `"content": "$this->info('--- DEBUG ---');",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4108 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4109 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4115 | non_compliant | `"content": "\/\/ Log slow queries (for debugging a...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4116 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4117 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4123 | non_compliant | `"content": "Log::debug('PostgreSQL timeouts config...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4124 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4125 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4131 | non_compliant | `"content": "'note' => 'Queue not available, using ...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4132 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4133 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4139 | non_compliant | `"content": "['Debug Mode', config('app.debug') ? '...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4140 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4141 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4147 | non_compliant | `"content": "\/\/ TODO: Ger\u00e7ek pozisyon a\u00e...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4148 | non_compliant | `"pattern": "TODO",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4149 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4155 | non_compliant | `"content": "'notes' => [",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4156 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4157 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4163 | non_compliant | `"content": "'notes' => [",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4164 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4165 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4171 | non_compliant | `"content": "if (isset($help['notes'])) {",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4172 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4173 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4179 | non_compliant | `"content": "$this->info('Notes:');",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4180 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4181 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4187 | non_compliant | `"content": "foreach ($help['notes'] as $note) {",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4188 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4189 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4195 | non_compliant | `"content": "$this->line(\"  \u2022 {$note}\");",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4196 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4197 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4203 | non_compliant | `"content": "'disable_web_page_preview' => true,",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4204 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4205 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4211 | non_compliant | `"content": "$levels = ['debug' => 1, 'info' => 2, ...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4212 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4213 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4219 | non_compliant | `"content": "'stack_trace' => debug_backtrace(DEBUG...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4220 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4221 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4227 | non_compliant | `"content": "'note' => 'Login history for the last ...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4228 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4229 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4235 | non_compliant | `"content": "'note' => 'API usage statistics',",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4236 | non_compliant | `"pattern": "NOTE",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4237 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4243 | non_compliant | `"content": "Log::debug('Usage tracked', [",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4244 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4245 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4251 | non_compliant | `"content": "'last_retention_review' => now()->subM...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4252 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4253 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4259 | non_compliant | `"content": "\/\/ Debug: Hangi parametrelerin geldi...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4260 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4261 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4267 | non_compliant | `"content": "\/\/ Debug bilgisi ekle",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4268 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4269 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4275 | non_compliant | `"content": "Log::debug('CYCLE_RUNNER_START', ['sym...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4276 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4277 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4283 | non_compliant | `"content": "Log::debug('CYCLE_RUNNER_LOCK_ACQUIRED...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4284 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4285 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4291 | non_compliant | `"content": "Log::debug('RISK_GATE_CONFIG', ['enabl...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4292 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4293 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4299 | non_compliant | `"content": "Log::debug('TRADE_MANAGER_CHECK', ['ha...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4300 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4301 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4307 | non_compliant | `"content": "$recommendations[] = 'Review stop-loss...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4308 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4309 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4315 | non_compliant | `"content": "$recommendations[] = 'Review entry cri...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4316 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4317 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4323 | non_compliant | `"content": "$recommendations[] = 'Review exit stra...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4324 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4325 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4331 | non_compliant | `"content": "$recommendations[] = 'Review risk-rewa...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4332 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4333 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4339 | non_compliant | `"content": "'security', 'breach', 'hack', 'comprom...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4340 | non_compliant | `"pattern": "HACK",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4341 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4347 | non_compliant | `"content": "'security', 'breach', 'hack', 'comprom...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4348 | non_compliant | `"pattern": "HACK",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4349 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4355 | non_compliant | `"content": "'Critical announcements detected - imm...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4356 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4357 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4363 | non_compliant | `"content": "Log::debug('Telegram message delete fa...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4364 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4365 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4371 | non_compliant | `"content": "Log::debug('Circuit breaker success', ...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4372 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4373 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4379 | non_compliant | `"content": "'prompt_preview' => substr($prompt, 0,...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4380 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4381 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4387 | non_compliant | `"content": "Review the initial decisions from othe...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4388 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4389 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4395 | non_compliant | `"content": "'LOW' => 'debug',",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4396 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4397 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4403 | non_compliant | `"content": "\"## Manual Review Required\\n\" .",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4404 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4405 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4411 | non_compliant | `"content": "* Audit log for compliance and debuggi...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4412 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4413 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4419 | non_compliant | `"content": "'preview' => substr($response, 0, 100)...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4420 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4421 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4427 | non_compliant | `"content": "default => 'Review trade parameters',"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4428 | non_compliant | `"pattern": "REVIEW",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4429 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4435 | non_compliant | `"content": "* Log with debug level",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4436 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4437 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4443 | non_compliant | `"content": "public function debug(string $channel,...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4444 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4445 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4451 | non_compliant | `"content": "$this->log($channel, 'debug', $message...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4452 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4453 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4459 | non_compliant | `"content": "\"Bug\u00fcn ne yap\u0131yoruz? \ud83d...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4460 | non_compliant | `"pattern": "BUG",...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4461 | non_compliant | `"message": "TODO comment not in required format"...` | TODO comment not in required format |
| `reports/todo_violations.json` | 4464 | non_compliant | `"todos": []...` | TODO comment not in required format |
| `routes/console.php` | 40 | non_compliant | `$this->info('--- DEBUG ---');...` | TODO comment not in required format |
| `app/Providers/DatabaseServiceProvider.php` | 43 | non_compliant | `// Log slow queries (for debugging and optimizatio...` | TODO comment not in required format |
| `app/Providers/DatabaseServiceProvider.php` | 82 | non_compliant | `Log::debug('PostgreSQL timeouts configured', [...` | TODO comment not in required format |
| `app/Console/Commands/PerformanceMonitorCommand.php` | 247 | non_compliant | `'note' => 'Queue not available, using defaults',...` | TODO comment not in required format |
| `app/Console/Commands/PerformanceMonitor.php` | 123 | non_compliant | `['Debug Mode', config('app.debug') ? 'ON' : 'OFF']...` | TODO comment not in required format |
| `app/Console/Commands/OpenSpecificCommand.php` | 124 | expired_todo | `// ALLOWTODO: SENTX-001 2025-08-27 Gerçek pozisyo...` | TODO expired on 2025-08-27 |
| `app/Console/Commands/HelpCommand.php` | 93 | non_compliant | `'notes' => [...` | TODO comment not in required format |
| `app/Console/Commands/HelpCommand.php` | 135 | non_compliant | `'notes' => [...` | TODO comment not in required format |
| `app/Console/Commands/HelpCommand.php` | 185 | non_compliant | `if (isset($help['notes'])) {...` | TODO comment not in required format |
| `app/Console/Commands/HelpCommand.php` | 186 | non_compliant | `$this->info('Notes:');...` | TODO comment not in required format |
| `app/Console/Commands/HelpCommand.php` | 187 | non_compliant | `foreach ($help['notes'] as $note) {...` | TODO comment not in required format |
| `app/Console/Commands/HelpCommand.php` | 188 | non_compliant | `$this->line("  • {$note}");...` | TODO comment not in required format |
| `app/Services/Notifier/TelegramNotifier.php` | 41 | non_compliant | `'disable_web_page_preview' => true,...` | TODO comment not in required format |
| `app/Services/Notifier/AlertDispatcher.php` | 159 | non_compliant | `$levels = ['debug' => 1, 'info' => 2, 'warning' =>...` | TODO comment not in required format |
| `app/Services/Observability/EnhancedStructuredLogger.php` | 276 | non_compliant | `'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_I...` | TODO comment not in required format |
| `app/Services/GDPR/DataExportService.php` | 222 | non_compliant | `'note' => 'Login history for the last 90 days',...` | TODO comment not in required format |
| `app/Services/GDPR/DataExportService.php` | 226 | non_compliant | `'note' => 'API usage statistics',...` | TODO comment not in required format |
| `app/Services/Billing/SubscriptionService.php` | 103 | non_compliant | `Log::debug('Usage tracked', [...` | TODO comment not in required format |
| `app/Services/Billing/GdprService.php` | 470 | non_compliant | `'last_retention_review' => now()->subMonths(6)->to...` | TODO comment not in required format |
| `app/Services/CycleRunner.php` | 116 | non_compliant | `// Debug: Hangi parametrelerin geldiğini göster...` | TODO comment not in required format |
| `app/Services/CycleRunner.php` | 129 | non_compliant | `// Debug bilgisi ekle...` | TODO comment not in required format |
| `app/Services/CycleRunner.php` | 158 | non_compliant | `Log::debug('CYCLE_RUNNER_START', ['symbol' => $sym...` | TODO comment not in required format |
| `app/Services/CycleRunner.php` | 161 | non_compliant | `Log::debug('CYCLE_RUNNER_LOCK_ACQUIRED', ['symbol'...` | TODO comment not in required format |
| `app/Services/CycleRunner.php` | 210 | non_compliant | `Log::debug('RISK_GATE_CONFIG', ['enable_composite_...` | TODO comment not in required format |
| `app/Services/CycleRunner.php` | 298 | non_compliant | `Log::debug('TRADE_MANAGER_CHECK', ['has_method' =>...` | TODO comment not in required format |
| `app/Services/Lab/PerformanceMonitorService.php` | 352 | non_compliant | `$recommendations[] = 'Review stop-loss and risk ma...` | TODO comment not in required format |
| `app/Services/Lab/PerformanceMonitorService.php` | 356 | non_compliant | `$recommendations[] = 'Review entry criteria and fi...` | TODO comment not in required format |
| `app/Services/Lab/PerformanceMonitorService.php` | 361 | non_compliant | `$recommendations[] = 'Review exit strategies and t...` | TODO comment not in required format |
| `app/Services/Lab/PerformanceMonitorService.php` | 366 | non_compliant | `$recommendations[] = 'Review risk-reward ratios fo...` | TODO comment not in required format |
| `app/Services/Health/AnnouncementWatcher.php` | 234 | non_compliant | `'security', 'breach', 'hack', 'compromise', 'vulne...` | TODO comment not in required format |
| `app/Services/Health/AnnouncementWatcher.php` | 268 | non_compliant | `'security', 'breach', 'hack', 'compromise', 'vulne...` | TODO comment not in required format |
| `app/Services/Health/AnnouncementWatcher.php` | 366 | non_compliant | `'Critical announcements detected - immediate revie...` | TODO comment not in required format |
| `app/Services/Health/LiveHealthCheckService.php` | 129 | non_compliant | `Log::debug('Telegram message delete failed', ['err...` | TODO comment not in required format |
| `app/Services/Reliability/CircuitBreakerService.php` | 212 | non_compliant | `Log::debug('Circuit breaker success', [...` | TODO comment not in required format |
| `app/Services/AI/Prompt/PromptSecurityGuard.php` | 100 | non_compliant | `'prompt_preview' => substr($prompt, 0, 50).'...',...` | TODO comment not in required format |
| `app/Services/AI/PromptFactory.php` | 36 | non_compliant | `Review the initial decisions from other AI provide...` | TODO comment not in required format |
| `app/Services/Risk/DriftGuardService.php` | 214 | non_compliant | `'LOW' => 'debug',...` | TODO comment not in required format |
| `app/Services/Telegram/TelegramApprovalService.php` | 266 | non_compliant | `"## Manual Review Required\n"....` | TODO comment not in required format |
| `app/Services/Telegram/TelegramGatewayService.php` | 97 | non_compliant | `* Audit log for compliance and debugging...` | TODO comment not in required format |
| `app/Services/Telegram/TelegramGatewayService.php` | 120 | non_compliant | `'preview' => substr($response, 0, 100),...` | TODO comment not in required format |
| `app/Services/Trading/LeverageCalculatorService.php` | 247 | non_compliant | `default => 'Review trade parameters',...` | TODO comment not in required format |
| `app/Services/Logger/StructuredLogService.php` | 96 | non_compliant | `* Log with debug level...` | TODO comment not in required format |
| `app/Services/Logger/StructuredLogService.php` | 98 | non_compliant | `public function debug(string $channel, string $mes...` | TODO comment not in required format |
| `app/Services/Logger/StructuredLogService.php` | 100 | non_compliant | `$this->log($channel, 'debug', $message, $context);...` | TODO comment not in required format |
| `app/Http/Controllers/TelegramWebhookController.php` | 202 | non_compliant | `"Bugün ne yapıyoruz? 💪\n\n"....` | TODO comment not in required format |

## 📋 Required TODO Format

All TODO/FIXME/HACK comments must follow this format:

```
// ALLOWTODO: <JIRA|ISSUE-ID> <YYYY-MM-DD> <single sentence reason>
```

**Examples:**
```php
// ALLOWTODO: SENTX-123 2025-02-15 Real position opening code needs implementation
// ALLOWTODO: ISSUE-456 2025-01-30 Temporary workaround for API rate limiting
// ALLOWTODO: TECH-789 2025-03-01 Performance optimization pending team review
```

## 🔧 Fixing Violations

1. **Non-compliant TODOs**: Update to use ALLOWTODO format
2. **Expired TODOs**: Update date or resolve the issue
3. **Invalid dates**: Use proper YYYY-MM-DD format

## ⚙️ Scanner Configuration

- **Patterns**: TODO, FIXME, HACK, XXX, BUG, NOTE, REVIEW
- **Extensions**: php, js, ts, vue, blade.php, css, scss, yaml, yml, json
- **Excluded paths**: vendor/, node_modules/, .git/, storage/, bootstrap/cache/, public/build/, coverage-html/, deploy/ubuntu24/, scripts/todo-sweeper.php
