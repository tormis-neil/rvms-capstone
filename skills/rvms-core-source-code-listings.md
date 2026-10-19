# APPENDIX: CORE FEATURE SOURCE CODE LISTINGS
## Rescue Vehicle Management System (RVMS)

**Project:** Rescue Vehicle Management System (RVMS)  
**Institution:** Northwest Samar State University (NwSSU) — College of Computing and Information Sciences  
**Target Agencies:** CDRRMO, BFP, CHO, and PNP of Calbayog City  
**Purpose:** This document presents the primary source code listings implementing the core architectural, security, state synchronization, and operational workflows of the Rescue Vehicle Management System.

---

### Listing 1. Multi-Agency Data Isolation Global Scope
**File Path:** `backend/app/Models/Scopes/AgencyScope.php`  
**Language:** PHP (Laravel 11 Eloquent Global Scope)  
**Requirements Addressed:** FR-02 (User Roles and Agency Access), NFR-02 (Security & Agency Data Separation)  
**Description:** Automatically inspects the authenticated user's session and dynamically appends an `agency_id` filter to every Eloquent database query at runtime. This guarantees at the ORM layer that Agency Administrators from CDRRMO, BFP, CHO, and PNP can only view, update, and manage records belonging strictly to their respective agency.

```php
<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global scope enforcing agency-level data isolation (FR-02, NFR-02):
 * Every query on a scoped model is automatically restricted to the authenticated
 * user's own agency. Unauthenticated contexts (console commands, seeders,
 * and scheduled automated jobs) bypass filtering.
 */
class AgencyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user !== null) {
            $builder->where(
                $model->qualifyColumn('agency_id'),
                $user->agency_id,
            );
        }
    }
}
```

---

### Listing 2. Single-Gate Vehicle Operational Status State Machine
**File Path:** `backend/app/Services/VehicleStatusWriter.php`  
**Language:** PHP (Laravel Service Class)  
**Requirements Addressed:** FR-20 (Vehicle Availability Monitoring), FR-21 (Vehicle Status Management), NFR-04 (Reliability & Cross-Platform Synchronization)  
**Description:** Implements the central business rule and state machine governing vehicle availability. Enforces the invariant that any vehicle currently deployed on an active dispatch cannot have its status overridden by other modules (such as maintenance or inspection reviews) until the dispatch is officially closed. Stamps the originating module source for administrative auditability.

```php
<?php

namespace App\Services;

use App\Models\Dispatch;
use App\Models\Vehicle;
use Illuminate\Validation\ValidationException;

/**
 * The single gate for every vehicle status write (FR-20, FR-21).
 * Ensures centralized transition logic across the four standardized operational states:
 * Operational, Dispatched, Not Operational, and Under Preventive Maintenance.
 */
class VehicleStatusWriter
{
    public const SOURCE_VEHICLES = 'Vehicle Management';
    public const SOURCE_INSPECTION = 'Inspection Review';
    public const SOURCE_DAMAGE = 'Damage Review';
    public const SOURCE_REPAIR = 'Repair Logs';
    public const SOURCE_PM = 'PM Schedules';
    public const SOURCE_DISPATCH_OPEN = 'Dispatch Opened';
    public const SOURCE_DISPATCH_CLOSE = 'Dispatch Closed';

    /**
     * Writes vehicle operational status with dispatch-lock protection.
     *
     * @throws ValidationException when the vehicle is currently on an active emergency dispatch
     */
    public function write(Vehicle $vehicle, string $status, string $source, array $extra = []): void
    {
        $this->assertNotOnActiveDispatch($vehicle);

        $this->commit($vehicle, $status, $source, $extra);
    }

    /**
     * Prevents status changes if vehicle is deployed on an unclosed mission.
     */
    private function assertNotOnActiveDispatch(Vehicle $vehicle): void
    {
        $active = Dispatch::withoutGlobalScopes()
            ->where('vehicle_id', $vehicle->id)
            ->whereNull('time_in')
            ->first();

        if ($active !== null) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Cannot change status: %s is currently dispatched on an active mission (%s). '
                    .'Close the dispatch before updating vehicle status.',
                    $vehicle->plate_number,
                    $active->mission_type
                ),
            ]);
        }
    }

    private function commit(Vehicle $vehicle, string $status, string $source, array $extra): void
    {
        $vehicle->update(array_merge($extra, [
            'status' => $status,
            'status_source' => $source,
            'status_updated_at' => now(),
        ]));
    }
}
```

---

