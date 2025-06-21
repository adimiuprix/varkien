<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ccxt\Exchange;

class ListCcxtExchanges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ccxt:list-exchanges';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all supported CCXT exchanges';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Daftar Exchange yang didukung oleh CCXT:\n");

        $exchanges = Exchange::$exchanges;

        foreach ($exchanges as $exchange) {
            $this->line('- ' . $exchange);
        }
    }
}
