#!/usr/bin/env bash
set -euo pipefail

DRY_RUN="${DRY_RUN:-1}"  # 1: sadece echo, 0: uygula

run() {
  if [[ "$DRY_RUN" = "1" ]]; then echo "[DRY-RUN] $*"; else eval "$@"; fi
}

echo "===> SENTINENTX Scaffold (idempotent). DRY_RUN=$DRY_RUN"

# Klasörler
for d in \
  docs scripts tools \
  app/Contracts app/DTO app/Services/AI app/Services/Market app/Services/Exchange \
  app/Services/Trading app/Services/Risk app/Services/Notifier app/Services/Logger \
  app/Services/Billing app/Services/Health app/Services/Ws \
  app/Console/Commands routes tests/Unit tests/Feature resources/views/admin \
  storage/app storage/framework cache storage/logs
do
  run "mkdir -p \"$d\""
  # .gitkeep
  if [[ ! -e "$d/.gitkeep" ]]; then run "touch \"$d/.gitkeep\""; fi
done

# Config dosyaları (varsa dokunma)
[[ -e config/notifier.php ]] || run "cat > config/notifier.php <<'PHP'
<?php
return [
  'telegram' => ['enabled' => true, 'bot_token' => env('TG_BOT_TOKEN'), 'chat_id' => env('TG_CHAT_ID')],
  'slack'    => ['enabled' => false],
  'mail'     => ['enabled' => false],
];
PHP"

[[ -e config/health.php ]] || run "cat > config/health.php <<'PHP'
<?php
return [
  'stablecoin' => ['asset' => 'USDT', 'min' => 0.98, 'max' => 1.02],
  'announcements' => ['exchanges' => ['bybit' => true]],
];
PHP"

# Dokümanlar
for f in docs/TRANSCRIPT.md docs/DECISIONS.md docs/REPO_CLEANUP.md docs/REPO_SCAFFOLD_PLAN.md; do
  [[ -e "$f" ]] || run "printf '' > \"$f\"";
done

# (Yumuşak) taşıma/yeniden adlandırma önerileri — **yorumlu** bırakılır; onayla birlikte açılır.
# run "mv resources/views/ai_payload.blade.php resources/views/admin/ai_payload.blade.php"
# run "mv config/Notifier.php config/notifier.php"

echo "===> Tamam. Bu script yıkıcı değildir; tekrar çalıştırılabilir.
Kullanım:
  DRY_RUN=1 bash scripts/scaffold.sh   # sadece neler olacağını gösterir
  DRY_RUN=0 bash scripts/scaffold.sh   # uygular
"
