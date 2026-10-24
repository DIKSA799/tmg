<?php

namespace Tests\Feature\Ambassador;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_an_ambassador_can_upload_a_photo_that_is_cropped_and_compressed(): void
    {
        Storage::fake('public');

        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);

        $this->actingAs($me, 'ambassador')
            ->post(route('tmgamb.profile.photo'), [
                'photo' => UploadedFile::fake()->image('portrait.jpg', 1200, 900),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $me->refresh();

        $this->assertSame('ambassadors/'.$me->id.'.webp', $me->photo_path);
        Storage::disk('public')->assertExists($me->photo_path);

        $size = getimagesize(Storage::disk('public')->path($me->photo_path));

        $this->assertNotFalse($size);
        $this->assertSame(600, $size[0]);
        $this->assertSame(600, $size[1]);
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');

        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);

        $this->actingAs($me, 'ambassador')
            ->from(route('tmgamb.profile'))
            ->post(route('tmgamb.profile.photo'), [
                'photo' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertNull($me->fresh()->photo_path);
    }

    public function test_guests_cannot_upload_a_photo(): void
    {
        $this->post(route('tmgamb.profile.photo'))
            ->assertRedirect(route('tmgamb.login'));
    }
}
