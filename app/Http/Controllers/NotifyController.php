<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Payment\Gateway;
use App\Payment\TradeNo;
use function GuzzleHttp\Psr7\build_query;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class NotifyController extends Controller
{
    public function alipay(Request $request): Response
    {
        \Log::channel('order')->info('订单异步通知', [
            'params' => $request->all()
        ]);

        $chargeNo = $request->get('out_trade_no');

        if ($chargeNo) {
            try {
                $charge = Charge::where(Charge::CHARGE_NO, $chargeNo)
                    ->firstOrFail();

                Log::channel('order')->info('charge', [
                    'charge' => $charge
                ]);

                return (new Gateway())->setCharge($charge)->notify($request->all());
            } catch (\Exception $e) {
                Log::error($e->getTraceAsString());
                response('fail', 200)
                    ->header('Content-Type', 'text/plain');
            }
        }
    }

    /**
     * qq钱包
     */
    public function qqWallet()
    {
        return $this->tenPay();
    }

    /**
     * 微信支付
     */
    public function wechatPay()
    {
        return $this->tenPay();
    }

    /**
     * 财付通支付(适用于QQ钱包和微信支付)
     */
    private function tenPay()
    {
        $params = file_get_contents('php://input');
        \Log::channel('order')->info('财付通异步通知原始请求参数', [
            'params' => $params
        ]);

        $chargeNo = data_get($params, 'out_trade_no');
        Log::info($chargeNo);
    }
}
