<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('businesses', function (Blueprint $table) {
    if (! Schema::hasColumn('businesses', 'cid')) {
        $table->string('cid')->nullable()->after('scraping_job_id')->index();
        echo "Column 'cid' created successfully.\n";
    } else {
        echo "Column 'cid' already exists.\n";
    }
});
