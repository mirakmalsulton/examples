<?php

namespace App\Console\Commands;

use App\Src\Entities\DropBox;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Dropbox\Client;
use Spatie\Dropbox\Exceptions\BadRequest;

class DbDump extends Command
{
    const NAME = 'Db:dump';

    protected $signature = 'Db:dump';

    protected $description = 'Db dumper';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        /** @var DropBox $dropbox */
        if(!$dropbox = DropBox::first()) {
            Log::channel('backup')->error('backup fail : dropbox not configured');
            $this->error('backup fail : dropbox not configured');
            return 1;
        }

        $client = new Client($dropbox->token);

        $fileName = Carbon::now()->format('Y-m-d_H:i') . '.zip';
        $path = Storage::disk()->path('backups/' . $fileName);

        $db_root_user = 'root';
        $db_root_password = config('custom.DB_ROOT_PASSWORD');
        $db_name = config('custom.DB_DATABASE');
        exec("mysqldump --host=mysql -u$db_root_user -p$db_root_password $db_name | gzip > $path");

        try{
            $client->upload($fileName, fopen($path, 'r'), $mode = 'add');
            Log::channel('backup')->info('backup success ' . Carbon::now());
            $this->info('backup success');
        } catch (BadRequest $e) {
            Log::channel('backup')->error('backup fail : ' . $e->getMessage() . ' | ' . Carbon::now());
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}
