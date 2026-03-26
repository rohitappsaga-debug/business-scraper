<?php

namespace App\Scrapers\Pipelines;

use App\Services\BusinessService;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\Support\Configurable;

class SaveBusinessPipeline implements ItemProcessorInterface
{
    use Configurable;

    public function __construct(
        private readonly BusinessService $businessService
    ) {}

    public function processItem(ItemInterface $item): ItemInterface
    {
        $data = $item->all();
        $jobId = $data['scraping_job_id'] ?? null;
        $city = $data['city'] ?? '';

        if (! $jobId) {
            return $item;
        }

        $this->businessService->saveBusiness($data, $jobId, $city);

        return $item;
    }
}
