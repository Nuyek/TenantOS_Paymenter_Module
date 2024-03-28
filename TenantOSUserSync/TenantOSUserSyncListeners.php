<?php

namespace App\Extensions\Events\TenantOSUserSync;

use App\Events\Invoice\InvoiceCreated;
use App\Events\Invoice\InvoicePaid;
use App\Events\Ticket\TicketCreated;
use App\Events\Ticket\TicketMessageCreated;
use App\Events\User\UserUpdated;
use App\Helpers\ExtensionHelper;

class TenantOSUserSyncListeners
{


    private function GetRequest($apiEndpoint)
    {
        $url = ExtensionHelper::getConfig('TenantOSUserSync', 'host') . $apiEndpoint;
        $headers = [
            'Authorization: Bearer ' . ExtensionHelper::getConfig('TenantOSUserSync', 'apiKey'),
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if ($response === false) {
            echo 'Error: ' . curl_error($ch);
        } else {
            $body = json_decode($response);
            //print_r($body);
        }

        curl_close($ch);
        return $body;
    }

    private function PutRequest($apiEndpoint, $json)
    {
        $url = ExtensionHelper::getConfig('TenantOSUserSync', 'host') . $apiEndpoint;
        $headers = [
            'Authorization: Bearer ' . ExtensionHelper::getConfig('TenantOSUserSync', 'apiKey'),
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        $data = $json; // Define your data here

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); // Set as PUT request
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Set your POST data

        $response = curl_exec($ch);

        if ($response === false) {
            echo 'Error: ' . curl_error($ch);
        } else {
            $body = json_decode($response);
        }

        curl_close($ch);
        return $body;
    }
    private function PostRequest($apiEndpoint, $json)
    {
        $url = ExtensionHelper::getConfig('TenantOSUserSync', 'host') . $apiEndpoint;
        $headers = [
            'Authorization: Bearer ' . ExtensionHelper::getConfig('TenantOSUserSync', 'apiKey'),
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        $data = $json; // Define your data here

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true); // Set as POST request
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Set your POST data

        $response = curl_exec($ch);

        if ($response === false) {
            echo 'Error: ' . curl_error($ch);
        } else {
            $body = json_decode($response);
        }

        curl_close($ch);
        return $body;
    }

    private function FindUser($userData) 
    {
        $users = $this->GetRequest("/api/users");
        $users = $users->result;
        foreach ($users as $user) {
            if ($user->email === $userData->email) {
                return $user;
            }
        }
        return null;
    }
    
    private function FindUserById($userData) 
    {
        $user = $this->GetRequest("/api/users/" . $userData->id);
        return user;
    }
    private function DoesUserExist($userData):bool
    {
        $users = $this->GetRequest("/api/users");
        $users = $users->result;
        foreach ($users as $user) {
            if ($user->email === $userData->email) {
                return true;
            }
        }
        return false;
    }

    public function userUpdated($event)
    {
        $user = $event->user;
        $newEmail = $user->email;
        $oldEmail = $event->user->getOriginal('email');
        $user->email = $oldEmail;


        $findUser = $this->FindUser($user);

        if($findUser) 
        {
            $updatedDetails = new \stdClass();
            $updatedDetails->email = $newEmail;
            $sanitized = preg_replace('/[^a-zA-Z0-9.]/', '', strtolower($user->first_name . '.' . $user->last_name));
            $updatedDetails->name = $sanitized;
            $updatedUserResponse = $this->PutRequest("/api/users/" . $findUser->id, $updatedDetails);

            ExtensionHelper::error('TenantOSUserSync', 'Updating TenantOS for ' . $newEmail . ' from ' . $oldEmail);
        }


    }


    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            UserUpdated::class => 'userUpdated',

        ];
    }
}
