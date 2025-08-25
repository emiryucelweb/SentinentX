<?php

declare(strict_types=1);

namespace App\Contracts\Trading;

use App\Models\Trade;

interface PnlServiceInterface
{
    /**
     * PnL hesapla ve persist et
     *
     * @param  Trade  $trade  Trade modeli
     * @param  string  $closeOrderLinkId  Kapanış order link ID
     * @return array PnL sonuçları
     */
    public function computeAndPersist(Trade $trade, string $closeOrderLinkId): array;
}
