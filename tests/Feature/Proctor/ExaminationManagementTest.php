<?php

namespace Tests\Feature\Proctor;

use App\Models\Examination;
use App\Models\User;
use Tests\Concerns\RefreshMongoDatabase;
use Tests\TestCase;

class ExaminationManagementTest extends TestCase
{
    use RefreshMongoDatabase;

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'title' => 'Midterm Exam',
            'subject' => 'Networking 2',
            'section' => 'BSIT-3A',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '13:00',
            'end_time' => '14:00',
        ];
    }

    public function test_proctor_can_create_an_examination_with_an_access_code(): void
    {
        $proctor = User::factory()->proctor()->create();

        $this->actingAs($proctor)
            ->post(route('proctor.examinations.store'), $this->validPayload())
            ->assertRedirect(route('proctor.examinations.index'))
            ->assertSessionHas('success');

        $examination = Examination::first();

        $this->assertNotNull($examination);
        $this->assertSame('Midterm Exam', $examination->title);
        $this->assertSame((string) $proctor->getKey(), (string) $examination->proctor_id);
        $this->assertSame(Examination::STATUS_SCHEDULED, $examination->status);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{6}$/', $examination->access_code);
    }

    public function test_examination_creation_is_validated_on_the_backend(): void
    {
        $proctor = User::factory()->proctor()->create();

        $this->actingAs($proctor)
            ->post(route('proctor.examinations.store'), [
                'title' => '',
                'subject' => '',
                'section' => '',
                'scheduled_date' => 'not-a-date',
                'start_time' => '14:00',
                'end_time' => '13:00',
            ])
            ->assertSessionHasErrors(['title', 'subject', 'section', 'scheduled_date', 'end_time']);

        $this->assertSame(0, Examination::count());
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $proctor = User::factory()->proctor()->create();

        $this->actingAs($proctor)
            ->post(route('proctor.examinations.store'), [
                ...$this->validPayload(),
                'start_time' => '14:00',
                'end_time' => '14:00',
            ])
            ->assertSessionHasErrors('end_time');

        $this->assertSame(0, Examination::count());
    }

    public function test_proctor_can_start_and_end_their_own_examination(): void
    {
        $proctor = User::factory()->proctor()->create();

        $examination = Examination::factory()->create([
            'proctor_id' => (string) $proctor->getKey(),
            'status' => Examination::STATUS_SCHEDULED,
        ]);

        $this->actingAs($proctor)
            ->patch(route('proctor.examinations.start', $examination))
            ->assertRedirect()
            ->assertSessionHas('success');

        $examination->refresh();
        $this->assertSame(Examination::STATUS_ONGOING, $examination->status);
        $this->assertNotNull($examination->started_at);

        $this->actingAs($proctor)
            ->patch(route('proctor.examinations.end', $examination))
            ->assertRedirect()
            ->assertSessionHas('success');

        $examination->refresh();
        $this->assertSame(Examination::STATUS_COMPLETED, $examination->status);
        $this->assertNotNull($examination->ended_at);
    }

    public function test_another_proctor_cannot_start_or_end_an_examination(): void
    {
        $owner = User::factory()->proctor()->create();
        $other = User::factory()->proctor()->create();

        $examination = Examination::factory()->create([
            'proctor_id' => (string) $owner->getKey(),
            'status' => Examination::STATUS_SCHEDULED,
        ]);

        $this->actingAs($other)
            ->patch(route('proctor.examinations.start', $examination))
            ->assertForbidden();

        $this->assertSame(Examination::STATUS_SCHEDULED, $examination->refresh()->status);
    }

    public function test_an_ongoing_examination_cannot_be_started_twice(): void
    {
        $proctor = User::factory()->proctor()->create();

        $examination = Examination::factory()->ongoing()->create([
            'proctor_id' => (string) $proctor->getKey(),
        ]);

        $this->actingAs($proctor)
            ->patch(route('proctor.examinations.start', $examination))
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}
