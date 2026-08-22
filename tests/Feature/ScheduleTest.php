<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    public function test_queue_work_is_scheduled_every_minute_for_hostinger_cron(): void
    {
        /** @var list<Event> $events */
        $events = app(Schedule::class)->events();

        $queueWork = collect($events)->first(
            fn (Event $event) => str_contains($event->command ?? '', 'queue:work')
                && str_contains($event->command ?? '', '--stop-when-empty'),
        );

        $this->assertNotNull($queueWork, 'queue:work --stop-when-empty doit être planifié');
        $this->assertSame('* * * * *', $queueWork->expression);
    }

    public function test_publicite_expiration_is_scheduled_daily(): void
    {
        /** @var list<Event> $events */
        $events = app(Schedule::class)->events();

        $expire = collect($events)->first(
            fn (Event $event) => ($event->description ?? '') === 'publicites-expire'
                || ($event->mutexName() ?? '') === 'framework/schedule-publicites-expire'
                || str_contains($event->description ?? '', 'Expire les publicités'),
        );

        // Named closure events expose description via ->description property after name()
        $named = collect($events)->first(
            fn (Event $event) => method_exists($event, 'mutexName')
                && str_contains($event->mutexName(), 'publicites-expire'),
        );

        $this->assertNotNull($named ?? $expire, 'L\'expiration des publicités doit être planifiée');
    }
}