### Listing 3. Digital BLOWBAGETS Checklist Submission & Defect Flagging
**File Path:** `backend/app/Http/Controllers/Api/V1/InspectionController.php`  
**Language:** PHP (Laravel 11 REST API Controller)  
**Requirements Addressed:** FR-11 (Digital BLOWBAGETS Inspection), FR-12 (Inspection Monitoring), FR-25 (Administrator Notifications)  
**Description:** Processes daily pre-trip checklist submissions from the mobile app inside a database transaction. Evaluates each checklist item against the catalog, requires explanatory remarks for flagged defects (`Has Issue`), saves the inspection record, and triggers administrative notifications when defects are reported.

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInspectionRequest;
use App\Http\Resources\InspectionResource;
use App\Models\Inspection;
use App\Models\InspectionItem;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Support\Facades\DB;

class InspectionController extends Controller
{
    /**
     * POST /api/v1/inspections — Driver pre-trip checklist submission.
     */
    public function store(StoreInspectionRequest $request)
    {
        $inspection = DB::transaction(function () use ($request) {
            $inspection = Inspection::create([
                'vehicle_id' => $request->validated('vehicle_id'),
                'driver_id' => $request->user()->id,
                'inspection_date' => $request->validated('inspection_date') ?? now()->toDateString(),
                'review_status' => Inspection::STATUS_PENDING,
            ]);

            foreach ($request->validated('items') as $item) {
                $inspection->items()->create([
                    'checklist_item_id' => $item['checklist_item_id'],
                    'status' => $item['status'],
                    'remarks' => $item['status'] === InspectionItem::STATUS_HAS_ISSUE ? $item['remarks'] : null,
                ]);
            }

            return $inspection;
        });

        // Notify agency administrators if any checklist items were flagged with defects
        $this->notifyIfFlagged($inspection);

        return InspectionResource::make($inspection->load(['items.checklistItem', 'vehicle', 'driver']))
            ->response()
            ->setStatusCode(201);
    }

    private function notifyIfFlagged(Inspection $inspection): void
    {
        $flagged = $inspection->items()
            ->where('status', InspectionItem::STATUS_HAS_ISSUE)
            ->with('checklistItem')
            ->get();

        if ($flagged->isNotEmpty()) {
            app(NotificationDispatcher::class)->dispatchToAdmins(
                agencyId: $inspection->agency_id,
                type: Notification::TYPE_INSPECTION_FLAGGED,
                title: "Inspection Defect Reported: {$inspection->vehicle->plate_number}",
                message: "Driver {$inspection->driver->name} flagged {$flagged->count()} issue(s) during BLOWBAGETS inspection.",
                data: ['inspection_id' => $inspection->id, 'vehicle_id' => $inspection->vehicle_id]
            );
        }
    }
}
```

---

### Listing 4. Dispatch Conflict Prevention & Driver Availability Guard
**File Path:** `backend/app/Services/DispatchGuard.php`  
**Language:** PHP (Laravel Service Class)  
**Requirements Addressed:** FR-18 (Dispatch Logging), FR-19 (Dispatch Closing), NFR-04 (Operational Reliability)  
**Description:** Enforces the "one mission at a time" operational rule. Validates that neither the selected emergency vehicle nor the primary or secondary driver is currently engaged in an active, unclosed dispatch, preventing double-deployment errors during chaotic emergency dispatching.

```php
<?php

namespace App\Services;

use App\Models\Dispatch;
use Illuminate\Validation\ValidationException;

class DispatchGuard
{
    /**
     * Checks if a vehicle is currently deployed on an active dispatch.
     */
    public function openForVehicle(int $vehicleId, ?int $ignoreDispatchId = null): ?Dispatch
    {
        return Dispatch::whereNull('time_in')
            ->when($ignoreDispatchId, fn($q) => $q->where('id', '!=', $ignoreDispatchId))
            ->where('vehicle_id', $vehicleId)
            ->first();
    }

    /**
     * Checks if a driver (primary or secondary) is currently out on a mission.
     */
    public function openForDriver(int $driverId, ?int $ignoreDispatchId = null): ?Dispatch
    {
        return Dispatch::whereNull('time_in')
            ->when($ignoreDispatchId, fn($q) => $q->where('id', '!=', $ignoreDispatchId))
            ->where(fn ($q) => $q
                ->where('driver_id', $driverId)
                ->orWhere('second_driver_id', $driverId))
            ->first();
    }

