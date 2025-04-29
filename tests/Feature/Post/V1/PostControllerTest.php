<?php

namespace Tests\Feature\Post\V1;

use App\Events\V1\NewPostEvent;
use App\Models\V1\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    private $user;
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Event::fake();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }
    public function test_create_post_with_title_and_description_successfully()
    {
        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Mi primer post',
            'description' => 'Esta es una descripción de prueba'
        ]);

        $response->assertStatus(201);


        $this->assertDatabaseHas('posts', [
            'title' => 'Mi primer post',
            'description' => 'Esta es una descripción de prueba',
            'user_id' => $this->user->id
        ]);
    }

    public function test_create_post_with_images_successfully()
    {
        $images = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.png')
        ];

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Post con imágenes',
            'description' => 'Descripción con imágenes',
            'images' => $images
        ]);

        $response->assertStatus(201);
        // Verificar relación en la base de datos
        $this->assertCount(2, $response->json('data.images'));
    }

    public function test_fails_when_images_exceed_max_size()
    {
        $largeImage = UploadedFile::fake()->image('large.jpg')->size(4000); // 4MB

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Post con imagen grande',
            'description' => 'Descripción',
            'images' => [$largeImage]
        ]);

        $response->assertStatus(422);
    }

    public function test_fails_with_invalid_image_format()
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Post con formato inválido',
            'description' => 'Descripción',
            'images' => [$invalidFile]
        ]);

        $response->assertStatus(422);
    }




    public function test_fails_when_uploading_more_than_10_images()
    {
        $images = [];
        for ($i = 0; $i < 11; $i++) {
            $images[] = UploadedFile::fake()->image("photo$i.jpg");
        }

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Post con muchas imágenes',
            'description' => 'Descripción',
            'images' => $images
        ]);

        $response->assertStatus(422);
    }






    public function test_validation_errors()
    {
        // Caso 1: Falta título
        $response1 = $this->postJson('/api/v1/posts', [
            'description' => 'Descripción sin título'
        ]);
        $response1->assertStatus(422);

        // Caso 2: Descripción muy larga
        $response2 = $this->postJson('/api/v1/posts', [
            'title' => 'Título',
            'description' => str_repeat('a', 251)
        ]);
        $response2->assertStatus(422);
    }






    public function test_notifies_followers_when_new_post_is_created()
    {
        // Crear un seguidor
        $follower = User::factory()->create();
        $this->user->followers()->create(['follower_id' => $follower->id]);

        $this->postJson('/api/v1/posts', [
            'title' => 'Post con notificación',
            'description' => 'Los seguidores deben ser notificados'
        ]);

        // Verificar que se disparó el evento
        Event::assertDispatched(NewPostEvent::class, function ($event) {
            return $event->post->title === 'Post con notificación';
        });
    }
}
