<?php

namespace AndreasGlaser\PPC;

use GuzzleHttp\Client;

/**
 * Class PPC
 *
 * @package AndreasGlaser\PPC
 */
class PPC
{
    const RES_BASE = 'https://poloniex.com';
    const RES_TRADING = '/tradingApi';
    const RES_PUBLIC = '/public';
    const CANDLE_STICK_PERIODS = [300, 900, 1800, 7200, 14400, 86400];

    /**
     * @var bool
     */
    protected $enableTrading = false;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * PPC constructor.
     *
     * @param string|null $apiKey
     * @param string|null $apiSecret
     * @param array       $guzzleClientOptions
     */
    public function __construct(string $apiKey = null, string $apiSecret = null, array $guzzleClientOptions = [])
    {
        if (($apiKey || $apiSecret) && (!$apiKey || !$apiSecret)) {
            throw new \LogicException(sprintf('Both "apiKey" and "apiSecret" have to be provided'));
        }

        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;

        if ($this->apiKey) {
            $this->enableTrading = true;
        }

        $defaultOptions = [
            'timeout'  => 10,
            'base_uri' => self::RES_BASE,
        ];

        $guzzleClientOptions = array_replace_recursive($defaultOptions, $guzzleClientOptions);

        $this->httpClient = new Client($guzzleClientOptions);
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient(): Client
    {
        return $this->httpClient;
    }

    /**
     * @param array $params
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function sendTradingRequest(array $params = []): Result
    {
        if (!$this->enableTrading) {
            throw new \LogicException('Trading request are not possible if api key and secret have not been set');
        }

        $mt = explode(' ', microtime());
        $params['nonce'] = $mt[1] . substr($mt[0], 2, 6);

        $postData = http_build_query($params, '', '&');
        $sign = hash_hmac('sha512', $postData, $this->apiSecret);

        $options = [
            'headers'     => [
                'Key'  => $this->apiKey,
                'Sign' => $sign,
            ],
            'form_params' => $params,
        ];

        $response = $this->httpClient->post(self::RES_TRADING, $options);

        return new Result($response);
    }

    /**
     * @param array $params
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function sendPublicRequest(array $params = []): Result
    {
        $response = $this->httpClient->get(self::RES_PUBLIC, ['query' => $params]);

        return new Result($response);
    }

    /**
     * @return \AndreasGlaser\PPC\Result
     */
    public function getTicker(): Result
    {
        return $this->sendPublicRequest([
            'command' => 'returnTicker',
        ]);
    }

    /**
     * @return \AndreasGlaser\PPC\Result
     */
    public function get24hVolume(): Result
    {
        return $this->sendPublicRequest([
            'command' => 'return24hVolume',
        ]);
    }

    /**
     * @param string $currencyPair
     * @param int    $depth
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function getOrderBook(string $currencyPair = 'ALL', int $depth = 10): Result
    {
        return $this->sendPublicRequest([
            'command'      => 'returnOrderBook',
            'currencyPair' => $currencyPair,
            'depth'        => $depth,
        ]);
    }

    /**
     * @param string $currencyPair
     * @param int    $start
     * @param int    $end
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function getPublicTradeHistory(string $currencyPair = 'ALL', int $start, int $end): Result
    {
        return $this->sendPublicRequest([
                'command'      => 'returnTradeHistory',
                'currencyPair' => $currencyPair,
                'start'        => $start,
                'end'          => $end,
            ]
        );
    }

    /**
     * @param string $currencyPair
     * @param int    $start
     * @param int    $end
     * @param int    $period
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function getChartData(string $currencyPair = 'ALL', int $start, int $end, int $period): Result
    {
        if (!in_array($period, self::CANDLE_STICK_PERIODS)) {
            throw new \LogicException(sprintf('Candle stick period "%s" is invalid. Valid are "%s', $period, implode(', ', self::CANDLE_STICK_PERIODS)));
        }

        return $this->sendPublicRequest([
                'command'      => 'returnChartData',
                'currencyPair' => $currencyPair,
                'start'        => $start,
                'end'          => $end,
                'period'       => $period,
            ]
        );
    }

    /**
     * @return \AndreasGlaser\PPC\Result
     */
    public function getCurrencies(): Result
    {
        return $this->sendPublicRequest([
                'command' => 'returnCurrencies',
            ]
        );
    }

    /**
     * @param string   $currency
     * @param int|null $limit
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function getLoanOrders(string $currency = 'BTC', int $limit = null): Result
    {
        return $this->sendPublicRequest([
                'command'  => 'returnLoanOrders',
                'currency' => $currency,
                'limit'    => $limit,
            ]
        );
    }

    /**
     * @return \AndreasGlaser\PPC\Result
     */
    public function getBalances(): Result
    {
        return $this->sendTradingRequest([
            'command' => 'returnBalances',
        ]);
    }

    /**
     * @param string $account
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function getCompleteBalances($account = 'exchange'): Result
    {
        return $this->sendTradingRequest([
            'command' => 'returnCompleteBalances',
            'account' => $account,
        ]);
    }

    /**
     * @return \AndreasGlaser\PPC\Result
     */
    public function getDepositAddresses(): Result
    {
        return $this->sendTradingRequest([
            'command' => 'returnDepositAddresses',
        ]);
    }

    /**
     * @param string $currency
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function generateNewAddress(string $currency): Result
    {
        return $this->sendTradingRequest([
            'command'  => 'generateNewAddress',
            'currency' => $currency,
        ]);
    }

    /**
     * @param int $start
     * @param int $end
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function getDepositsWithdrawals(int $start, int $end): Result
    {
        return $this->sendTradingRequest([
            'command' => 'returnDepositsWithdrawals',
            'start'   => $start,
            'end'     => $end,
        ]);
    }

    /**
     * @param string $currencyPair
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function getOpenOrders(string $currencyPair = 'ALL'): Result
    {
        return $this->sendTradingRequest([
            'command'      => 'returnOpenOrders',
            'currencyPair' => $currencyPair,
        ]);
    }

    /**
     * @param string   $currencyPair
     * @param int|null $start
     * @param int|null $end
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function getTradeHistory(string $currencyPair = 'ALL', int $start = null, int $end = null): Result
    {
        return $this->sendTradingRequest([
            'command'      => 'returnTradeHistory',
            'currencyPair' => $currencyPair,
            'start'        => $start,
            'end'          => $end,
        ]);
    }

    /**
     * @param string $orderNumber
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function getOrderTrades(string $orderNumber = 'ALL'): Result
    {
        return $this->sendTradingRequest([
            'command'     => 'returnOrderTrades',
            'orderNumber' => $orderNumber,
        ]);
    }

    /**
     * @param string    $currencyPair
     * @param int|float $rate
     * @param int|float $amount
     * @param int       $fillOrKill
     * @param int       $immediateOrCancel
     * @param int       $postOnly
     *
     * @return \AndreasGlaser\PPC\Result
     *
     * You may optionally set "fillOrKill", "immediateOrCancel", "postOnly" to 1.
     * A fill-or-kill order will either fill in its entirety or be completely aborted.
     * An immediate-or-cancel order can be partially or completely filled, but any portion of the order that cannot be filled immediately
     * will be canceled rather than left on the order book.
     * A post-only order will only be placed if no portion of it fills immediately; this guarantees you will never pay the taker fee
     * on any part of the order that fills.
     * @see    https://poloniex.com/support/api/
     */
    public function buy(string $currencyPair, $rate, $amount, int $fillOrKill = 1, int $immediateOrCancel = 0, int $postOnly = 0): Result
    {
        return $this->sendTradingRequest([
            'command'           => 'buy',
            'currencyPair'      => $currencyPair,
            'rate'              => $rate,
            'amount'            => $amount,
            'fillOrKill'        => $fillOrKill,
            'immediateOrCancel' => $immediateOrCancel,
            'postOnly'          => $postOnly,
        ]);
    }

    /**
     * @param string    $currencyPair
     * @param int|float $rate
     * @param int|float $amount
     * @param int       $fillOrKill
     * @param int       $immediateOrCancel
     * @param int       $postOnly
     *
     * @return \AndreasGlaser\PPC\Result
     *
     * You may optionally set "fillOrKill", "immediateOrCancel", "postOnly" to 1.
     * A fill-or-kill order will either fill in its entirety or be completely aborted.
     * An immediate-or-cancel order can be partially or completely filled, but any portion of the order that cannot be filled immediately
     * will be canceled rather than left on the order book.
     * A post-only order will only be placed if no portion of it fills immediately; this guarantees you will never pay the taker fee
     * on any part of the order that fills.
     * @see    https://poloniex.com/support/api/
     */
    public function sell(string $currencyPair, $rate, $amount, int $fillOrKill = 1, int $immediateOrCancel = 0, int $postOnly = 0): Result
    {
        return $this->sendTradingRequest([
            'command'           => 'sell',
            'currencyPair'      => $currencyPair,
            'rate'              => $rate,
            'amount'            => $amount,
            'fillOrKill'        => $fillOrKill,
            'immediateOrCancel' => $immediateOrCancel,
            'postOnly'          => $postOnly,
        ]);
    }

    /**
     * @param string $orderNumber
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function cancelOrder(string $orderNumber): Result
    {
        return $this->sendTradingRequest([
            'command'     => 'cancelOrder',
            'orderNumber' => $orderNumber,
        ]);
    }

    /**
     * @param string   $orderNumber
     * @param          $rate
     * @param null     $amount
     * @param int|null $postOnly
     * @param int|null $immediateOrCancel
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function moveOrder(string $orderNumber, $rate, $amount = null, int $postOnly = null, int $immediateOrCancel = null): Result
    {
        return $this->sendTradingRequest([
            'command'           => 'moveOrder',
            'orderNumber'       => $orderNumber,
            'rate'              => $rate,
            'amount'            => $amount,
            'immediateOrCancel' => $immediateOrCancel,
            'postOnly'          => $postOnly,
        ]);
    }

    /**
     * @param string      $currency
     * @param             $amount
     * @param string      $address
     * @param string|null $paymentId
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function withdraw(string $currency, $amount, string $address, string $paymentId = null): Result
    {
        return $this->sendTradingRequest([
            'command'   => 'withdraw',
            'currency'  => $currency,
            'amount'    => $amount,
            'address'   => $address,
            'paymentId' => $paymentId,
        ]);
    }

    /**
     * @return \AndreasGlaser\PPC\Result
     */
    public function getFeeInfo(): Result
    {
        return $this->sendTradingRequest([
            'command' => 'returnFeeInfo',
        ]);
    }

    /**
     * @param string $account
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function getAvailableAccountBalances(string $account = 'exchange'): Result
    {
        return $this->sendTradingRequest([
            'command' => 'returnAvailableAccountBalances',
            'account' => $account,
        ]);
    }

    /**
     * @return \AndreasGlaser\PPC\Result
     */
    public function getTradableBalances(): Result
    {
        return $this->sendTradingRequest([
            'command' => 'returnTradableBalances',
        ]);
    }

    /**
     * @param string $currency
     * @param        $amount
     * @param string $fromAccount
     * @param string $toAccount
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function transferBalance(string $currency, $amount, string $fromAccount, string $toAccount): Result
    {
        return $this->sendTradingRequest([
            'command'     => 'transferBalance',
            'currency'    => $currency,
            'amount'      => $amount,
            'fromAccount' => $fromAccount,
            'toAccount'   => $toAccount,
        ]);
    }

    /**
     * @return \AndreasGlaser\PPC\Result
     */
    public function getMarginAccountSummary(): Result
    {
        return $this->sendTradingRequest([
            'command' => 'returnMarginAccountSummary',
        ]);
    }

    /**
     * @param string $currencyPair
     * @param        $rate
     * @param        $amount
     * @param null   $lendingRate
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function marginBuy(string $currencyPair, $rate, $amount, $lendingRate = null): Result
    {
        return $this->sendTradingRequest([
            'command'      => 'marginBuy',
            'currencyPair' => $currencyPair,
            'rate'         => $rate,
            'amount'       => $amount,
            'lendingRate'  => $lendingRate,
        ]);
    }

    /**
     * @param string $currencyPair
     * @param        $rate
     * @param        $amount
     * @param null   $lendingRate
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function marginSell(string $currencyPair, $rate, $amount, $lendingRate = null): Result
    {
        return $this->sendTradingRequest([
            'command'      => 'marginSell',
            'currencyPair' => $currencyPair,
            'rate'         => $rate,
            'amount'       => $amount,
            'lendingRate'  => $lendingRate,
        ]);
    }

    /**
     * @param string $currencyPair
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function getMarginPosition(string $currencyPair = 'ALL'): Result
    {
        return $this->sendTradingRequest([
            'command'      => 'getMarginPosition',
            'currencyPair' => $currencyPair,
        ]);
    }

    /**
     * @param string $currencyPair
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function closeMarginPosition(string $currencyPair): Result
    {
        return $this->sendTradingRequest([
            'command'      => 'closeMarginPosition',
            'currencyPair' => $currencyPair,
        ]);
    }

    /**
     * @param string $currency
     * @param        $amount
     * @param        $lendingRate
     * @param        $duration
     * @param int    $autoRenew
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function createLoanOffer(string $currency, $amount, $lendingRate, $duration, $autoRenew = 0): Result
    {
        return $this->sendTradingRequest([
            'command'     => 'createLoanOffer',
            'currency'    => $currency,
            'amount'      => $amount,
            'lendingRate' => $lendingRate,
            'duration'    => $duration,
            'autoRenew'   => $autoRenew,
        ]);
    }

    /**
     * @param int $orderNumber
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function cancelLoanOffer(int $orderNumber): Result
    {
        return $this->sendTradingRequest([
            'command'     => 'cancelLoanOffer',
            'orderNumber' => $orderNumber,
        ]);
    }

    /**
     * @return \AndreasGlaser\PPC\Result
     */
    public function getOpenLoanOffers(): Result
    {
        return $this->sendTradingRequest([
            'command' => 'returnOpenLoanOffers',
        ]);
    }

    /**
     * @return \AndreasGlaser\PPC\Result
     */
    public function getActiveLoans(): Result
    {
        return $this->sendTradingRequest([
            'command' => 'returnActiveLoans',
        ]);
    }

    /**
     * @param int      $start
     * @param int      $end
     * @param int|null $limit
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function getLendingHistory(int $start, int $end, int $limit = null): Result
    {
        return $this->sendTradingRequest([
            'command' => 'returnLendingHistory',
            'start'   => $start,
            'end'     => $end,
            'limit'   => $limit,
        ]);
    }

    /**
     * @param int $orderNumber
     *
     * @return \AndreasGlaser\PPC\Result
     */
    public function toggleAutoRenew(int $orderNumber): Result
    {
        return $this->sendTradingRequest([
            'command'     => 'toggleAutoRenew',
            'orderNumber' => $orderNumber,
        ]);
    }
}