    /**
     * Asserts that vehicle and crew are free before transaction commit.
     */
    public function assertFree(int $vehicleId, int $driverId, ?int $secondDriverId = null, ?int $ignoreDispatchId = null): void
    {
        if ($clash = $this->openForVehicle($vehicleId, $ignoreDispatchId)) {
            throw ValidationException::withMessages([
                'vehicle_id' => "Vehicle is already out on active dispatch ({$clash->mission_type}).",
            ]);
        }

        if ($clash = $this->openForDriver($driverId, $ignoreDispatchId)) {
            throw ValidationException::withMessages([
                'driver_id' => "Selected driver is already deployed on mission ({$clash->mission_type}).",
            ]);
        }

        if ($secondDriverId && ($clash = $this->openForDriver($secondDriverId, $ignoreDispatchId))) {
            throw ValidationException::withMessages([
                'second_driver_id' => "Secondary driver is already deployed on mission ({$clash->mission_type}).",
            ]);
        }
    }
}
```

---

### Listing 5. Automated Time-Driven and Mileage-Driven Alert Engine
**File Path:** `backend/app/Services/MaintenanceAlerts.php`  
**Language:** PHP (Laravel Service Class / Scheduled Engine)  
**Requirements Addressed:** FR-10 (License Expiry Monitoring), FR-16 (PM Scheduling), FR-24 & FR-25 (Automated Notifications)  
**Description:** Evaluates preventive maintenance schedules against current vehicle mileage and calendar dates, detecting when vehicles cross the `Due Soon` or `Due` thresholds. Incorporates idempotency controls to prevent spamming notifications while escalating alerts from warning to overdue.

```php
<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PmSchedule;
use App\Models\User;

class MaintenanceAlerts
{
    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    /**
     * Evaluates and raises alerts for a single PM schedule based on threshold status.
     */
    public function raiseForSchedule(PmSchedule $schedule): bool
    {
        if (! in_array($schedule->status, [PmSchedule::STATUS_DUE_SOON, PmSchedule::STATUS_DUE], true)) {
            return false;
        }

        $adminType = $schedule->status === PmSchedule::STATUS_DUE
            ? Notification::TYPE_PM_DUE
            : Notification::TYPE_PM_DUE_SOON;

        // Idempotency check: prevent duplicate notifications for the same threshold state
        $alreadyRaised = Notification::query()
            ->withoutGlobalScopes()
            ->where('type', $adminType)
            ->where('data->pm_schedule_id', $schedule->id)
            ->exists();

        if ($alreadyRaised) {
            return false;
        }

        $urgency = $schedule->status === PmSchedule::STATUS_DUE ? 'DUE NOW' : 'DUE SOON';
        $vehicle = $schedule->vehicle;

        $this->dispatcher->dispatchToAdmins(
            agencyId: $schedule->agency_id,
            type: $adminType,
            title: "Preventive Maintenance {$urgency}: {$vehicle->plate_number}",
            message: "Target service '{$schedule->service_target}' is {$schedule->status}. Immediate maintenance coordination required.",
            data: ['pm_schedule_id' => $schedule->id, 'vehicle_id' => $vehicle->id]
        );

        return true;
    }
}
```

---

### Listing 6. Native Android Firebase Push Token Lifecycle Manager
**File Path:** `mobile/app/src/main/java/com/example/rvms/push/PushTokenRegistrar.kt`  
**Language:** Kotlin (Android Native / Coroutines / Firebase Messaging)  
**Requirements Addressed:** FR-24 (Driver Push Notifications), NFR-04 (Reliability of Push Delivery)  
**Description:** Manages the registration and synchronization of Firebase Cloud Messaging (FCM) device push tokens with the RVMS backend upon driver authentication. Operates asynchronously via Kotlin Coroutines with fail-safe error handling to ensure push service interruptions never prevent field drivers from accessing offline-stored vehicle records.

```kotlin
package com.example.rvms.push

import android.util.Log
import com.example.rvms.data.ServiceLocator
import com.google.firebase.messaging.FirebaseMessaging
import kotlinx.coroutines.tasks.await

/**
 * Registers Android device tokens with the RVMS backend for real-time alerts (FR-24).
 * Uses fail-safe coroutine execution so Firebase communication never blocks field operations.
 */
object PushTokenRegistrar {

    private const val TAG = "RvmsPush"

    /** Called upon successful driver login or FCM token refresh */
    suspend fun register() {
        val token = currentToken() ?: return
        registerToken(token)
    }

    /** Unregisters device push token prior to session clearance upon sign-out */
    suspend fun unregister() {
        runCatchingSilently { ServiceLocator.notificationRepository.clearDevice() }
    }

    suspend fun registerToken(token: String) {
        runCatchingSilently { ServiceLocator.notificationRepository.registerDevice(token) }
    }

    private suspend fun currentToken(): String? = try {
        FirebaseMessaging.getInstance().token.await()
    } catch (t: Throwable) {
        Log.w(TAG, "No FCM token available (${t.javaClass.simpleName}). Push is inert.")
        null
    }

    private inline fun runCatchingSilently(block: () -> Unit) {
        try {
            block()
        } catch (t: Throwable) {
            Log.w(TAG, "Push token registration failed non-fatally: ${t.message}")
        }
    }
}
```
