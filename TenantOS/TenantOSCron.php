<?php

namespace App\Extensions\Servers\TenantOS;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use App\Helpers\ExtensionHelper;

use App\Models\EmailTemplate;

class TenantOSCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:tenant-o-s-cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!EmailTemplate::where('mailable', \App\Extensions\Servers\TenantOS\NewDedicatedServerSetup::class)->exists()) {

            EmailTemplate::create([
                'mailable' => \App\Extensions\Servers\TenantOS\NewDedicatedServerSetup::class,
                'subject' => 'New Dedicated Server Details',
                'html_template' => '',
            ]);

        }

        $products = \App\Models\Product::all();

        foreach ($products as $product) {
            $productOldStock = $product->stock;
            $productExtensionId = $product->extension_id;


            $extension = \App\Models\Extension::find($productExtensionId);

            if ($extension == null || !isset($extension))
                continue;

            if ($extension->name != "TenantOS")
                continue;


            if ($product->stock_enabled == 0) {
                $product->stock_enabled = 1;
                $product->save();
            }
            $extensionHost = \App\Models\ExtensionSetting::find($productExtensionId);

            $productHost = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'host')->first();
            $productToken = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'apiKey')->first();
            $productTags = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'forRentTag')->first();

            $hostUrl = \Illuminate\Support\Facades\Crypt::decryptString($productHost->value);
            $apiToken = \Illuminate\Support\Facades\Crypt::decryptString($productToken->value);
            $tags = \Illuminate\Support\Facades\Crypt::decryptString($productTags->value);

            $json = [
                'tags' => [$tags],
            ];

            $servers = $this->postRequest($hostUrl . '/api/servers/getByTags', $apiToken, $json);

            if (!$servers->successful()) {
                ExtensionHelper::error('TenantOS Cron', 'Unable to find servers to update stock');
                continue;
            }

            $servers = json_decode($servers->getBody()->getContents())->result;

            $serverCounter = 0;
            $cpuModel = \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'CPU')->first()->value ?? null;

            if($cpuModel == null)
                continue;

            $shouldStockCheckConfigOptions = \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'StockCheckConfigOptions')->first()->value ?? null;

            if($shouldStockCheckConfigOptions == null)
                continue;
            
            if (isset($servers) && is_array($servers)) {
                foreach ($servers as $server) {
                    if(!isset($server->detailedHardwareInformation) || $server->detailedHardwareInformation == null)
                        continue;

                    if (strcmp($server->detailedHardwareInformation->cpu->model, $cpuModel) === 0) {
                        if ($server->reinstallationRunning || $server->diskwipeRunning)
                            continue;
                        if ($shouldStockCheckConfigOptions) {
                            $ram = \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'RAM')->first()->value;

                            // ExtensionHelper::error('TenantOSCron', $server->detailedHardwareInformation->memory->value);
                            //ExtensionHelper::error('TenantOSCron', $ram);
                            if ($server->detailedHardwareInformation->memory->value === $ram) {
                                continue;
                            }

                            $completeInventory = $this->getRequest($hostUrl . '/api/servers/' . $server->id . '/inventory', $apiToken);
                            $completeInventory = json_decode($completeInventory->getBody()->getContents())->result;

                            $requestedDisks = new \stdClass();
                            $requestedDisks->disk_1 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_1')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_1')->first()->value ?? null;
                            $requestedDisks->disk_2 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_2')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_2')->first()->value ?? null;
                            $requestedDisks->disk_3 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_3')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_3')->first()->value ?? null;
                            $requestedDisks->disk_4 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_4')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_4')->first()->value ?? null;
                            $requestedDisks->disk_5 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_5')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_5')->first()->value ?? null;
                            $requestedDisks->disk_6 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_6')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_6')->first()->value ?? null;
                            $requestedDisks->disk_7 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_7')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_7')->first()->value ?? null;
                            $requestedDisks->disk_8 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_8')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_8')->first()->value ?? null;
                            $requestedDisks->disk_9 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_9')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_9')->first()->value ?? null;
                            $requestedDisks->disk_10 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_10')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_10')->first()->value ?? null;
                            $requestedDisks->disk_11 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_11')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_11')->first()->value ?? null;
                            $requestedDisks->disk_12 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_12')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_12')->first()->value ?? null;
                            $requestedDisks->disk_13 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_13')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_13')->first()->value ?? null;
                            $requestedDisks->disk_14 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_14')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_14')->first()->value ?? null;
                            $requestedDisks->disk_15 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_15')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_15')->first()->value ?? null;
                            $requestedDisks->disk_16 = \App\Models\ExtensionSetting::where('extension_id', $productExtensionId)->where('key', 'disk_16')->first() ?? \App\Models\ProductSetting::where('product_id', $product->id)->where('name', 'disk_16')->first()->value ?? null;
                            

                            //HDD = 1, SSD = 2, NVMe = 3
                            $requestedDisksAmount = 0;
                            $disksOnServerAmount = 0;
                            foreach ($requestedDisks as $requestedDisk) {
                                if ($requestedDisk !== null) {
                                    if ($requestedDisk != 0)
                                        $requestedDisksAmount++;
                                }
                            }

                            foreach ($completeInventory as $item) {
                                if ($item->root_component->description === 'Disk') {
                                    $disksOnServerAmount++;
                                }
                            }

                            if ($requestedDisksAmount != $disksOnServerAmount) {
                                continue;
                            }
                            $requestedDiskArray = (array) $requestedDisks;
                            $requestedDiskArray = array_values(array_filter($requestedDiskArray, function ($value) {
                                return $value !== null && $value !== 0 && $value !== "0";
                            }));

                            $diskArray = [];
                            foreach ($completeInventory as $item) {
                                if ($item->root_component->description === 'Disk') {
                                    $diskType = $item->customfields[0]->value;
                                    $diskSizeInGB = round($item->value * 0.001048576);
                                    $diskInfo = $diskSizeInGB . ' ' . $diskType;
                                    $diskArray[] = $diskInfo;
                                }
                            }

                            //ExtensionHelper::error('TenantOS Cron', 'Arr1 ' . json_encode($requestedDiskArray));
                            //ExtensionHelper::error('TenantOS Cron', 'Arr2 ' . json_encode($diskArray));
                            $doesServerHaveCorrectDisks = $this->arraysMatch($requestedDiskArray, $diskArray);

                            if ($doesServerHaveCorrectDisks === 0) {
                                continue;
                            }

                        }
                        $serverCounter = $serverCounter + 1;
                    }
                }
            }

            if ($productOldStock != $serverCounter) {
                $product->stock = $serverCounter;
                $product->save();
            }
            ExtensionHelper::debug('TenantOS Cron', 'Cron ran to update stock for TenantOS products');
        }
    }

    private function arraysMatch($array1, $array2)
    {
        // Sort the arrays to ensure order doesn't affect the comparison
        sort($array1);
        sort($array2);

        // Compare arrays
        if (array_diff($array1, $array2) || array_diff($array2, $array1)) {
            return 0;
        } else {
            return 1;
        }
    }

    private function getRequest($url, $api): \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $api,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get($url);
    }

    private function postRequest($url, $api, $data): \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $api,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($url, $data);
    }

}
