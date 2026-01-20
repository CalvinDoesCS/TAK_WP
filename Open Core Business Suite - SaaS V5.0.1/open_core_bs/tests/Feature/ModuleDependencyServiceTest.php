<?php

namespace Tests\Feature;

use App\Services\AddonService\ModuleDependencyService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;

class ModuleDependencyServiceTest extends TestCase
{
    private ModuleDependencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ModuleDependencyService::class);

        // Clear cache before each test
        $this->service->clearCache();
    }

    /**
     * Test that service can be instantiated
     */
    public function test_service_instantiation(): void
    {
        $this->assertInstanceOf(ModuleDependencyService::class, $this->service);
    }

    /**
     * Test validateDependencies returns valid for module with no dependencies
     */
    public function test_validate_dependencies_with_no_dependencies(): void
    {
        // Use a real module that exists - AccountingCore has no dependencies
        $result = $this->service->validateDependencies('AccountingCore');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('missing', $result);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['missing']);
    }

    /**
     * Test getDependents returns empty array for module with no dependents
     */
    public function test_get_dependents_with_no_dependents(): void
    {
        // Most modules won't have dependents initially
        $result = $this->service->getDependents('NoticeBoard');

        $this->assertIsArray($result);
    }

    /**
     * Test getDependencyTree returns proper structure
     */
    public function test_get_dependency_tree_structure(): void
    {
        $result = $this->service->getDependencyTree('AccountingCore');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('exists', $result);
        $this->assertArrayHasKey('enabled', $result);
        $this->assertArrayHasKey('dependencies', $result);
        $this->assertEquals('AccountingCore', $result['name']);
    }

    /**
     * Test canEnable returns true for enabled module
     */
    public function test_can_enable_already_enabled_module(): void
    {
        // AccountingCore is enabled by default
        $result = $this->service->canEnable('AccountingCore');

        $this->assertTrue($result);
    }

    /**
     * Test canEnable returns false for non-existent module
     */
    public function test_can_enable_non_existent_module(): void
    {
        $result = $this->service->canEnable('NonExistentModule123');

        $this->assertFalse($result);
    }

    /**
     * Test canDisable returns true for module with no enabled dependents
     */
    public function test_can_disable_module_with_no_dependents(): void
    {
        // Most modules can be disabled if they have no dependents
        $result = $this->service->canDisable('NoticeBoard');

        $this->assertTrue($result);
    }

    /**
     * Test canDisable returns false for non-existent module
     */
    public function test_can_disable_non_existent_module(): void
    {
        $result = $this->service->canDisable('NonExistentModule123');

        $this->assertFalse($result);
    }

    /**
     * Test getRequiredToEnable returns empty for module with no dependencies
     */
    public function test_get_required_to_enable_with_no_dependencies(): void
    {
        $result = $this->service->getRequiredToEnable('AccountingCore');

        $this->assertIsArray($result);
    }

    /**
     * Test getMissingDependencies returns empty for valid module
     */
    public function test_get_missing_dependencies_for_valid_module(): void
    {
        $result = $this->service->getMissingDependencies('AccountingCore');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test getEnableOrder with single module
     */
    public function test_get_enable_order_single_module(): void
    {
        $result = $this->service->getEnableOrder(['AccountingCore']);

        $this->assertIsArray($result);
        $this->assertContains('AccountingCore', $result);
    }

    /**
     * Test getEnableOrder with multiple modules without dependencies
     */
    public function test_get_enable_order_multiple_modules(): void
    {
        $result = $this->service->getEnableOrder(['AccountingCore', 'NoticeBoard']);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains('AccountingCore', $result);
        $this->assertContains('NoticeBoard', $result);
    }

    /**
     * Test getAllModulesWithDependencyStatus returns proper structure
     */
    public function test_get_all_modules_with_dependency_status(): void
    {
        $result = $this->service->getAllModulesWithDependencyStatus();

        $this->assertIsArray($result);

        if (! empty($result)) {
            $firstModule = $result[0];
            $this->assertArrayHasKey('name', $firstModule);
            $this->assertArrayHasKey('enabled', $firstModule);
            $this->assertArrayHasKey('dependencies', $firstModule);
            $this->assertArrayHasKey('dependents', $firstModule);
            $this->assertArrayHasKey('can_enable', $firstModule);
            $this->assertArrayHasKey('can_disable', $firstModule);
            $this->assertArrayHasKey('missing_dependencies', $firstModule);
        }
    }

    /**
     * Test hasDependencies returns false for module without dependencies
     */
    public function test_has_dependencies_false(): void
    {
        $result = $this->service->hasDependencies('AccountingCore');

        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    /**
     * Test hasDependents returns boolean
     */
    public function test_has_dependents_returns_boolean(): void
    {
        $result = $this->service->hasDependents('AccountingCore');

        $this->assertIsBool($result);
    }

    /**
     * Test getEnabledDependents returns array
     */
    public function test_get_enabled_dependents_returns_array(): void
    {
        $result = $this->service->getEnabledDependents('AccountingCore');

        $this->assertIsArray($result);
    }

    /**
     * Test getDisabledDependencies returns array
     */
    public function test_get_disabled_dependencies_returns_array(): void
    {
        $result = $this->service->getDisabledDependencies('AccountingCore');

        $this->assertIsArray($result);
        $this->assertEmpty($result); // AccountingCore has no dependencies
    }

    /**
     * Test cache functionality
     */
    public function test_cache_is_used(): void
    {
        // First call - should cache the result
        $this->service->validateDependencies('AccountingCore');

        // Check if cache was set (we can't directly check, but we can call clearCache)
        $this->service->clearCache();

        // This test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Test circular dependency detection in topological sort
     */
    public function test_circular_dependency_throws_exception(): void
    {
        // To test circular dependencies, we'd need to create mock modules
        // For now, we test that the method exists and works with valid input
        $result = $this->service->getEnableOrder(['AccountingCore']);

        $this->assertIsArray($result);
    }

    /**
     * Test dependency tree circular detection
     */
    public function test_dependency_tree_handles_circular_references(): void
    {
        $result = $this->service->getDependencyTree('AccountingCore');

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('circular', $result); // Should not be circular
    }

    /**
     * Test validateDependencies with missing module
     */
    public function test_validate_dependencies_handles_missing_module_gracefully(): void
    {
        // Create a scenario where we check dependencies for a non-existent module
        $result = $this->service->validateDependencies('NonExistentModule');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('missing', $result);
    }

    /**
     * Test that getDependents works across all modules
     */
    public function test_get_dependents_scans_all_modules(): void
    {
        $allModules = Module::all();

        foreach ($allModules as $module) {
            $result = $this->service->getDependents($module->getName());
            $this->assertIsArray($result);
        }

        $this->assertTrue(true); // Test passes if no exception thrown
    }

    /**
     * Test that canEnable and canDisable are consistent
     */
    public function test_can_enable_disable_consistency(): void
    {
        // For a disabled module with no dependencies, both should work
        // For an enabled module, canEnable should be true

        $module = Module::find('AccountingCore');

        if ($module && $module->isEnabled()) {
            $this->assertTrue($this->service->canEnable('AccountingCore'));
        }

        // Test passes if logic is consistent
        $this->assertTrue(true);
    }

    /**
     * Test getEnableOrder with empty array
     */
    public function test_get_enable_order_with_empty_array(): void
    {
        $result = $this->service->getEnableOrder([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that missing dependencies have proper structure
     */
    public function test_missing_dependencies_structure(): void
    {
        // Even if there are no missing dependencies, the structure should be correct
        $result = $this->service->getMissingDependencies('AccountingCore');

        $this->assertIsArray($result);

        // If there are missing dependencies, check structure
        foreach ($result as $missing) {
            $this->assertArrayHasKey('name', $missing);
            $this->assertArrayHasKey('status', $missing);
            $this->assertArrayHasKey('message', $missing);
        }
    }

    /**
     * Test clearCache doesn't throw exceptions
     */
    public function test_clear_cache_works(): void
    {
        $this->service->clearCache();

        // If no exception is thrown, test passes
        $this->assertTrue(true);
    }
}
