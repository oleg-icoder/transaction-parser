<?php
class TransactionParser
{
    private $filePath;
    private $euCountries = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU',
        'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PO', 'PT', 'RO', 'SE', 'SI', 'SK'
    ];
    private $binListUrl = 'https://lookup.binlist.net/';
    private $exchangeRatesUrl = 'https://api.exchangeratesapi.io/latest';

    function __construct(string $path)
    {
        $this->filePath = $path;
    }

    public function processTransactions() {
        $handle = fopen($this->filePath, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                echo $this->processTransaction($line);
                print "\n";
            }
            fclose($handle);
        }
    }

    public function getCountryCode(int $bin): string {
        $response = file_get_contents($this->binListUrl . $bin);
        if (empty($response)) {
            die('error!');
        }
        $response = json_decode($response);
        return $response->country->alpha2;
    }
    public function convertToEur(float $amount, string $currency): float {
        if ($currency === 'EUR') {
            return $amount;
        }

        $rate = $this->getExchangeRate($currency);
        return $rate == 0 ? $amount : $amount / $rate;
    }
    public function getExchangeRate(string $currency): float {
        $response = file_get_contents($this->exchangeRatesUrl);
        $response = json_decode($response);
        return $response->rates->$currency;
    }

    public function getCommission(int $bin): float {
        return $this->isEuCountry($this->getCountryCode($bin)) ? 0.01 : 0.02;
    }

    private function processTransaction($transaction): float {
        $transaction = json_decode($transaction);

        $amount = $this->convertToEur($transaction->amount, $transaction->currency);
        return $amount * $this->getCommission($transaction->bin);
    }

    public function isEuCountry(string $countryCode): bool {
        return in_array($countryCode, $this->euCountries);
    }
}

$parser = new TransactionParser('./fixtures/transactions.txt');
$parser->processTransactions();