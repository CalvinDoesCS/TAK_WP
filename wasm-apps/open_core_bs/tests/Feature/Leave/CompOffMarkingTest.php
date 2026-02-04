<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveRequestStatus;
use App\Models\CompensatoryOff;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesUsers;

class CompOffMarkingTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    protected User $admin;

    protected User $employee;

    protected LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name' => 'field_employee',
            'guard_name' => 'web',
            'is_mobile_app_access_enabled' => true,
        ]);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create employee
        $this->employee = User::factory()->create();
        $this->employee->assignRole('field_employee');

        // Create leave type
        $this->leaveType = LeaveType::create([
            'name' => 'Compensatory Off',
            'code' => 'COMP',
            'status' => 'active',
            'is_comp_off_type' => true,
        ]);
    }

    public function test_comp_off_is_marked_as_used_when_leave_is_approved_via_web(): void
    {
        // Create an approved comp-off
        $compOff = CompensatoryOff::create([
            'user_id' => $this->employee->id,
            'worked_date' => now()->subDays(7),
            'hours_worked' => 8,
            'comp_off_days' => 1,
            'reason' => 'Worked on weekend',
            'expiry_date' => now()->addMonths(3),
            'status' => 'approved',
            'is_used' => false,
            'approved_by_id' => $this->admin->id,
            'approved_at' => now(),
        ]);

        // Create a leave request using the comp-off
        $leaveRequest = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'from_date' => now()->addDays(1),
            'to_date' => now()->addDays(1),
            'total_days' => 1,
            'use_comp_off' => true,
            'comp_off_days_used' => 1,
            'comp_off_ids' => [$compOff->id],
            'status' => LeaveRequestStatus::PENDING,
            'user_notes' => 'Taking comp-off',
        ]);

        // Admin approves the leave request
        $response = $this->actingAs($this->admin)
            ->postJson("/leave/{$leaveRequest->id}/approve", [
                'notes' => 'Approved',
            ]);

        $response->assertOk()
            ->assertJson(['status' => 'success']);

        // Verify the comp-off is now marked as used
        $compOff->refresh();
        $this->assertTrue($compOff->is_used, 'Comp-off should be marked as used');
        $this->assertEquals($leaveRequest->id, $compOff->leave_request_id, 'Comp-off should be linked to the leave request');
        $this->assertNotNull($compOff->used_date, 'Comp-off used_date should be set');
    }

    public function test_comp_off_is_marked_as_used_when_leave_is_approved_via_action_ajax(): void
    {
        // Create an approved comp-off
        $compOff = CompensatoryOff::create([
            'user_id' => $this->employee->id,
            'worked_date' => now()->subDays(7),
            'hours_worked' => 8,
            'comp_off_days' => 1,
            'reason' => 'Worked on weekend',
            'expiry_date' => now()->addMonths(3),
            'status' => 'approved',
            'is_used' => false,
            'approved_by_id' => $this->admin->id,
            'approved_at' => now(),
        ]);

        // Create a leave request using the comp-off
        $leaveRequest = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'from_date' => now()->addDays(1),
            'to_date' => now()->addDays(1),
            'total_days' => 1,
            'use_comp_off' => true,
            'comp_off_days_used' => 1,
            'comp_off_ids' => [$compOff->id],
            'status' => LeaveRequestStatus::PENDING,
            'user_notes' => 'Taking comp-off',
        ]);

        // Admin approves via actionAjax endpoint
        $response = $this->actingAs($this->admin)
            ->postJson('/leave/action', [
                'id' => $leaveRequest->id,
                'status' => 'approved',
                'adminNotes' => 'Approved via bulk action',
            ]);

        $response->assertOk()
            ->assertJson(['status' => 'success']);

        // Verify the comp-off is now marked as used
        $compOff->refresh();
        $this->assertTrue($compOff->is_used, 'Comp-off should be marked as used');
        $this->assertEquals($leaveRequest->id, $compOff->leave_request_id, 'Comp-off should be linked to the leave request');
        $this->assertNotNull($compOff->used_date, 'Comp-off used_date should be set');
    }

    public function test_comp_off_is_marked_as_used_when_leave_is_approved_via_api(): void
    {
        // Create an approved comp-off
        $compOff = CompensatoryOff::create([
            'user_id' => $this->employee->id,
            'worked_date' => now()->subDays(7),
            'hours_worked' => 8,
            'comp_off_days' => 1,
            'reason' => 'Worked on weekend',
            'expiry_date' => now()->addMonths(3),
            'status' => 'approved',
            'is_used' => false,
            'approved_by_id' => $this->admin->id,
            'approved_at' => now(),
        ]);

        // Create a leave request using the comp-off
        $leaveRequest = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'from_date' => now()->addDays(1),
            'to_date' => now()->addDays(1),
            'total_days' => 1,
            'use_comp_off' => true,
            'comp_off_days_used' => 1,
            'comp_off_ids' => [$compOff->id],
            'status' => LeaveRequestStatus::PENDING,
            'user_notes' => 'Taking comp-off',
        ]);

        // Manager approves via API
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/V1/approval/leave-action', [
                'id' => $leaveRequest->id,
                'status' => 'approved',
                'comments' => 'Approved via mobile',
            ]);

        $response->assertOk()
            ->assertJson(['status' => 'success']);

        // Verify the comp-off is now marked as used
        $compOff->refresh();
        $this->assertTrue($compOff->is_used, 'Comp-off should be marked as used');
        $this->assertEquals($leaveRequest->id, $compOff->leave_request_id, 'Comp-off should be linked to the leave request');
        $this->assertNotNull($compOff->used_date, 'Comp-off used_date should be set');
    }

    public function test_used_comp_off_cannot_be_reused_for_new_leave_request(): void
    {
        // Create an approved and USED comp-off
        $compOff = CompensatoryOff::create([
            'user_id' => $this->employee->id,
            'worked_date' => now()->subDays(7),
            'hours_worked' => 8,
            'comp_off_days' => 1,
            'reason' => 'Worked on weekend',
            'expiry_date' => now()->addMonths(3),
            'status' => 'approved',
            'is_used' => true, // Already used
            'used_date' => now()->subDays(1),
            'leave_request_id' => 999, // Linked to a previous leave request
            'approved_by_id' => $this->admin->id,
            'approved_at' => now(),
        ]);

        // Verify that canBeUsed() returns false
        $this->assertFalse($compOff->canBeUsed(), 'A used comp-off should not be available for use');

        // The available() scope should not return this comp-off
        $availableCompOffs = CompensatoryOff::available()
            ->where('user_id', $this->employee->id)
            ->get();

        $this->assertFalse(
            $availableCompOffs->contains('id', $compOff->id),
            'Used comp-off should not appear in available comp-offs'
        );
    }

    public function test_multiple_comp_offs_are_marked_as_used_when_leave_is_approved(): void
    {
        // Create two approved comp-offs
        $compOff1 = CompensatoryOff::create([
            'user_id' => $this->employee->id,
            'worked_date' => now()->subDays(14),
            'hours_worked' => 8,
            'comp_off_days' => 1,
            'reason' => 'Worked on weekend 1',
            'expiry_date' => now()->addMonths(3),
            'status' => 'approved',
            'is_used' => false,
            'approved_by_id' => $this->admin->id,
            'approved_at' => now(),
        ]);

        $compOff2 = CompensatoryOff::create([
            'user_id' => $this->employee->id,
            'worked_date' => now()->subDays(7),
            'hours_worked' => 8,
            'comp_off_days' => 1,
            'reason' => 'Worked on weekend 2',
            'expiry_date' => now()->addMonths(3),
            'status' => 'approved',
            'is_used' => false,
            'approved_by_id' => $this->admin->id,
            'approved_at' => now(),
        ]);

        // Create a leave request using both comp-offs
        $leaveRequest = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'from_date' => now()->addDays(1),
            'to_date' => now()->addDays(2),
            'total_days' => 2,
            'use_comp_off' => true,
            'comp_off_days_used' => 2,
            'comp_off_ids' => [$compOff1->id, $compOff2->id],
            'status' => LeaveRequestStatus::PENDING,
            'user_notes' => 'Taking multiple comp-offs',
        ]);

        // Admin approves the leave request
        $response = $this->actingAs($this->admin)
            ->postJson("/leave/{$leaveRequest->id}/approve", [
                'notes' => 'Approved',
            ]);

        $response->assertOk();

        // Verify both comp-offs are marked as used
        $compOff1->refresh();
        $compOff2->refresh();

        $this->assertTrue($compOff1->is_used, 'First comp-off should be marked as used');
        $this->assertEquals($leaveRequest->id, $compOff1->leave_request_id);

        $this->assertTrue($compOff2->is_used, 'Second comp-off should be marked as used');
        $this->assertEquals($leaveRequest->id, $compOff2->leave_request_id);
    }

    public function test_comp_off_is_released_when_leave_is_cancelled(): void
    {
        // Create an approved comp-off
        $compOff = CompensatoryOff::create([
            'user_id' => $this->employee->id,
            'worked_date' => now()->subDays(7),
            'hours_worked' => 8,
            'comp_off_days' => 1,
            'reason' => 'Worked on weekend',
            'expiry_date' => now()->addMonths(3),
            'status' => 'approved',
            'is_used' => false,
            'approved_by_id' => $this->admin->id,
            'approved_at' => now(),
        ]);

        // Create and approve a leave request
        $leaveRequest = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'from_date' => now()->addDays(5),
            'to_date' => now()->addDays(5),
            'total_days' => 1,
            'use_comp_off' => true,
            'comp_off_days_used' => 1,
            'comp_off_ids' => [$compOff->id],
            'status' => LeaveRequestStatus::PENDING,
            'user_notes' => 'Taking comp-off',
        ]);

        // Approve the leave
        $this->actingAs($this->admin)
            ->postJson("/leave/{$leaveRequest->id}/approve", ['notes' => 'Approved']);

        // Verify comp-off is used
        $compOff->refresh();
        $this->assertTrue($compOff->is_used);

        // Cancel the leave request
        $response = $this->actingAs($this->admin)
            ->postJson('/leave/action', [
                'id' => $leaveRequest->id,
                'status' => 'cancelled',
                'adminNotes' => 'Plans changed',
            ]);

        $response->assertOk();

        // Verify the comp-off is released and can be used again
        $compOff->refresh();
        $this->assertFalse($compOff->is_used, 'Comp-off should be released after cancellation');
        $this->assertNull($compOff->leave_request_id, 'Comp-off leave_request_id should be null');
        $this->assertNull($compOff->used_date, 'Comp-off used_date should be null');
    }

    public function test_leave_request_without_comp_off_does_not_affect_comp_offs(): void
    {
        // Create an approved comp-off (not used in leave request)
        $compOff = CompensatoryOff::create([
            'user_id' => $this->employee->id,
            'worked_date' => now()->subDays(7),
            'hours_worked' => 8,
            'comp_off_days' => 1,
            'reason' => 'Worked on weekend',
            'expiry_date' => now()->addMonths(3),
            'status' => 'approved',
            'is_used' => false,
            'approved_by_id' => $this->admin->id,
            'approved_at' => now(),
        ]);

        // Create a leave request WITHOUT using comp-off
        $leaveRequest = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'from_date' => now()->addDays(1),
            'to_date' => now()->addDays(1),
            'total_days' => 1,
            'use_comp_off' => false, // Not using comp-off
            'status' => LeaveRequestStatus::PENDING,
            'user_notes' => 'Regular leave',
        ]);

        // Admin approves the leave request
        $response = $this->actingAs($this->admin)
            ->postJson("/leave/{$leaveRequest->id}/approve", [
                'notes' => 'Approved',
            ]);

        $response->assertOk();

        // Verify the comp-off is still available
        $compOff->refresh();
        $this->assertFalse($compOff->is_used, 'Comp-off should still be available');
        $this->assertNull($compOff->leave_request_id);
    }
}
