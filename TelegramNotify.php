<?php

namespace App\Console\Commands;

use App\Src\Entities\Order;
use App\Src\Entities\TelegramUser;
use App\Utils\TelegramSender;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TelegramNotify extends Command
{
    const NAME = 'telegram:notify';

    protected $signature = 'telegram:notify';
    protected $description = 'telegram:notify';
    private TelegramSender $telegramSender;

    public function __construct(TelegramSender $telegramSender)
    {
        $this->telegramSender = $telegramSender;
        parent::__construct();
    }

    public function handle()
    {
        $orders = Order::select()->onlyOpened()->get();
        if ($orders->isEmpty()) {
            $this->warn('Orders not found');
            return 0;
        }

        foreach ($orders as $order) {
            if (!$telegramUser = TelegramUser::where('client_id', $order->client_id)->first()) continue;

            $ordered_at = Carbon::parse($order->ordered_at);
            $now = Carbon::now();
            $daysBeforePayment = config('custom.TELEGRAM_NOTIFY_BEFORE_DAYS');
            $diffMonth = $ordered_at->diffInMonths($now->addDays($daysBeforePayment));
            if ($diffMonth < 1) continue;

            $sendTime = Carbon::parse($order->ordered_at)->addMonthsNoOverflow($diffMonth)->subDays($daysBeforePayment);
            $expiredTime = Carbon::parse($order->ordered_at)->addMonthsNoOverflow($diffMonth);
            if (!$sendTime->isPast()) continue;

            $notify = DB::table('telegram_notify')
                ->where('order_id', $order->id)
                ->where('sent_at', '>', $sendTime)
                ->first();

            if (empty($notify)) {
                $this->notifier($telegramUser, $order, $expiredTime);

                DB::table('telegram_notify')->insert(['order_id' => $order->id, 'sent_at' => Carbon::now()]);
                $this->info('Notify sent');
                return 0;
            }
        }

        $this->warn('No notify sent');
        return 0;
    }

    private function notifier(TelegramUser $telegramUser, Order $order, Carbon $expiredTime)
    {
        $this->telegramSender->send([
            'chat_id' => $telegramUser->id,
            'text' => __('tg.Напоминаем, что Вы должны оплатить за кредит до :date', [
                'date' => $expiredTime->format('d.m.Y')
            ]),
            'parse_mode' => 'HTML',
        ]);
    }

}
