@if ($details && isset($details->scalar))
@php
$data = json_decode($details->scalar);
@endphp
@endif

<div class="grid grid-cols-1 md:grid-cols-2">
    <div class="flex">
        <div class="flex-col">
            <div class="font-bold">Name:</div>
            <div class="font-bold">Hostname:</div>
            <div class="font-bold">Traffic:</div>
        </div>
        <div class="flex-col ml-4">
            <div>{{ $data->servername }}</div>
            <div>{{ $data->hostname }}</div>
            <div>N/A</div>
        </div>
    </div>
</div>
<div style="padding-top:20px;" class="grid grid-cols-1 md:grid-cols-2">
    <div class="flex">
        <div class="flex-col">
            <div class="font-bold">IPv4:</div>

        </div>
        <div class="flex-col ml-4">
            <ul>
                @foreach ($data->ipAssignments as $assignment)
                @if ($assignment->ipAttributes->isIpv4)
                {{ $assignment->ip }} @if($assignment->primary_ip) - Primary @endif
                <br>
                @endif
                @if ($assignment->ipAttributes->isSubnet && $assignment->ipAttributes->isIpv4)
                <br>
                Subnet: {{ $assignment->subnetinformation->subnet }}
                <br>
                @if (!empty($assignment->subnetinformation->netmask))
                Netmask:{{ $assignment->subnetinformation->netmask }}
                <br>
                @endif
                Gateway:{{ $assignment->subnetinformation->gw }}
                <br>
                <br>
                @endif
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="flex">
        <div class="flex-col">
            <div class="font-bold">IPv6:</div>
        </div>
        <div class="flex-col ml-4">
            <ul>
                @foreach ($data->ipAssignments as $assignment)
                @if ($assignment->ipAttributes->isIpv6)
                {{ $assignment->ip }} @if($assignment->primary_ip) - Primary @endif
                @endif
                @if ($assignment->ipAttributes->isSubnet && $assignment->ipAttributes->isIpv6)
                <br>
                Subnet: {{ $assignment->subnetinformation->subnet }}
                <br>
                @if (!empty($assignment->subnetinformation->netmask))
                Netmask:{{ $assignment->subnetinformation->netmask }}
                <br>
                @endif
                Gateway:{{ $assignment->subnetinformation->gw }}
                <br>
                <br>
                @endif
                </li>
                @endforeach
            </ul>
        </div>
    </div>


</div>

<p class="mt-8">Manage your server via our dedicated control panel. You will be automatically authenticated and the
    control panel will open in a new tab.</p>
<div id="warning" class="mt-2 text-red-500">This button will only work for the first @if(isset($data->validForSeconds) && !empty($data->validForSeconds)){{ $data->validForSeconds }}@endif seconds after this page is
    loaded. Afterwards it will not allow you to login. Please refresh the page if it's disabled.
</div>

<a id="loginButton" class="button button-primary mt-2" href="@if(isset($data->sso) && !empty($data->sso)){{$data->sso}}@endif" target="_blank">
    Login to control panel
</a>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        var loginButton = document.getElementById('loginButton');
        // Show warning message for the first X seconds
        setTimeout(function() {
            loginButton.classList.add('disabled');
            loginButton.removeAttribute('href');
            loginButton.addEventListener('click', function(event) {
                event.preventDefault();
            });
        }, @if(isset($data->validForSeconds) && !empty($data->validForSeconds)) {{ $data->validForSeconds * 1000}} @endif); // 60000 milliseconds = 60 seconds
    });
</script>
<style>
    .disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>