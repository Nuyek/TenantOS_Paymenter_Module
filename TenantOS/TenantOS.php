<?php

namespace App\Extensions\Servers\TenantOS;

use Illuminate\Support\Facades\Http;

use App\Classes\Extensions\Server;
use App\Helpers\ExtensionHelper;

use App\Models\Product;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\EmailLog;
use App\Extensions\Servers\TenantOS\NewDedicatedServerSetup;
use Illuminate\Support\Facades\Mail;


class TenantOS extends Server
{
    /**
     * Get the extension metadata
     * 
     * @return array
     */
    public function getMetadata()
    {
        return [
            'display_name' => 'TenantOS',
            'version' => '1.0.0',
            'author' => 'Nuyek, LLC',
            'website' => 'https://nuyek.com',
        ];
    }

    public function config($key): ?string
    {
        $config = ExtensionHelper::getConfig('TenantOS', $key);
        if ($config) {
            if ($key == 'host') {
                return rtrim($config, '/');
            }
            return $config;
        }

        return null;
    }


    /**
     * Get all the configuration for the extension
     * 
     * @return array
     */
    public function getConfig()
    {
        return [
            [
                'name' => 'host',
                'friendlyName' => 'TenantOS URL ( Do not have a / on the end )',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'apiKey',
                'friendlyName' => 'API Key',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'accountOwnershipId',
                'friendlyName' => 'Account that should own the server after suspension ETC..',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'customerAssignedTag',
                'friendlyName' => 'Tag to set a server when a customer is assigned it ( THIS SHOULD NEVER BE CHANGED IN PRODUCTION !!!!! )',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'forRentTag',
                'friendlyName' => 'Tag to decide which servers can be rented/assigned automatically ( THIS SHOULD NEVER BE CHANGED IN PRODUCTION !!!!! )',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'postTerminationProvisionProfile',
                'friendlyName' => 'Provision Profile to run after Termination ( Usually Disk Wipe )',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'apiTokenUrl',
                'friendlyName' => 'URL for the API Token',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'apiToken',
                'friendlyName' => 'Billing/Support Area Ticket API Token ( Mainly used to create tickets )',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'deleteUserIfNoOwnedServersAfterTermination',
                'friendlyName' => 'Deletes the user in TenantOS if they own no servers after a termination.',
                'type' => 'boolean',
            ],
        ];
    }

    /**
     * Get product config
     * 
     * @param array $options
     * @return array
     */
    public function getProductConfig($options)
    {
        return
            [
                [
                    'name' => 'CPU',
                    'friendlyName' => 'CPU Name ( As exactly it shows in TenantOS',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'RAM',
                    'friendlyName' => 'RAM Amount in MB.. 1024 = 1GB',
                    'type' => 'text',
                ],
                [
                    'name' => 'StockCheckConfigOptions',
                    'friendlyName' => 'Should stock checker apply config settings like RAM/DISKS? If not it only checks if theres available servers with the CPU name',
                    'type' => 'boolean',
                ],
                [
                    'name' => 'disk_1',
                    'friendlyName' => 'Disk One ( Format is MB DiskType, 2TB HDD = 2000 1, 256GB SSD = 256 2, 256 GB NVMe = 256 3)',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_2',
                    'friendlyName' => 'Disk Two',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_3',
                    'friendlyName' => 'Disk Three',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_4',
                    'friendlyName' => 'Disk Four',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_5',
                    'friendlyName' => 'Disk Five',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_6',
                    'friendlyName' => 'Disk Six',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_7',
                    'friendlyName' => 'Disk Seven',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_8',
                    'friendlyName' => 'Disk Eight',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_9',
                    'friendlyName' => 'Disk Nine',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_10',
                    'friendlyName' => 'Disk Ten',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_11',
                    'friendlyName' => 'Disk Eleven',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_12',
                    'friendlyName' => 'Disk Twelve',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_13',
                    'friendlyName' => 'Disk Thirteen',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_14',
                    'friendlyName' => 'Disk Fourteen',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_15',
                    'friendlyName' => 'Disk Fiveteen',
                    'type' => 'text',
                ],
                [
                    'name' => 'disk_16',
                    'friendlyName' => 'Disk Sixteen',
                    'type' => 'text',
                ],
            ];
    }

