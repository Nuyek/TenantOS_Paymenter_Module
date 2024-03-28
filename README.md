# TenantOS_Paymenter_Module

### Installation Steps
1. **Step One**
   - Goto /App/Console/Kernel.php

   - Add ```$schedule->command('app:tenant-o-s-cron')->everyFifteenSeconds();``` under ```$schedule->command('p:stats')->dailyAt($this->registerStatsCommand());```

    - Add ```$this->load(__DIR__ . '/../Extensions/Servers/TenantOS');``` under ```$this->load(__DIR__ . '/Commands');```

    - You can change ->everyFifteenSeconds(); to whatever time you want. I have it set at fiveteen seconds for now since it was just easier for me to test the CRON.


2. **Step Two**
   - Upload the folder TenantOS to folder /App/Extensions/Servers/
   - GOTO {YOUR_DOMAIN}/admin/extensions and enable the Extension under the Server category.

3. **Step Three(Optional but HIGHLY recommended)**
   - Upload the folder TenantOSUserSync to folder /App/Extensions/Events/
   - This just keeps the email in sync with tenantOS since that's what I use to find users by. If they update their  Paymenter email this will update it in TenantOS.
   - GOTO {YOUR_DOMAIN}/admin/extensions and enable the Extension under the Events category.