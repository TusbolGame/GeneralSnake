<?php

namespace App\Console;

use App\Http\Controllers\Api\DepositController;
use App\Http\Controllers\Api\EthereumController;
use App\Http\Controllers\Api\MarketController;
use App\Http\Controllers\Api\RippleController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\XinController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->call(function() {
            $walletController = new WalletController();
            $walletController->checkETHPending();
            $walletController->checkCLOPending();
        });

        $schedule->call(function() {
            $depositController = new DepositController();
            $depositController->getETHDeposit();
            $depositController->getXRPDeposit();
            $depositController->getTRXDeposit();
            //$depositController->getCoinDeposit();
            //$depositController->getXINDeposit();
            $depositController->getUSDTDeposit();
            $depositController->getAchainDeposit();
            $depositController->getBitsharesDeposit();
            // $depositController->getNTYDeposit();
            $depositController->getPIRLDeposit();
            $depositController->getCLODeposit();
            $depositController->getWavesDeposit();
            $depositController->getETNDeposit();
            $depositController->getScorumDeposit();
            $depositController->getBRAVODeposit();
            $depositController->getXRCDeposit();
            $depositController->getONTDeposit();
            $depositController->getMPGDeposit();
            $depositController->getTGNDeposit();
            $depositController->getBENDeposit();
            $depositController->getBTWDeposit();
            $depositController->getCCXDeposit();
        })->everyMinute();

//        $schedule->call(function() {
//            $marketController = new MarketController();
//            $marketController->updateCurrencyRate();
//        });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
