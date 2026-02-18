<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\PhotoFilterService;
use PHPUnit\Framework\TestCase;

class PhotoFilterServiceTest extends TestCase
{
    private PhotoFilterService $filterService;

    protected function setUp(): void
    {
        $this->filterService = new PhotoFilterService();
    }

    public function testProcessFiltersWithEmptyParameters(): void
    {
        $filters = $this->filterService->processFilters([]);

        $this->assertEmpty($filters);
        $this->assertFalse($this->filterService->hasActiveFilters($filters));
    }

    public function testProcessFiltersWithLocation(): void
    {
        $parameters = ['location' => '  Paris  '];
        $filters = $this->filterService->processFilters($parameters);

        $this->assertEquals(['location' => 'Paris'], $filters);
        $this->assertTrue($this->filterService->hasActiveFilters($filters));
    }

    public function testProcessFiltersWithCamera(): void
    {
        $parameters = ['camera' => 'Canon EOS 5D'];
        $filters = $this->filterService->processFilters($parameters);

        $this->assertEquals(['camera' => 'Canon EOS 5D'], $filters);
        $this->assertTrue($this->filterService->hasActiveFilters($filters));
    }

    public function testProcessFiltersWithDescription(): void
    {
        $parameters = ['description' => 'beautiful sunset'];
        $filters = $this->filterService->processFilters($parameters);

        $this->assertEquals(['description' => 'beautiful sunset'], $filters);
        $this->assertTrue($this->filterService->hasActiveFilters($filters));
    }

    public function testProcessFiltersWithUsername(): void
    {
        $parameters = ['username' => '  photographer123  '];
        $filters = $this->filterService->processFilters($parameters);

        $this->assertEquals(['username' => 'photographer123'], $filters);
        $this->assertTrue($this->filterService->hasActiveFilters($filters));
    }

    public function testProcessFiltersWithValidDates(): void
    {
        $parameters = [
            'taken_at_from' => '2023-01-15',
            'taken_at_to' => '2023-12-31'
        ];
        $filters = $this->filterService->processFilters($parameters);

        $this->assertArrayHasKey('taken_at_from', $filters);
        $this->assertArrayHasKey('taken_at_to', $filters);
        $this->assertInstanceOf(\DateTimeImmutable::class, $filters['taken_at_from']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $filters['taken_at_to']);
        $this->assertTrue($this->filterService->hasActiveFilters($filters));
    }

    public function testProcessFiltersWithInvalidDates(): void
    {
        $parameters = [
            'taken_at_from' => 'invalid-date',
            'taken_at_to' => '2023-13-45'
        ];
        $filters = $this->filterService->processFilters($parameters);

        $this->assertEmpty($filters);
        $this->assertFalse($this->filterService->hasActiveFilters($filters));
    }

    public function testProcessFiltersWithMixedValidAndInvalidData(): void
    {
        $parameters = [
            'location' => 'Paris',
            'taken_at_from' => 'invalid-date',
            'camera' => 'Canon'
        ];
        $filters = $this->filterService->processFilters($parameters);

        $expected = [
            'location' => 'Paris',
            'camera' => 'Canon'
        ];

        $this->assertEquals($expected, $filters);
        $this->assertTrue($this->filterService->hasActiveFilters($filters));
    }

    public function testProcessFiltersIgnoresEmptyValues(): void
    {
        $parameters = [
            'location' => '',
            'camera' => '   ',
            'description' => null
        ];
        $filters = $this->filterService->processFilters($parameters);

        $this->assertEmpty($filters);
        $this->assertFalse($this->filterService->hasActiveFilters($filters));
    }

    public function testGetFilterSummaryWithSingleFilter(): void
    {
        $filters = ['location' => 'Paris'];
        $summary = $this->filterService->getFilterSummary($filters);

        $this->assertEquals(['Location: Paris'], $summary);
    }

    public function testGetFilterSummaryWithMultipleFilters(): void
    {
        $filters = [
            'location' => 'Paris',
            'camera' => 'Canon EOS',
            'description' => 'sunset',
            'username' => 'photographer'
        ];
        $summary = $this->filterService->getFilterSummary($filters);

        $expected = [
            'Location: Paris',
            'Camera: Canon EOS',
            'Description contains: sunset',
            'Username: photographer'
        ];

        $this->assertEquals($expected, $summary);
    }

    public function testGetFilterSummaryWithDateRange(): void
    {
        $filters = [
            'taken_at_from' => new \DateTimeImmutable('2023-01-15'),
            'taken_at_to' => new \DateTimeImmutable('2023-12-31')
        ];
        $summary = $this->filterService->getFilterSummary($filters);

        $expected = [
            'From: 2023-01-15',
            'To: 2023-12-31'
        ];

        $this->assertEquals($expected, $summary);
    }

    public function testGetFilterSummaryWithEmptyFilters(): void
    {
        $filters = [];
        $summary = $this->filterService->getFilterSummary($filters);

        $this->assertEmpty($summary);
    }

    public function testDateTimeValidationSetsTimeToBeginningOfDay(): void
    {
        $parameters = ['taken_at_from' => '2023-01-15'];
        $filters = $this->filterService->processFilters($parameters);

        $this->assertEquals('00:00:00', $filters['taken_at_from']->format('H:i:s'));
    }

    public function testDateTimeValidationHandlesDateTimeWithTime(): void
    {
        $parameters = ['taken_at_from' => '2023-01-15 14:30:00'];
        $filters = $this->filterService->processFilters($parameters);

        $this->assertEquals('14:30:00', $filters['taken_at_from']->format('H:i:s'));
    }
}
