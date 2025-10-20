<?php

declare(strict_types=1);

namespace App\Service;

class PricingService
{
    private const DEFAULT_COUNTRY_KEY = 'default';

    /** @var array<string, array<string, mixed>> */
    private array $pricingConfig;

    private string $defaultKey;

    private ?string $geoServiceBaseUrl;

    public function __construct(array $pricingConfig, ?string $geoServiceBaseUrl = null, ?string $defaultKey = null)
    {
        $this->pricingConfig = $pricingConfig;
        $this->geoServiceBaseUrl = $geoServiceBaseUrl ?? 'http://geoip/country';
        $this->defaultKey = $defaultKey ?? self::DEFAULT_COUNTRY_KEY;
    }

    /**
     * @param array<string, mixed> $serverParams
     * @param array<string, mixed> $session
     *
     * @return array<string, mixed>
     */
    public function resolvePricing(array $serverParams, array &$session): array
    {
        $pricingKey = $this->determinePricingCountry($serverParams, $session);
        $pricing = $this->pricingConfig[$pricingKey] ?? $this->pricingConfig[$this->defaultKey];

        $currentPrice = $this->formatPriceValue($pricing);
        $oldPrice = $this->formatPriceValue($pricing, 'old_amount');
        $priceForMeta = $currentPrice;
        $discountPercent = $this->calculateDiscountPercent($pricing);

        if ($oldPrice !== null) {
            $priceForMeta .= ' вместо ' . $oldPrice;
        }

        return [
            'key' => $pricingKey,
            'pricing' => $pricing,
            'currentPrice' => $currentPrice,
            'oldPrice' => $oldPrice,
            'priceForMeta' => $priceForMeta,
            'discountPercent' => $discountPercent,
            'markup' => [
                'default' => $this->renderPriceMarkup($currentPrice, $oldPrice),
                'strong' => $this->renderPriceMarkup($currentPrice, $oldPrice, 'strong'),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function calculateDiscountPercent(array $settings): ?int
    {
        if (!isset($settings['amount'], $settings['old_amount'])) {
            return null;
        }

        if (!is_numeric($settings['amount']) || !is_numeric($settings['old_amount'])) {
            return null;
        }

        $currentAmount = (float) $settings['amount'];
        $oldAmount = (float) $settings['old_amount'];

        if ($oldAmount <= 0.0) {
            return null;
        }

        $discount = (($oldAmount - $currentAmount) / $oldAmount) * 100.0;

        if (!is_finite($discount)) {
            return null;
        }

        if ($discount < 0.0) {
            $discount = 0.0;
        }

        return (int) round($discount);
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function formatPriceValue(array $settings, string $amountKey = 'amount'): ?string
    {
        if (!isset($settings[$amountKey]) || !is_numeric($settings[$amountKey])) {
            return null;
        }

        $amount = (float) $settings[$amountKey];
        $decimals = (int) ($settings['decimals'] ?? 0);
        $decimalSeparator = is_string($settings['decimal_separator'] ?? null) ? $settings['decimal_separator'] : ',';
        $thousandsSeparator = is_string($settings['thousands_separator'] ?? null) ? $settings['thousands_separator'] : ' ';
        $formattedAmount = number_format($amount, $decimals, $decimalSeparator, $thousandsSeparator);

        $symbol = $settings['currency_symbol'] ?? '';
        if (!is_string($symbol) || $symbol === '') {
            return $formattedAmount;
        }

        $position = strtolower((string) ($settings['symbol_position'] ?? 'suffix'));
        $separator = $settings['symbol_separator'] ?? ($position === 'suffix' ? ' ' : '');
        if (!is_string($separator)) {
            $separator = $position === 'suffix' ? ' ' : '';
        }

        if ($position === 'prefix') {
            return $symbol . $separator . $formattedAmount;
        }

        return $formattedAmount . $separator . $symbol;
    }

    private function renderPriceMarkup(?string $currentPrice, ?string $oldPrice = null, string $containerTag = 'span', string $baseClass = 'price'): string
    {
        if ($currentPrice === null) {
            return '';
        }

        if (!preg_match('/^[a-z]+$/i', $containerTag)) {
            $containerTag = 'span';
        }

        $classList = trim($baseClass . ($oldPrice !== null ? ' ' . $baseClass . '--discount' : ''));
        $currentHtml = htmlspecialchars($currentPrice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if ($oldPrice !== null) {
            $oldHtml = htmlspecialchars($oldPrice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            return sprintf(
                '<%1$s class="%2$s"><span class="price__old"><s>%3$s</s></span> <span class="price__new">%4$s</span></%1$s>',
                $containerTag,
                $classList,
                $oldHtml,
                $currentHtml
            );
        }

        return sprintf('<%1$s class="%2$s">%3$s</%1$s>', $containerTag, $classList, $currentHtml);
    }

    /**
     * @param array<string, mixed> $serverParams
     * @param array<string, mixed> $session
     */
    private function determinePricingCountry(array $serverParams, array &$session): string
    {
        $sessionCountry = $session['pricing_country_code'] ?? null;
        if (is_string($sessionCountry) && isset($this->pricingConfig[$sessionCountry])) {
            return $sessionCountry;
        }

        if ($this->isLikelyBotRequest($serverParams)) {
            return $this->defaultKey;
        }

        $clientIp = $this->getClientIpAddress($serverParams);
        if ($clientIp === null) {
            return $this->defaultKey;
        }

        $countryCode = $this->lookupCountryCodeByIp($clientIp);
        if ($countryCode !== null && isset($this->pricingConfig[$countryCode])) {
            $session['pricing_country_code'] = $countryCode;

            return $countryCode;
        }

        return $this->defaultKey;
    }

    /**
     * @param array<string, mixed> $serverParams
     */
    private function getClientIpAddress(array $serverParams): ?string
    {
        $candidates = [];

        if (!empty($serverParams['HTTP_CLIENT_IP'])) {
            $candidates[] = (string) $serverParams['HTTP_CLIENT_IP'];
        }

        if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $forwardedFor = explode(',', (string) $serverParams['HTTP_X_FORWARDED_FOR']);
            foreach ($forwardedFor as $ip) {
                $candidates[] = trim($ip);
            }
        }

        if (!empty($serverParams['REMOTE_ADDR'])) {
            $candidates[] = (string) $serverParams['REMOTE_ADDR'];
        }

        foreach ($candidates as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }

        foreach ($candidates as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $serverParams
     */
    private function isLikelyBotRequest(array $serverParams): bool
    {
        $userAgent = $serverParams['HTTP_USER_AGENT'] ?? '';
        if ($userAgent === '' || !is_string($userAgent)) {
            return true;
        }

        $botSignatures = [
            'bot',
            'crawl',
            'spider',
            'slurp',
            'mediapartners-google',
            'bingpreview',
            'pingdom',
            'monitor',
            'curl',
            'wget',
            'httpclient',
            'python-requests',
        ];

        $normalized = strtolower($userAgent);
        foreach ($botSignatures as $signature) {
            if (strpos($normalized, $signature) !== false) {
                return true;
            }
        }

        return false;
    }

    private function lookupCountryCodeByIp(string $ip): ?string
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        $serviceBaseUrl = rtrim($this->geoServiceBaseUrl ?? '', '/');
        if ($serviceBaseUrl === '') {
            return null;
        }

        $endpoint = sprintf('%s?ip=%s', $serviceBaseUrl, rawurlencode($ip));
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_TIMEOUT => 2,
        ]);

        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            error_log(sprintf('IP lookup failed: ip=%s error=%s', $ip, curl_error($ch)));
            curl_close($ch);

            return null;
        }

        if ($httpStatus >= 400) {
            error_log(sprintf('IP lookup failed: ip=%s http_status=%s', $ip, $httpStatus));
            curl_close($ch);

            return null;
        }

        curl_close($ch);

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return null;
        }

        $country = $data['data']['iso_code'] ?? $data['country'] ?? $data['country_code'] ?? $data['countryCode'] ?? null;
        if (!is_string($country) || $country === '') {
            return null;
        }

        $country = strtoupper($country);
        if (strlen($country) !== 2) {
            return null;
        }

        return $country;
    }
}
