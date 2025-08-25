<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\AiDecision;

/**
 * Tüm AI sağlayıcılarının uygulaması gereken arayüz (sözleşme).
 * ConsensusService'in beklentileriyle tam uyumlu hale getirilmiştir.
 */
interface AiProvider
{
    /**
     * Sağlayıcının aktif olup olmadığını kontrol eder.
     */
    public function enabled(): bool;

    /**
     * Sağlayıcının benzersiz adını döndürür.
     */
    public function name(): string;

    /**
     * Verilen piyasa verilerine göre bir karar üretir.
     * Bu metod, ConsensusService tarafından çağrılır.
     *
     * @param  array  $snapshot  Karar bağlamı (fiyat, ATR, vb.)
     * @param  string  $stage  Karar turu ('R1' | 'R2')
     * @param  string  $symbol  İşlem yapılan sembol (örn. BTCUSDT)
     * @return AiDecision Karar nesnesi.
     */
    public function decide(array $snapshot, string $stage, string $symbol): AiDecision;
}