    /**
     * Create a server
     * 
     * @param User $user
     * @param array $params
     * @param Order $order
     * @param OrderProduct $orderProduct
     * @param array $configurableOptions
     * @return bool
     */
    public function createServer($user, $params, $order, $orderProduct, $configurableOptions)
    {
        if (isset($params['config']['server_id'])) {
            if (!empty($params['config']['server_id'])) {
                ExtensionHelper::debug('TenantOS', 'Trying to assign/create server for ' . $user->email . ' but already has an assigned server_id of ' . $params['config']['server_id']);
                return false;
            }
        }
        $doesUserExist = $this->DoesUserExist($user);
        $foundUser = null;
        if ($doesUserExist) {
            $foundUser = $this->FindUser($user);
        } else {

            $sanitized = preg_replace('/[^a-zA-Z0-9.]/', '', strtolower($user->first_name . '.' . $user->last_name));
            $json = [
                'name' => $user->first_name . " " . $user->last_name,
                'username' => $sanitized . $user->id . $this->generateRandomString(),
                'password' => $this->generateRandomString(12),
                'email' => $user->email,
                'role_id' => 3,
            ];

            $createUserResponse = $this->postRequest($this->config('host') . '/api/users', $json);
            if (!$createUserResponse->successful()) {
                ExtensionHelper::error('TenantOS', 'Failure to create user');
                return false;
            }

            $foundUser = json_decode($createUserResponse->getBody()->getContents())->result;
        }

        if ($doesUserExist == false) {
            ExtensionHelper::error('TenantOS', 'Should never reach this point without a user.');
            return false;
        }

        $foundServer = $this->FindServerWithSpecs($params, $configurableOptions, $orderProduct);

        if ($foundServer == null) {

            /*
            $mail = new NewDedicatedServerSetup($user, $order, $orderProduct);
            $emailLog = EmailLog::create([
                'user_id' => $user->id,
                'body' => $mail->render(),
                'subject' => $mail->subject,
                'body_text' => $mail->textView,
            ]);
            try {
                Mail::to($user->email)->queue($mail);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                $emailLog->update([
                    'errors' => (string) $e,
                    'success' => false,
                ]);
            }*/


            ExtensionHelper::error('TenantOS', 'Failure to find server for ' . $user->email);

            $message = "Hello, our system has failed to automatically provision your server from order " . $order->id . ". Most likely it wasn't able to find an available server with your selected configuration. We should have it up within a few hours to replace the parts needed. In rare occasions we will need to order additional parts.";
            //$this->CreateTicket($user, $order, "Automatic Provision failed for Server", $message, "high");
            return false;
        }

        $json = [
            'user_id' => $foundUser->id,
            'hostname' => $foundServer->servername,
            'tags' => [$this->config('customerAssignedTag')]
        ];
        $updatedServerResponse = $this->putRequest($this->config('host') . '/api/servers/' . $foundServer->id, $json);

        if (!$updatedServerResponse->successful()) {
            ExtensionHelper::debug('TenantOS', 'Failure to update found server ' . $foundServer->id . ' for new client ' . $user->email);
            return false;
        }
        ExtensionHelper::setOrderProductConfig('server_id', $foundServer->id, $orderProduct->id);
        ExtensionHelper::error('TenantOS', 'Found Server');
        $this->UpdateStock($orderProduct->product_id, $params, $configurableOptions);
        return true;
    }

    private function UpdateStock($productId, $params, $configurableOptions)
    {
        $json = [
            'tags' => [$this->config('forRentTag')],
        ];

        $servers = $this->postRequest($this->config('host') . '/api/servers/getByTags', $json);

        if (!$servers->successful()) {
            ExtensionHelper::error('TenantOS', 'Unable to find servers to update stock');
            return null;
        }

        $servers = json_decode($servers->getBody()->getContents())->result;

        $serverCounter = 0;
        $cpuModel = $configurableOptions['CPU'] ?? $params['CPU'];
        if (isset($servers) && is_array($servers)) {
            foreach ($servers as $server) {
                if (!isset($server->detailedHardwareInformation) || $server->detailedHardwareInformation == null)
                    continue;
                if ($server->detailedHardwareInformation->cpu->model === $cpuModel) {
                    if ($server->reinstallationRunning || $server->diskwipeRunning)
                        continue;

                    if ($this->config('StockCheckConfigOptions')) {
                        $ram = $configurableOptions['RAM'] ?? $params['RAM'];

                        if ($server->detailedHardwareInformation->memory->value != $ram) {
                            continue;
                        }

                        $completeInventory = $this->GetRequest($this->config('host') . '/api/servers/' . $server->id . '/inventory');
                        if (!$completeInventory->successful()) {
                            ExtensionHelper::error('TenantOS', 'Unable to fetch complete inventory for server ' . $server->id);
                        }

                        $completeInventory = json_decode($completeInventory->getBody()->getContents())->result;

                        $requestedDisks = new \stdClass();
                        $requestedDisks->disk_1 = $configurableOptions['disk_1'] ?? $params['disk_1'] ?? null;
                        $requestedDisks->disk_2 = $configurableOptions['disk_2'] ?? $params['disk_2'] ?? null;
                        $requestedDisks->disk_3 = $configurableOptions['disk_3'] ?? $params['disk_3'] ?? null;
                        $requestedDisks->disk_4 = $configurableOptions['disk_4'] ?? $params['disk_4'] ?? null;
                        $requestedDisks->disk_5 = $configurableOptions['disk_5'] ?? $params['disk_5'] ?? null;
                        $requestedDisks->disk_6 = $configurableOptions['disk_6'] ?? $params['disk_6'] ?? null;
                        $requestedDisks->disk_7 = $configurableOptions['disk_7'] ?? $params['disk_7'] ?? null;
                        $requestedDisks->disk_8 = $configurableOptions['disk_8'] ?? $params['disk_8'] ?? null;
                        $requestedDisks->disk_9 = $configurableOptions['disk_9'] ?? $params['disk_9'] ?? null;
                        $requestedDisks->disk_10 = $configurableOptions['disk_10'] ?? $params['disk_10'] ?? null;
                        $requestedDisks->disk_11 = $configurableOptions['disk_11'] ?? $params['disk_11'] ?? null;
                        $requestedDisks->disk_12 = $configurableOptions['disk_12'] ?? $params['disk_12'] ?? null;
                        $requestedDisks->disk_13 = $configurableOptions['disk_13'] ?? $params['disk_13'] ?? null;
                        $requestedDisks->disk_14 = $configurableOptions['disk_14'] ?? $params['disk_14'] ?? null;
                        $requestedDisks->disk_15 = $configurableOptions['disk_15'] ?? $params['disk_15'] ?? null;
                        $requestedDisks->disk_16 = $configurableOptions['disk_16'] ?? $params['disk_16'] ?? null;


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

                        $doesServerHaveCorrectDisks = $this->arraysMatch($requestedDiskArray, $diskArray);

                        if ($doesServerHaveCorrectDisks === 0) {
                            continue;
                        }

                    }

                    $serverCounter = $serverCounter + 1;
                }
            }
        }
        $productForStock = Product::find($productId);
        if ($productForStock->stock_enabled == 0) {
            $productForStock->stock_enabled = 1;
            $productForStock->save();
        }
        $productForStock->stock = $serverCounter;
        $productForStock->save();
    }
    private function CreateTicket($user, $order, $title, $message, $priority)
    {
        $ticket = Ticket::create([
            'title' => $title,
            'priority' => $priority,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'status' => 'open',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'message' => $message,
            'user_id' => 1,
        ]);

    }
    private function FindServerWithSpecs($params, $configurableOptions, $orderProduct)
    {

        $json = [
            'tags' => [$this->config('forRentTag')],
        ];


        $servers = $this->postRequest($this->config('host') . '/api/servers/getByTags', $json);

        if (!$servers->successful()) {
            ExtensionHelper::error('TenantOS', 'Unable to fetch servers for finding servers');
            return null;
        }
        $servers = json_decode($servers->getBody()->getContents())->result;
        if (isset($servers) && is_array($servers)) {
            foreach ($servers as $server) {
                if ($server->user_id != $this->config('accountOwnershipId'))
                    continue;

                $cpuModel = $configurableOptions['CPU'] ?? $params['CPU'];
                $ram = $configurableOptions['RAM'] ?? $params['RAM'];

                if (!isset($server->detailedHardwareInformation) || $server->detailedHardwareInformation == null)
                    continue;

                if ($server->detailedHardwareInformation->cpu->model == $cpuModel && $server->detailedHardwareInformation->memory->value == $ram) {
                    if ($server->reinstallationRunning || $server->diskwipeRunning)
                        continue;

                    $completeInventory = $this->GetRequest($this->config('host') . '/api/servers/' . $server->id . '/inventory');
                    if (!$completeInventory->successful()) {
                        ExtensionHelper::error('TenantOS', 'Unable to fetch complete inventory for server ' . $server->id);
                    }

                    $completeInventory = json_decode($completeInventory->getBody()->getContents())->result;

                    $requestedDisks = new \stdClass();
                    $requestedDisks->disk_1 = $configurableOptions['disk_1'] ?? $params['disk_1'] ?? null;
                    $requestedDisks->disk_2 = $configurableOptions['disk_2'] ?? $params['disk_2'] ?? null;
                    $requestedDisks->disk_3 = $configurableOptions['disk_3'] ?? $params['disk_3'] ?? null;
                    $requestedDisks->disk_4 = $configurableOptions['disk_4'] ?? $params['disk_4'] ?? null;
                    $requestedDisks->disk_5 = $configurableOptions['disk_5'] ?? $params['disk_5'] ?? null;
                    $requestedDisks->disk_6 = $configurableOptions['disk_6'] ?? $params['disk_6'] ?? null;
                    $requestedDisks->disk_7 = $configurableOptions['disk_7'] ?? $params['disk_7'] ?? null;
                    $requestedDisks->disk_8 = $configurableOptions['disk_8'] ?? $params['disk_8'] ?? null;
                    $requestedDisks->disk_9 = $configurableOptions['disk_9'] ?? $params['disk_9'] ?? null;
                    $requestedDisks->disk_10 = $configurableOptions['disk_10'] ?? $params['disk_10'] ?? null;
                    $requestedDisks->disk_11 = $configurableOptions['disk_11'] ?? $params['disk_11'] ?? null;
                    $requestedDisks->disk_12 = $configurableOptions['disk_12'] ?? $params['disk_12'] ?? null;
                    $requestedDisks->disk_13 = $configurableOptions['disk_13'] ?? $params['disk_13'] ?? null;
                    $requestedDisks->disk_14 = $configurableOptions['disk_14'] ?? $params['disk_14'] ?? null;
                    $requestedDisks->disk_15 = $configurableOptions['disk_15'] ?? $params['disk_15'] ?? null;
                    $requestedDisks->disk_16 = $configurableOptions['disk_16'] ?? $params['disk_16'] ?? null;


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

                    $doesServerHaveCorrectDisks = $this->arraysMatch($requestedDiskArray, $diskArray);

                    if ($doesServerHaveCorrectDisks === 0) {
                        continue;
                    }

                    return $server;
                }
            }
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

    private function FindUser($userData)
    {
        $users = $this->getRequest($this->config('host') . "/api/users");

        if (!$users->successful()) {
            return null;
        }

        $users = json_decode($users->getBody()->getContents())->result;
        foreach ($users as $user) {
            if ($user->email === $userData->email) {
                return $user;
            }
        }
        return null;
    }
    private function DoesUserExist($userData): bool
    {
        $users = $this->getRequest($this->config('host') . '/api/users');
        if (!$users->successful()) {
            return false;
        }
        $users = json_decode($users->getBody()->getContents())->result;
        foreach ($users as $user) {
            if ($user->email === $userData->email) {
                return true;
            }
        }
        return false;
    }
    /**
     * Suspend a server
     * 
     * @param User $user
     * @param array $params
     * @param Order $order
     * @param OrderProduct $orderProduct
     * @param array $configurableOptions
     * @return bool
     */
    public function suspendServer($user, $params, $order, $orderProduct, $configurableOptions)
    {
        if (!isset($params['config']['server_id'])) {
            ExtensionHelper::error('TenantOS', 'Trying to upgrade server but server_id is not set');
            return false;
        }
        if (empty($params['config']['server_id'])) {
            ExtensionHelper::error('TenantOS', 'Trying to upgrade server but server_id is empty');
            return false;
        }
        $serverId = $params['config']['server_id'];
        $server = $this->getRequest($this->config('host') . '/api/servers/' . $serverId);

        if (!$server->successful()) {
            ExtensionHelper::error('TenantOS', 'Unsuccessfully retrieved servers during suspension to find id for server' . $serverId);
        }
        $json = [
            'suspendUserIds' => [json_decode($server->getBody()->getContents())->result->user_id],
        ];

        $updatedServerResponse = $this->putRequest($this->config('host') . '/api/servers/' . $serverId, $json);


        if (!$updatedServerResponse->successful()) {
            ExtensionHelper::error('TenantOS', 'Unsuccessful on suspending server ' . $serverId);
            return false;
        }
        $powerOnResponse = $this->getRequest($this->config('host') . '/api/servers/' . $serverId . '/power/setPowerOff');
        if (!$powerOnResponse->successful()) {
            ExtensionHelper::error('TenantOS', 'Unsuccessful on triggering power off after suspension for server ' . $serverId);
        }
        ExtensionHelper::debug('TenantOS', 'Suspending server ' . $serverId . ' for ' . $user->email);
        return true;
    }

    /**
     * Unsuspend a server
     * 
     * @param User $user
     * @param array $params
     * @param Order $order
     * @param OrderProduct $orderProduct
     * @param array $configurableOptions
     * @return bool
     */
    public function unsuspendServer($user, $params, $order, $orderProduct, $configurableOptions)
    {
        if (!isset($params['config']['server_id'])) {
            ExtensionHelper::error('TenantOS', 'Trying to  upgrade server but server_id is not set');
            return false;
        }
        if (empty($params['config']['server_id'])) {
            ExtensionHelper::error('TenantOS', 'Trying to upgrade server but server_id is empty');
            return false;
        }
        $serverId = $params['config']['server_id'];

        $json = [
            'suspendUserIds' => [],
        ];

        $updatedServerResponse = $this->putRequest($this->config('host') . '/api/servers/' . $serverId, $json);


        if (!$updatedServerResponse->successful()) {
            ExtensionHelper::error('TenantOS', 'Unsuccessful on suspending server ' . $serverId);
            return false;
        }
        $powerOnResponse = $this->getRequest($this->config('host') . '/api/servers/' . $serverId . '/power/setPowerOn');
        if (!$powerOnResponse->successful()) {
            ExtensionHelper::error('TenantOS', 'Unsuccessful on triggering power on after unsuspension for server ' . $serverId);
        }
        ExtensionHelper::debug('TenantOS', 'Unsuspending server ' . $serverId . ' for ' . $user->email);
        return true;
    }
    public function upgradeServer($user, $params, $order, $orderProduct, $configurableOptions)
    {
        if (!isset($params['config']['server_id'])) {
            ExtensionHelper::error('TenantOS', 'Trying to  upgrade server but server_id is not set');
            return false;
        }
        if (empty($params['config']['server_id'])) {
            ExtensionHelper::error('TenantOS', 'Trying to upgrade server but server_id is empty');
            return false;
        }

        return true;
    }
    /**
     * Terminate a server
     * 
     * @param User $user
     * @param array $params
     * @param Order $order
     * @param OrderProduct $orderProduct
     * @param array $configurableOptions
     * @return bool
     */
    public function terminateServer($user, $params, $order, $orderProduct, $configurableOptions)
    {
        if (!isset($params['config']['server_id'])) {
            ExtensionHelper::error('TenantOS', 'Trying to terminate server but server_id is not set');
            return false;
        }
        if (empty($params['config']['server_id'])) {
            ExtensionHelper::error('TenantOS', 'Trying to terminate server but server_id is empty');
            return false;
        }

        $serverId = $params['config']['server_id'];
        $server = $this->getRequest($this->config('host') . '/api/servers/' . $serverId);
        $server = json_decode($server->getBody()->getContents())->result;
        $serverOldOwnerId = $server->user_id;

        $json = [
            'user_id' => intval($this->config('accountOwnershipId')),
            'hostname' => $server->servername,
            'tags' => [$this->config('forRentTag')]
        ];
        $updatedServerResponse = $this->putRequest($this->config('host') . '/api/servers/' . $serverId, $json);

        if (!$updatedServerResponse->successful()) {
            ExtensionHelper::error('TenantOS', 'Unsuccessful on removing user and adding tag to server during termination for server ' . $serverId);
            return false;
        }
        ExtensionHelper::setOrderProductConfig('server_id', '', $orderProduct->id);
        ExtensionHelper::debug('TenantOS', 'Terminating server ' . $serverId . ' for ' . $user->email);

        $json = [
            'profileId' => intval($this->config('postTerminationProvisionProfile'))
        ];
        $reinstallationResponse = $this->postRequest($this->config('host') . '/api/servers/' . $serverId . '/provisioning/startReinstallation', $json);

        if (!$reinstallationResponse->successful()) {
            ExtensionHelper::error('TenantOS', 'Unsuccessfull on triggering disk wipe during termination for server ' . $serverId);
        }

        $ipAssignments = $this->GetRequest($this->config('host') . '/api/servers/' . $serverId . '/ipassignments');

        if (!$ipAssignments->successful()) {
            ExtensionHelper::error('TenantOS', 'Unsuccessfully retrieved IP assignments during termination for server ' . $serverId);
        }

        $ipAssignments = json_decode($ipAssignments->getBody()->getContents())->result;

        foreach ($ipAssignments as $ipAssignment) {
            if ($ipAssignment->primary_ip != 1) {
                $json = [
                    'ip' => $ipAssignment->ip
                ];
                $removedIpResponse = $this->deleteRequest($this->config('host') . '/api/servers/' . $serverId . '/ipassignments/' . $ipAssignment->id, $json);

                if (!$removedIpResponse->successful()) {
                    ExtensionHelper::error('TenantOS', 'Unsuccessful on removing IP from server during termination for server ' . $serverId);
                }
            }
        }

        $this->UpdateStock($orderProduct->product_id, $params, $configurableOptions);
        return true;
    }


    private function generateRandomString($length = 6)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    public function getCustomPages($user, $params, $order, $product, $configurableOptions)
    {
        if (!isset($params['config']['server_id'])) {
            return;
        }

        if (empty($params['config']['server_id']) || $params['config']['server_id'] == '') {
            return;
        }

        $serverId = $params['config']['server_id'];
        $server = $this->getRequest($this->config('host') . '/api/servers/' . $serverId);
        $server = json_decode($server->getBody()->getContents())->result;

        $userData = $this->getRequest($this->config('host') . '/api/users/' . $server->user_id);
        $userData = json_decode($userData->getBody()->getContents())->result;

        if ($userData->email != $user->email || $userData->role_id == 1 || $userData->id == 1 || $userData->id == 2) {
            return;
        }


        $data = new \stdClass();
        $data->sso = '';
        $data->ipAssignments = $server->ipassignments;
        //Just in case something ever happens we can only login as a reseller/user role?
        if ($userData->role_id == 3 || $userData->role_id == 2 && $userData->id != 1 && $userData->id != 2) {


            $validForSeconds = 60; //Time SSO is valid for.

            $json = [
                'endpoint' => '/servers',
                'validForSeconds' => $validForSeconds
            ];

            $userSSOToken = $this->postRequest($this->config('host') . '/api/users/' . $server->user_id . '/generateSsoToken', $json);
            $userSSOToken = json_decode($userSSOToken->getBody()->getContents())->result;


            $data->sso = $this->config('host') . '/tokenLogin/' . $userSSOToken->authToken;
            $data->validForSeconds = $validForSeconds;
        }
        $data->servername = $server->servername;
        $data->hostname = $server->hostname;


        return [
            'name' => 'TenantOS',
            'template' => 'tenantos::control',
            'data' => [
                'details' => (object) json_encode($data),
            ],
        ];
    }
    private function postRequest($url, $data): \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config('apiKey'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($url, $data);
    }

    private function patchRequest($url, $data): \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config('apiKey'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->patch($url, $data);
    }

    private function getRequest($url): \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config('apiKey'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get($url);
    }

    public function deleteRequest($url, $data): \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config('apiKey'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->delete($url, $data);
    }
    public function putRequest($url, $data): \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config('apiKey'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->put($url, $data);
    }
    
}
